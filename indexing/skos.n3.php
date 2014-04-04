<?php 
  header("Content-type: text/n3; Charset: utf-8;");
  include_once('../setup.php');
?>
@prefix skos:   <http://www.w3.org/2004/02/skos/core#> .
@prefix rdf:    <http://www.w3.org/1999/02/22-rdf-syntax-ns#> .
@prefix rdfs:   <http://www.w3.org/2000/01/rdf-schema#> .
@prefix wiki:   <http://<?php echo $wiki ?>/index.php/> .

<?php

  // Find all SKOS Concepts
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

  // Loop through them and format them nicely
  foreach ($concepts as $concept) {
    
    echo "<{$concept->fullurl}> rdf:type skos:Concept ;\n";

    $attributes = array();
    $attributes[] = "skos:prefLabel \"{$concept->fulltext}\"";

    foreach ($concept->printouts->{'Skos:definition'} as $label)
      $attributes[] = "skos:definition \"{$label}\"";

    foreach ($concept->printouts->{'Skos:altLabel'} as $label)
      $attributes[] = "skos:altLabel \"{$label}\"";

    foreach ($concept->printouts->{'Skos:inScheme'} as $label)
      $attributes[] = "skos:inScheme <{$label->fullurl}>";

    foreach ($concept->printouts->{'Skos:related'} as $label)
      $attributes[] = "skos:related <{$label->fullurl}>";

    foreach ($concept->printouts->{'Skosem:broader'} as $label)
      $attributes[] = "skos:broader <{$label->fullurl}>";

    foreach ($concept->printouts->{'Skosem:narrower'} as $label)
      $attributes[] = "skos:narrower <{$label->fullurl}>";

    foreach ($concept->printouts->{'Skosem:partOf'} as $label)
      $attributes[] = "skos:partOf <{$label->fullurl}>";
    
    echo "  " . implode(" ;\n  ",$attributes) . " .\n";

    echo "\n";
  }

?>