<html>
<head>
<title>Create ElasticSearch Index</title>
<meta charset="utf-8">
<link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/3.1.1/css/bootstrap.min.css">
</head>
<body>
<div class="container">
<h1>Create ElasticSearch Index <small>Internship HzBwNature</small></h1>
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
    |?skos:inScheme
  ');

  foreach ($concepts as $c) {

    echo "<h3>{$c->fulltext} <small>{$c->fullurl}</small></h3>\n";
    echo "<p>{$c->printouts->{'Skos:definition'}[0]}</p>";

  }

?>   
</div>
</body>
</html>