# document.ready
$ ->
  # get references to useful entities
  $field = $('#search-query')
  $form = $('#search-form')
  $results = $('#results')

  # start a ElasticSearch client
  client = new $.es.Client
    'host': 'localhost:9200'

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
    $results.addClass 'csspinner line no-overlay'
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
      console.log resp.hits
      $results.append($('<div>').html(result_template(hit))) for hit in hits

