# document.ready
$ ->

  # start a ElasticSearch client
  client = new $.es.Client
    'host': 'localhost:9200'

  # get references to useful entities
  $field = $('#search-query')
  $form = $('#search-form')
  $results = $('#results')

  # set spinner
  $results.addClass 'csspinner'

  # get contexts
  p = client.search
    index: 'hzbwnature'
    type: 'context'
    size: 1000
    body:
      query:
        match_all: {}


  p.then (e) ->

    # done loading
    $results.removeClass 'csspinner'

    # functions for finding out in which contexts people search
    supercontext = {}
    supercontext[a._source.url] = a._source.supercontext for a in e.hits.hits

    children = {}
    children[a._source.supercontext] = [] for a in e.hits.hits
    children[a._source.supercontext].push(a._source.url) for a in e.hits.hits

    # addWeights adds weight recursively to the storage array. 
    # returns new
    addWeight = (url, weight, storage) ->
      # console.log 'add', weight, 'to', url
      if storage[url]?
        storage[url] = storage[url] + weight
      else
        storage[url] = weight
      sc = supercontext[url]
      storage = addWeight(sc, weight, storage) unless url == "ROOT"
      return storage

    # Find the context from a node, given weights and a minimum weight
    findContext = (node, weights, minimum) ->
      # console.log 'finding context within', node, 'for weights above', minimum
      # weight function
      weight = (c) -> if weights[c]? then weights[c] else 0
      # if it has no no kids, node is the one
      return node if !children? || children[node] !instanceof Array
      # find weights of all the children and take the max
      childWeights = ({'node': c, 'weight': weight(c)} for c in children[node])
      maxWeight = _.max childWeights, (w) -> w.weight
      if (maxWeight.weight > minimum) then findContext(maxWeight.node, weights, minimum) else node


    # compile the template for showing results
    source = $('#result-template').html()
    result_template = Handlebars.compile source

    Handlebars.registerHelper 'ifvalue', (conditional, options) ->
      if (options.hash.value == conditional)
        options.fn this
      else
        options.inverse this

    # onsubmit for the form
    $form.submit (e) ->
      # prevent default form behaviour
      e.preventDefault()
      # set spinner
      $results.addClass 'csspinner'
      p = client.search
        index: 'hzbwnature'
        type: 'intentional_element'
        size: 100
        body:
          query:
            multi_match:
              query: $field.val()
              fields: [
                "skos:prefLabel^3"
                "skos:definition"
                "title^3"
                "content"
                "concerns_readable^2"
                "context_readable^2"
              ]
      p.then (resp) ->
        hits = resp.hits.hits
        $results.html "";
        $results.removeClass 'csspinner'
        $results.append($('<div>').html(result_template(hit))) for hit in hits

        # figure out the context
        storage = {}
        storage = addWeight(c, a._score, storage) for c in a._source.context for a in hits
        
        # branch minimum 
        minimum = storage.ROOT * 0.8

        context = findContext 'ROOT', storage, minimum
        $('.context').text(context);

