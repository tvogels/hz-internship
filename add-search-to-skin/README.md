# Adding our custom search to the deltaskin

Thijs Vogels, May 29

## (1) Copy some files to the corresponding directory in the skin

* `js/search.js`
* `css/search.css`
* `composer.json`
* `composer.lock`
* `build_search_index.php`
* `search.php`

## (2) Install required php extensions via composer:

* Download [Composer](https://getcomposer.org/download/).
* Run `composer.phar install`.

## (3) Add assets to the resource loader

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