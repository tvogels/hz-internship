<html>

<head>
  <title>Visualization: Contexts</title>
  <meta charset="utf-8">
  <link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/3.1.1/css/bootstrap.min.css">
  <link rel="stylesheet" href="/css/csspinner.css">
  <style>
    .node circle {
      cursor: pointer;
      fill: #fff;
      stroke: steelblue;
      stroke-width: 1.5px;
    }

    .node text {
      font-size: 11px;
    }

    path.link {
      fill: none;
      stroke: #ccc;
      stroke-width: 1.5px;
    }
  </style>
</head>

<body>

  <div class="container">

    <h1>Contexts <small>Internship HzBwNature</small></h1>
    <hr>
    <div class="panel panel-default">
      <div class="panel-heading">
        <div class="panel-title">Tree of the Contexts in HzBwNature</div>
      </div>
      <div class="panel-body" id="body">
      </div>
    </div>

  </div>

  <script src="http://ajax.googleapis.com/ajax/libs/jquery/2.1.0/jquery.min.js"></script>
  <script src="http://d3js.org/d3.v3.min.js"></script>
  <script src="/bower_components/handlebars/handlebars.min.js"></script>
  <script src="/bower_components/elasticsearch/elasticsearch.jquery.min.js"></script>
  <script src="/js/contexts.js"></script>

</body>

</html>