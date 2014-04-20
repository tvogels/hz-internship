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


  // Add SKOS Concepts
  $ask = new AskApi($wiki);
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

  $verbose = false;

  if ($verbose) {
    echo "<hr>\n";
    echo "<dl class=\"dl-horizontal\">\n";
  }

  foreach ($concepts as $c) {

    // Display
    if ($verbose) {
      echo "<dt>" . prettify($c) . "</dt>\n";
      if (count($c->printouts->{'Skos:definition'})>0) {
        echo "<dd>{$c->printouts->{'Skos:definition'}[0]}</dd>";
      }
    }

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
      "skos:partOf" =>array_map(function ($a) { return $a->fullurl; }, $c->printouts->{'Skosem:partOf'})
    );
    $params['index'] = 'hzbwnature';
    $params['type'] = 'skos_concept';
    $params['id'] = md5($c->fullurl);
    $ret = $elastic->index($params);

    if ($ret['created'] === false) {
      echo "<p class=\"error\">Oops! This concept was not indexed.</p>\n";
    }
  }
  if ($verbose) echo "</dl>";
  echo "<hr>\n";




  // Now let's do all intentional elements

  // Loop through the SKOS concepts and add them to the index /hwbwnature/skos
  echo "<h3>Intentional Elements</h3>\n";

  $verbose = true;

  if ($verbose) {
    echo "<hr>\n";
    echo "<dl>\n";
  }

  // Retrieve the elements
  $elements = $ask->query('[[Category:Intentional Element]]|?Concerns|?Context');

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

  foreach ($elements as $element) {
    $pars = getParagraphs($element->fullurl, $elementPars);

    // Display
    if ($verbose) {
      echo "<dt>" . prettify($element) . "</dt>\n";
      echo "<dd>\n";
      foreach ($pars as $p) {
        // var_dump($p);
      }
      echo "</dd>\n";
    }

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
      "concerns" => array_map(function ($a) { return $a->fullurl; }, $element->printouts->{'Concerns'}),
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

  if ($verbose) echo "</dl>";
  echo "<hr>\n";


?>   
</div>
</body>
</html>