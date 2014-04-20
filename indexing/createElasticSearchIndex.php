<html>
<head>
<title>Create ElasticSearch Index</title>
<meta charset="utf-8">
<link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/3.1.1/css/bootstrap.min.css">
</head>
<body>
<div class="container">
<h1>Create ElasticSearch Index <small>Internship HzBwNature</small></h1>
<hr>
<?php

  // Include settings & stuff
  require_once('../setup.php');


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
      )
    )
  );
  $indexParams['body']['mappings']['_default_']['properties']['subject'] = array (
    "type" => "string",
    "index_analyzer" => "skos",
    "search_analyzer" => "standard"
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

    // Add to the index
    $params = array();
    $root = 'ROOT';
    if (count($c->printouts->{'Supercontext'}) > 0) 
      $root = $c->printouts->{'Supercontext'}[0]->fullurl;
    $params['body'] = array(
      'url' => $c->fullurl,
      'name' => $c->fulltext,
      'supercontext' => $root,
      'category' => array_map(function ($a) { return $a->fullurl; }, $c->printouts->{'Category'}),
      'category_readable' => array_map(function ($a) { return $a->fulltext; }, $c->printouts->{'Category'})
    );
    $params['index'] = 'hzbwnature';
    $params['type'] = 'skos_context';
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
    // if ($element->printouts->{'Category'}[0]->fulltext == 'Category:SKOS Concept') continue;

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
    $params['body'] = array(
      "url" => $element->fullurl,
      "title" => $element->fulltext,
      "content" => $content,
      "concerns_readable" => implode(array_map(function ($a) { return $a->fulltext; }, $element->printouts->{'Concerns'}), " "),
      "concerns" => array_map(function ($a) { return $a->fullurl; }, $element->printouts->{'Concerns'}),
      "context_readable"=> implode(array_map(function ($a) { return $a->fulltext; }, $element->printouts->{'Context'}), " "),
      "context"=> array_map(function ($a) { return $a->fullurl; }, $element->printouts->{'Context'})
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
</body>
</html>