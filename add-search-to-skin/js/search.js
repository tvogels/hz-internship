/*
 *  This hooks up the search box with the custom search engine
 *  jQuery is required
 *  @author Thijs Vogels (info@tvogels.nl)
 */

// Constants

var MIN_LENGTH_FOR_SUGGESTIONS = 2,
    ARROW_UP_KEY = 38,
    ARROW_DOWN_KEY = 40,
    ENTER_KEY = 13,
    ELASTIC_SERVER = "http://localhost:9200",
    ELASTIC_INDEX = "hzbwnature";


// On dom ready ...

$(function() {


  // Get references to DOM elements
  var $searchForm = $('#searchform'),
      $searchInput = $('#searchInput'),
      $suggestions = $('.suggestions');



  // This submits the form and opens the search page

  var submitSearch = function (event) {
    event.preventDefault();
    console.log('go search');
    document.location.href='/index.php/Zoeken?q='+$searchInput.val()+'&fulltext=Search';
  };



  // And this opens a suggestion by its URL

  var openSuggestion = function (url) {
    document.location.href = url;
  };



  // when the search bar focusses, show the suggestions
  // the class focus controls the width and visible the opacity

  $searchInput.focus(function () {
    $suggestions.addClass('focus');
    $searchInput.addClass('selected');
    var text = $(this).val();
    if (text.length >= MIN_LENGTH_FOR_SUGGESTIONS)
      $suggestions.addClass('visible');
  });

  $searchInput.blur(function () {
    $suggestions.removeClass('visible');
    $suggestions.removeClass('focus');
    $searchInput.removeClass('selected');
  });



  // By default, the current suggestion is -1: normal search
  // if the values is >=0, the number is the index of the selected
  // suggestion

  var currentSuggestion = -1;



  // This selects a suggestion and updates the classes of the suggestion list

  selectSuggestion = function (i) {
    currentSuggestion = i;
    $searchInput.removeClass('selected');
    $('.suggestions li').removeClass('selected');
    if (i >= 0) {
      $($('.suggestions li')[i]).addClass('selected');
    } else {
      console.log('normaal zoeken!');
      $searchInput.addClass('selected');
    }
  };


  // On keyup in the search input, we give suggestions
  // in the case of NOT arrow up, arrow down or submit

  $searchInput.keyup(function (e) {
    if (
      e.which != ARROW_UP_KEY && 
      e.which != ARROW_DOWN_KEY && 
      e.which != ENTER_KEY
    ) {
      giveSearchSuggestions(e);
    }
  });

  // go back to normal search when the mouse leaves the suggestions
  // ul.
  $suggestions.hover(function (e) {}, function (e) {
    selectSuggestion(-1);
  });

  // This loads the suggestions and updates the list

  var giveSearchSuggestions = function (event) {

    // If relevant, prevent the default submission of the form
    event.preventDefault();

    // Load the current search text
    var text = $searchInput.val();

    if (text.length >= MIN_LENGTH_FOR_SUGGESTIONS) {

      // Set the suggestions to visible
      $suggestions.addClass('visible');

      // Post to ElasticSearch
      $.post(
        ELASTIC_SERVER+'/'+ELASTIC_INDEX+'/_suggest', 
        '{"suggestions":{"text":"'+text+'","completion":{"field":"suggest","fuzzy":{"fuzziness":0}}}}', 
        function (e) {

          // Store the results in 'results'
          var result = e.suggestions[0].options;



          // Empty the suggestions UL and set the current selection to -1 (normal search)
          $suggestions.html("");

          // for each result, render a LI
          $(result).each(function (key,value) {

            // select a VN page
            if (value.payload.vn_pages.length > 0) {
              var link_page = value.payload.vn_pages[0];
            } else {
              var link_page = value.payload.url;
            }

            var element = $("<li data-key="+key+" data-url=\"" + link_page + "\"><h4>" + value.text + "</h4><p>"+value.payload.context+"</p></li>");
            element.mousedown(function (e) {
              e.preventDefault();
              console.log('open page ',link_page);
              document.location.href = link_page;
            });
            element.hover(function () {
              selectSuggestion(key);
            });
            $suggestions.append(element);
          });

          // Select normal search as the selected option.
          selectSuggestion(-1);
        }
      );

    } else {
      // less than 3 caracters: hide suggestions
      $suggestions.removeClass('visible');
    }
  };



  // Listen to the keydown event of the search input
  // Move the selection on arrows, submit on enter

  $searchInput.keydown(function (e) {
    var code = e.which;
    if (code == ARROW_UP_KEY) {
      // up
      e.preventDefault();
      var next = currentSuggestion-1;
      if (currentSuggestion == -1) {
        next = $('.suggestions li').length-1;
      }
      selectSuggestion(next);
    } else if (code == ARROW_DOWN_KEY) {
      // down
      e.preventDefault();
      var next = currentSuggestion+1;
      if (currentSuggestion == $('.suggestions li').length-1) {
        next = -1;
      }
      selectSuggestion(next);
    } else if (code == ENTER_KEY) {
      // enter
      e.preventDefault();
      if (currentSuggestion == -1) {
        submitSearch(e);
      } else {
        openSuggestion($($('.suggestions li')[currentSuggestion]).attr('data-url'));
      }
    }
  });



  // When submitting the form, search

  $searchForm.submit(submitSearch);






  // Here comes the code for the actual search page

  var selectedContext = null;

  $('[data-context]').click(function (e) {
    e.preventDefault();
    selectedContext = $(this).attr('data-context');
    if (selectedContext == 'null') selectedContext = null;

    $('[data-context]').removeClass('active');
    $(this).addClass('active');

    if (selectedContext != null) {
      $('[data-contexts]').hide();
      $('[data-contexts*='+selectedContext+']').fadeIn();
    } else {
      $('[data-contexts]').fadeIn();
    }


    if (selectedContext != null)
      $('.search-everywhere').fadeIn();
    else
      $('.search-everywhere').fadeOut();

    var count = $(this).attr('data-count');

    if (count == 1)
      var zoekresultaten = 'zoekresultaat';
    else
      var zoekresultaten = 'zoekresultaten';

    if (selectedContext != null)
      $('.count-string').text(count+' '+zoekresultaten+' in de context '+$(this).attr('data-context-name'));
    else
      $('.count-string').text(count+' '+zoekresultaten+'');

  });












});