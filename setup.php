<?php

// N E C E S S A R Y   I N C L U D E S

# a class that allows for sending ASK queries to the wiki
include_once('classes/AskApi.php');
include_once('vendor/autoload.php');


// H E L P E R   F U N C T I O N S

// Helper function to print links
function prettify($obj) {
  if (isset($obj->fullurl)) {
    return "<a href=\"{$obj->fullurl}\" target=\"_blank\">{$obj->fulltext}</a>";
  } else {
    return $obj;
  }
}

// S E T T I N G S

$wiki = "wiki.local";

$elasticIndexDir = "/tmp/elasticindex";
$skosN3file = "/Users/thijs/Development/internship/skos/hzbwnature.n3"; 
# generated by /indexing/skos.n3.php
