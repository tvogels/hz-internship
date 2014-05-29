<?php 

  require 'vendor/autoload.php';


  // Define some constants
  define(ROOT_NODE, 'ROOT');
  define(SEARCH_CONTEXT_MIN_WEIGHT, 0.8);



  // Check if a query was given
  if (!isset($_GET['q'])) {
    header('Location: /index.php');
    exit;
  }



  // Create an ElasticSearch client
  
  $esclient = new Elasticsearch\Client();



  // Retrieve all contexts 
  $contexts = $esclient->search([
    'index' => 'hzbwnature',
    'type' => 'context',
    'size' => 1000,
    'body' => [
      'query' => [
        'match_all' => []
      ]
    ]
  ]);
  $contexts = $contexts['hits']['hits'];



  // Get search results

  $search_results = $esclient->search([
    'index' => 'hzbwnature',
    'type' => 'intentional_element',
    'size' => 100,
    'body' => [
      'query' => [
        'multi_match' => [
          'query' => $_GET['q'],
          'fields' => [
            "skos:prefLabel^3",
            "skos:definition",
            "title^3",
            "content",
            "concerns_readable^2",
            "context_readable^2"
          ]
        ]
      ]
    ]
  ]);
  $search_results = $search_results['hits']['hits'];



  // This helper returns the VN page that should be visited 
  // when a link is clicked (if available)

  function vn_url ($source) {
    if (count($source['vn_pages'])>0)
      return $source['vn_pages'][0];
    else
      return $source['url'];   
  }




  // Construct a data structure that will be used
  // to quickly find parent- and children nodes of the
  // context tree
  // The data structures are two dictionaries: 
  // parents[url] = superurl, and
  // children[url] = [child,child,child]

  $parents = [];
  $children = [];
  $info = [];
  foreach ($contexts as $context) {
    $url = $context['_source']['url'];
    $super = $context['_source']['supercontext'];
    $parents[urldecode($url)] = urldecode($super);
    $children[$super][] = $url;
    $info[$url] = $context['_source'];
  }

  $info[ROOT_NODE] = [
    'name' => 'Alle Contexten',
    'url' => ''
  ];

  $contextExists = function ($context) use ($parents) {
    return isset($parents[$context]);
  };


  // Determine the 'search context'

  // (1) Define an array that will store the weight
  //     that is attached to all context for the current
  //     search, and define a function that adds weight
  //     to the nodes recursively.
  
  $weights = [];

  $addWeight = function ($context_url, $weight_to_add) use (&$weights, $parents, &$addWeight, $contextExists) {
    
    // we SKIP invalid contexts
    if ($context_url != ROOT_NODE && !$contextExists($context_url)) return;

    // printf("<b>Add weight to %s.</b><br>\n",$context_url);

    if (isset($weights[$context_url])) {
      $weights[$context_url] += $weight_to_add;
    } else {
      $weights[$context_url] = $weight_to_add;
    }

    // recursive step
    if ($context_url != ROOT_NODE) {
      // printf("Add weight to parent %s.<br>\n",$parents[$context_url]);
      $addWeight($parents[$context_url] , $weight_to_add);
    }

  };


  // (2) Initialize the weights for the current search

  foreach ($search_results as $result) {
    $weight = $result['_score'];
    foreach ($result['_source']['context'] as $context) {
      $addWeight($context, $weight);
    }
  }



  // The search context should have a minimum weight of
  // MIN_PERCENTAGE * WEIGHT[ROOT]
  $min_weight = SEARCH_CONTEXT_MIN_WEIGHT * $weights[ROOT_NODE];



  // This searches for the child with the highest percentage

  $findSearchContext = function ($context_url, $min_weight) use (&$findSearchContext, $weights, $children) {

    // Collect the children of the node and their weights
    $kiddos = $children[$context_url];
    $kiddo_weights = array_map(function ($kid) use ($weights) {
      if (isset($weights[$kid])) return $weights[$kid];
      else return 0.;
    }, $kiddos);


    // If there is a child with a weight > $min_weight, recurse,
    // otherwise, return this context as the search context.
    if (count($kiddos) > 0 && max($kiddo_weights) >= $min_weight) {
      $key = array_keys($kiddo_weights, max($kiddo_weights));
      $key = $key[0];
      return $findSearchContext($kiddos[$key],$min_weight);
    } else {
      return $context_url;
    }
  };

  // Determine the search context

  $search_context = $findSearchContext(ROOT_NODE, $min_weight);


  // And store some info about it

  $search_context_info = $info[$search_context];



  // This traces a context's parents and returns them in an array

  $trace = function ($context_url) use (&$trace, $parents, $contextExists) {


    // we SKIP invalid contexts
    if ($context_url != ROOT_NODE && !$contextExists($context_url)) return array();

    if ($context_url == ROOT_NODE) 
      return array(md5(ROOT_NODE));
    else {
      $parent_trace = $trace($parents[$context_url]);
      array_push($parent_trace,md5($context_url));
      return $parent_trace;
    }
  };



  // Count the number of results per context
  // in a dictionary like $counts[md5 of url] = int.
  $counts = [];
  foreach ($search_results as $result) {
    $url = urldecode($result['_source']['url']);
    foreach ($result['_source']['context'] as $cntxt) {
      $tr = $trace(urldecode($cntxt));
      foreach ($tr as $t) {
        $counts[$t]++;
      }
    }
  }

  $urlCounts = function ($url) use ($counts) {
    $md5 = md5($url);
    if (isset($counts[$md5])) return $counts[$md5];
    else return 0;
  };


?>
<div id="sectionNav"></div>
<div id="body">
  
  <h1>Doorzoek DeltaExpertise</h1>

  <input type="hidden" id="search-query" value="<?php echo htmlentities($_GET['q']) ?>">

  <div id="mw-content-text" lang="nl" dir="ltr" class="mw-content-ltr">
    <p class="count-string"><?php echo count($search_results) ?> zoekresultaten voor "<?php echo $_GET['q'] ?>" ...</p>

    <div id="page">
      <ul class="search-results">
        <?php foreach($search_results as $result): ?>
        <a data-contexts="<?php echo implode(" ",$trace($result['_source']['context'][0])) ?>" href="<?php echo htmlentities(vn_url($result['_source'])) ?>"><li>
          <h2><?php echo htmlentities($result['_source']['title']) ?></h2>
          <p>Context: <?php echo htmlentities($result['_source']['context_readable']) ?>
          <?php
            if (!$contextExists($result['_source']['context'][0])) {
              echo "<span class=\"error\"> !</span>\n";
            }
          ?>
          </p>
        </li></a>
        <?php endforeach ?>
      </ul>
    </div>
    <?php if (count($search_results) > 1): ?>
      <div class="aside search-context-info" id="taxonomy">
        <div>
          <p>Zoek specifieker in:</p>
          <h2>
            <a 
              data-count="<?php echo $urlCounts($search_context) ?>" 
              data-context="<?php echo md5($search_context) ?>" 
              data-context-name="<?php echo htmlentities($search_context_info['name']) ?>"
              href="<?php echo vn_url($search_context_info) ?>"
            >
              <?php echo $search_context_info['name'] ?>  (<?php echo $urlCounts($search_context) ?>)
            </a>
          </h2>
          <ul class="list-unstyled">
            <?php foreach($children[$search_context] as $child): ?>
              <?php if ($urlCounts($child) > 0): ?>
              <li>
                <a 
                  data-count="<?php echo $urlCounts($child) ?>" 
                  data-context="<?php echo md5($child) ?>" 
                  data-context-name="<?php echo htmlentities($info[$child]['name']) ?>"
                  href="<?php echo vn_url($info[$child]) ?>"
                >
                  <?php echo $info[$child]['name'] ?> (<?php echo $urlCounts($child) ?>)
                </a>
              </li>
              <?php endif ?>
            <?php endforeach ?>
          </ul>
          <p><a href="#" data-count="<?php echo count($search_results) ?>" class="search-everywhere" data-context="null">Zoek overal (<?php echo count($search_results) ?>)</a></p>
        </div>
      </div>
    <?php endif ?>
  </div>
</div>