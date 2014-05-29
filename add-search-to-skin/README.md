# Adding our custom search to the deltaskin

Thijs Vogels: May 29, 2014


## Make sure ElasticSearch is running

For example on `http://localhost:9200`.


## Copy files to the corresponding directory in the skin

* `js/search.js`
* `css/search.css`
* `composer.json`
* `composer.lock`
* `build_search_index.php`
* `search.php`

Note: in the PHP files, I use the notation `$bla = ['a','b','c']` for arrays. This is only supported in modern versions of PHP, so possibly this has to be changed to `$bla = array('a','b','c')`.

## Install required php extensions via composer

* Download [Composer](https://getcomposer.org/download/).
* Run `composer.phar install`.


## Add assets to the resource loader

Make sure the section on resource loading in `deltaskin.php` looks like this:

```php
$wgResourceModules['skins.deltaskin'] = array(
    'scripts' => array(
        ...
        'deltaskin/js/search.js'
    ),
    'styles' => array(
        ...
        'deltaskin/css/search.css'
    ),
    ...
);
```


## Edit helper.php

We add some logic to `helper.php` for routing:

```php
if( $this->data['title'] == "Main Page" || $this->data['title'] == "Home" )
{
    $home = true;
    $rubric = $this->data['title'];
}
elseif ($this->data['title'] == "Zoeken") 
{
    $search = true;
}
elseif ($this->data['title'] == "BuildSearchIndex") 
{
    $build_search_index = true;
}
else
...
```

Maybe you want to put the names of the pages in constants. I just did it like this now, but that can be changed.

Note: I see that since my last checkout, the `helper.php` file has changed a lot. Maybe the implementation of this step should be a little different now, but that should not be too difficult.


## Edit DeltaSkin.skin.php

Again, add two routing cases to `DeltaSkin.skin.php`:

```php
if($home)
{
    include 'home.php';       
} 
elseif($search)
{
    include 'search.php';
}
elseif($build_search_index)
{
    include 'build_search_index.php';
}
elseif($subhome)
{
    include 'subhome.php';
}
else
...
```


## Change code of the search box a little bit

The div `#searchBoxFront` should now look like

```html
<div id="searchBoxFront">
    <form action="/index.php/Search" id="searchform" method="GET">
        <fieldset>
            <input autocomplete="off" type="search" name="q" id='searchInput' value='<?php echo $_GET['q'] ?>' placeholder="<?php echo $this->translator->translate( 'searchbutton' )?>">
            <button data-icon="q"><span><?php $this->translator->translate( 'searchbutton' ) ?></span></button>
        </fieldset>
        <ul class="suggestions">
        </ul>
    </form>
</div>
```


## Turn off autocomplete by MediaWiki

Change `LocalSettings.php` and add

```php
// disable standard search suggestions:
$wgUseAjax = false;
```


## Place domain specific ontology file

Move `hzbwnature.n3` to any location on the file system that is readable to Apache. This file is used by ElasticSearch. It can be automatically generated, but the code for that should still be improved.


## Prepare building the index

Set three settings in `build_search_index.php`:

```php
$wiki = "wiki.local"; 
// url of the wiki
$elasticIndexDir = "/tmp/elasticindex"; 
// where can ElasticSearch put some temp files (absolute path)
$skosN3file = "/Users/thijs/Development/internship/skos/hzbwnature.n3"; 
// absolute path of the file that was installed in the previous step.
```

## Build the index

Go to `wikiurl/index.php/BuildSearchIndex` and wait. This goes pretty slow now it is incorporated into the skin. Standalone, it worked faster. We could improve the indexing by changing API calls to PHP instead of using HTTP calls every time.

## Configure search

If ElaticSearch is not running on `localhost:9200`, this should be specified in `search.php`.
