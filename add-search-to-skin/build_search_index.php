<div id="sectionNav"></div>
<div id="body">
  
<h1>Build the Search Index</h1>
<div id="mw-content-text" lang="nl" dir="ltr" class="mw-content-ltr">
<div id="page"></div>
<?php

  /*
   * Wrapper for the SMW AskApiExtension
   */

  class AskApi {

    private $host;

    public function __construct($host) {

      $this->host = $host;
    
    }

    public function query($q) {
      $url = $this->host . '/api.php';
      $response = json_decode(
        file_get_contents("http://{$url}?action=ask&format=json&query=" . urlencode($q . "|limit=5000"))
      );
      return $response->query->results;
    }

  }

  // Include settings & stuff
  $wiki = "wiki.local";
  $elasticIndexDir = "/tmp/elasticindex";
  $skosN3file = "/Users/thijs/Development/internship/skos/hzbwnature.n3"; 
  require 'vendor/autoload.php';

  // Helper function to print links
  function prettify($obj) {
    if (isset($obj->fullurl)) {
      return "<a href=\"{$obj->fullurl}\" target=\"_blank\">{$obj->fulltext}</a>";
    } else {
      return $obj;
    }
  }


  // Instantiate Elastic Client
  $elastic = new Elasticsearch\Client();
  $ask = new AskApi($wiki);




  // Delete the hzbwnature index
  $deleteParams['index'] = 'hzbwnature';
  try { $elastic->indices()->delete($deleteParams); }
  catch (Exception $e) {}


  // Create the index
  $indexParams['index']  = 'hzbwnature';
  $indexParams['body']['settings']['index']['analysis'] = array (
    "filter" => array (
      "skosfilter" => array (
        "type" => "skos",
        "path" => $elasticIndexDir,
        "skosFile" => $skosN3file,
        "expansionType" => "URI"
      )
    ),
    "analyzer" => array (
      "skos" => array (
        "type" => "custom",
        "tokenizer" => "keyword",
        "filter" => "skosfilter"
      ),
      "my_analyzer" => array (
        "type" => "snowball",
        "language" => "Dutch",
        "stopwords" => array("aan","af","al","alles","als","altijd","andere","ben","bij","daar","dan","dat","de","der","deze","die","dit","doch","doen","door","dus","een","eens","en","er","ge","geen","geweest","haar","had","heb","hebben","heeft","hem","het","hier","hij ","hoe","hun","iemand","iets","ik","in","is","ja","je ","kan","kon","kunnen","maar","me","meer","men","met","mij","mijn","moet","na","naar","niet","niets","nog","nu","of","om","omdat","ons","ook","op","over","reeds","te","tegen","toch","toen","tot","u","uit","uw","van","veel","voor","want","waren","was","wat","we","wel","werd","wezen","wie","wij","wil","worden","zal","ze","zei","zelf","zich","zij","zijn","zo","zonder","zou")
      )
    )
  );
  $indexParams['body']['mappings']['_default_']['properties']['subject'] = array (
    "type" => "string",
    "index_analyzer" => "skos",
    "search_analyzer" => "standard"
  );
  $indexParams['body']['mappings']['_default_']['properties']['content'] = array (
    "type" => "string",
    "index_analyzer" => "my_analyzer",
    "search_analyzer" => "my_analyzer"
  );
  $indexParams['body']['mappings']['_default_']['properties']['title'] = array (
    "type" => "string",
    "index_analyzer" => "my_analyzer",
    "search_analyzer" => "my_analyzer"
  );  
  $indexParams['body']['mappings']['_default_']['properties']['suggest'] = array (
    "type" => "completion",
    "index_analyzer" => "simple",
    "search_analyzer" => "simple",
    "payloads" => true
  );
  $elastic->indices()->create($indexParams);











  // Retrieve all paragraphs and collect them by element
  $paragraphs = $ask->query('[[Paragraph::+]]|?Paragraph|?Paragraph subheading|?Paragraph language|?Paragraph number|?Paragraph back link');
  $elementPars = array();
  foreach ($paragraphs as $p) {
    $url = $p->printouts->{'Paragraph back link'}[0]->fullurl;
    $elementPars[$url][] = $p;
  }
  function getParagraphs($url,$elementPars) {
    if (isset($elementPars[$url])) return $elementPars[$url];
    else return array();
  }












  // Add SKOS Concepts
  $concepts = $ask->query('
    [[Category:SKOS Concept]]
    |?skos:altLabel
    |?skos:related
    |?skosem:narrower
    |?skosem:broader
    |?skosem:partOf
    |?skos:definition
  ');

  // Loop through the SKOS concepts and add them to the index /hwbwnature/skos
  echo "<h3>SKOS Concepts</h3>\n";
  echo "<hr>\n";
  echo "<dl class=\"dl-horizontal\">\n";

  foreach ($concepts as $c) {

    // Display
    echo "<dt>" . prettify($c) . "</dt>\n";
    if (count($c->printouts->{'Skos:definition'})>0) {
      echo "<dd>{$c->printouts->{'Skos:definition'}[0]}</dd>";
    }

    // Get paragraphs (if available)
    $pars = getParagraphs($c->fullurl, $elementPars);
    $content = implode(
      array_map(
        function ($p) { 
          return  implode($p->printouts->{'Paragraph subheading'}," ") . "\n " . 
                  implode($p->printouts->{'Paragraph'}," "); 
        }, 
        $pars
      )," \n");

    // Add to index
    $params = array();
    $params['body'] = array(
      "url" => $c->fullurl,
      "skos:prefLabel" => $c->fulltext,
      "skos:altLabel" =>$c->printouts->{'Skos:altLabel'},
      "skos:definition" =>$c->printouts->{'Skos:definition'},
      "skos:related" =>array_map(function ($a) { return $a->fullurl; }, $c->printouts->{'Skos:related'}),
      "skos:narrower" =>array_map(function ($a) { return $a->fullurl; }, $c->printouts->{'Skosem:narrower'}),
      "skos:broader" =>array_map(function ($a) { return $a->fullurl; }, $c->printouts->{'Skosem:broader'}),
      "skos:partOf" =>array_map(function ($a) { return $a->fullurl; }, $c->printouts->{'Skosem:partOf'}),
      "content" => $content
    );
    $params['index'] = 'hzbwnature';
    $params['type'] = 'skos_concept';
    $params['id'] = md5($c->fullurl);
    $ret = $elastic->index($params);

    if ($ret['created'] === false) {
      echo "<p class=\"error\">Oops! This concept was not indexed.</p>\n";
    }
  }
  echo "</dl>";
  echo "<hr>\n";



















  // C O N T E X T S

  echo "<h3>Contexts</h3>\n";
  echo "<hr>\n";
  echo "<dl>\n";

  // Retrieve the contexts
  $contexts = $ask->query('[[Category:Context]]|?Category|?Supercontext');

  foreach ($contexts as $c) {

    // Display
    echo "<dt>" . prettify($c) . "</dt>\n";

    // make a list of terms for auto completion
    // $autoCompleteInput = explode(" ",$c->fulltext);
    $autoCompleteInput = array();
    $autoCompleteInput[] = $c->fulltext;

    // find the VN pages
    $query = "[[Model link::{$c->fulltext}]]";
    $vns = $ask->query($query);
    $vnurls = array();
    foreach ($vns as $key => $value) {
      $vnurls[] = $value->fullurl;
    }

    // Add to the index
    $params = array();
    $super = 'ROOT';
    if (count($c->printouts->{'Supercontext'}) > 0) 
      $super = $c->printouts->{'Supercontext'}[0]->fullurl;

    $super_readable = '';
    if (count($c->printouts->{'Supercontext'}) > 0) 
      $super_readable = $c->printouts->{'Supercontext'}[0]->fulltext;

    $params['body'] = array(
      'url' => $c->fullurl,
      'name' => $c->fulltext,
      'supercontext' => $super,
      'category' => array_map(function ($a) { return $a->fullurl; }, $c->printouts->{'Category'}),
      'category_readable' => array_map(function ($a) { return $a->fulltext; }, $c->printouts->{'Category'}),
      'vn_pages' => $vnurls,
      "suggest" => array(
        "input" => $autoCompleteInput,
        "output" => $c->fulltext,
        "payload" => array("url" => $c->fullurl,"context" => $super_readable,'vn_pages' => $vnurls,"type"=>'context')
      )
    );
    $params['index'] = 'hzbwnature';
    $params['type'] = 'context';
    $params['id'] = md5($c->fullurl);
    $ret = $elastic->index($params);

    if ($ret['created'] === false) {
      echo "<p class=\"error\">Oops! This page was not indexed.</p>\n";
    }

  }

  echo "</dl>";
  echo "<hr>\n";





























  // Now let's do all intentional elements

  // Loop through the SKOS concepts and add them to the index /hwbwnature/skos
  echo "<h3>Intentional Elements</h3>\n";
  echo "<hr>\n";
  echo "<dl>\n";

  // Retrieve the elements
  $elements = $ask->query('[[Category:Intentional Element]][[Context::+]]|?Concerns|?Context|?Category');

  foreach ($elements as $element) {
    // Skip SKOS Concepts

    $type = "intentional"; // used for term suggestion
    // if ($element->printouts->{'Category'}[0]->fulltext == 'Category:SKOS Concept') $type = "skos";

    $pars = getParagraphs($element->fullurl, $elementPars);

    // Display
    echo "<dt>" . prettify($element) . "</dt>\n";
    echo "<dd>\n";
    foreach ($pars as $p) {
      // var_dump($p);
    }
    echo "</dd>\n";

    // Add to the index
    $params = array();
    $content = implode(
      array_map(
        function ($p) { 
          return  implode($p->printouts->{'Paragraph subheading'}," ") . "\n " . 
                  implode($p->printouts->{'Paragraph'}," "); 
        }, 
        $pars
      )," \n");

    // make a list of terms for auto completion
    // $autoCompleteInput = explode(" ",$element->fulltext);
    $autoCompleteInput = array();
    $autoCompleteInput[] = $element->fulltext;


    // find the VN pages
    $query = "[[Model link::{$element->fulltext}]]";
    $vns = $ask->query($query);
    $vnurls = array();
    foreach ($vns as $key => $value) {
      $vnurls[] = $value->fullurl;
    }

    $context_readable = implode(array_map(function ($a) { return $a->fulltext; }, $element->printouts->{'Context'}), " ");
    $params['body'] = array(
      "url" => $element->fullurl,
      "title" => $element->fulltext,
      "content" => $content,
      "concerns_readable" => implode(array_map(function ($a) { return $a->fulltext; }, $element->printouts->{'Concerns'}), " "),
      "concerns" => array_map(function ($a) { return $a->fullurl; }, $element->printouts->{'Concerns'}),
      "context_readable"=> $context_readable,
      "context"=> array_map(function ($a) { return $a->fullurl; }, $element->printouts->{'Context'}),
      "vn_pages" => $vnurls,
      "suggest" => array(
        "input" => $autoCompleteInput,
        "output" => $element->fulltext,
        "payload" => array("url" => $element->fullurl,"vn_pages" => $vnurls,"context" => $context_readable, "type" => $type)
      )
    );
    $params['index'] = 'hzbwnature';
    $params['type'] = 'intentional_element';
    $params['id'] = md5($element->fullurl);
    $ret = $elastic->index($params);

    if ($ret['created'] === false) {
      echo "<p class=\"error\">Oops! This page was not indexed.</p>\n";
    }

  }

  echo "</dl>";
  echo "<hr>\n";


?>   
</div>
</div>