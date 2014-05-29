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







