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