<html>

<head>
  <title>Basic Search</title>
  <meta charset="utf-8">
  <link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/3.1.1/css/bootstrap.min.css">
  <link rel="stylesheet" href="/css/csspinner.css">
  <style>
    .context {
      display:inline;
      float:right;
    }
    .context::before {
      content: "Context: ";
    }
  </style>
</head>

<body>

  <div class="container">

    <h1>Basic Search <small>Internship HzBwNature</small></h1>

    <form class="form-inline" role="form" id="search-form">
      <div class="form-group">
        <label class="sr-only" for="search-query">Search Query</label>
        <input type="text" class="form-control" id="search-query" placeholder="Search Query">
      </div>
      <button type="submit" class="btn btn-default">Go!</button>
    </form>

    <div class="panel panel-default">
      <div class="panel-heading">
        <div class="panel-title">Search Results <div class="context"></div></div>
      </div>
      <div class="panel-body" id="results" style="min-height:20em;">
        
      </div>
    </div>

  </div>

  <script id="result-template" type="text/x-handlebars-template">
    {{#ifvalue _type value="skos_concept"}}
      <h4><a target="_blank" href="{{_source.url}}"><span class="glyphicon glyphicon-certificate"></span> {{_source.skos:prefLabel}}</a> <small>{{_score}}</small></h4>
      <p>Context: {{_source.context_readable}}</p>
    {{else}}
      <h4><a target="_blank" href="{{_source.url}}"><span class="glyphicon glyphicon-file"></span> {{_source.title}}</a> <small>{{_score}}</small></h4>
      <p>Context: {{_source.context_readable}}</p>
    {{/ifvalue}}
  </script>

  <script src="http://ajax.googleapis.com/ajax/libs/jquery/2.1.0/jquery.min.js"></script>
  <script src="/bower_components/handlebars/handlebars.min.js"></script>
  <script src="/bower_components/elasticsearch/elasticsearch.jquery.min.js"></script>
  <script src="/bower_components/underscore/underscore.js"></script>
  <script src="/js/search.js"></script>

</body>

</html>