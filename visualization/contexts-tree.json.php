<?php 
  header("Content-type: text/json; Charset: utf-8;");
  include_once('../setup.php');

  // Find all Contexts
  $ask = new AskApi($wiki);
  $contexts = $ask->query('[[Category:Context]]|?Category|?Supercontext');

  // Make a dictionary of children per node
  // and one for a name given a url
  $dict = array();
  $nameForUrl = array('ROOT' => 'Root');
  foreach ($contexts as $c) {

    // Determine supercontext
    $super = 'ROOT';
    if (count($c->printouts->Supercontext) > 0)
      $super = $c->printouts->Supercontext[0]->fullurl;

    // Set values for dictionaries
    $dict[$super][] = $c->fullurl;
    $nameForUrl[$c->fullurl] = $c->fulltext;

  }

  // Construct the tree
  function getTree($url) {
    global $nameForUrl, $dict;

    if (isset($dict[$url])) {
      $children = $dict[$url];
      return array(
        'url' => $url,
        'name' => $nameForUrl[$url],
        'children' => array_map("getTree", $children)
      );
    } else {
      return array(
        'url' => $url,
        'name' => $nameForUrl[$url]
      );
    }
  }

  echo json_encode(getTree('ROOT'));
