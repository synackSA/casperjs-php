# casperjs-php
casperjs-php is a simple PHP wrapper for the fine library CasperJS designed to automate
user testing against web pages. It is an extension of alwex/php-casperjs including extra functions
making it easier for you to add your own code segemnts.

It is easy to integrate into PHPUnit test case.

Making a webcrawler has never been so easy !

Installation
------------

Before using casperjs-php is, you need to install both libraries:

1 - **PhantomJS** http://phantomjs.org/download.html

2 - **CasperJS** http://casperjs.org/installation.html

The `composer require synacksa/casperjs-php`

Usage
-----

```php
<?php

use synacksa\casperjsphp\Casper;

$casper = new Casper('/path/to/capserjs/bin/dir/');

// forward options to phantomJS
// for exemple to ignore ssl errors
$casper->setOptions(array(
    'ignore-ssl-errors' => 'yes'
));

// Setup User Agent
$casper->setUserAgent('Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10_1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/52.0.2743.116 Safari/537.36');

// navigate to google web page
$casper->start('http://www.google.com');

// Set the page viewport
$casper->setViewPort(1280, 1024);

// fill the search form and submit it
$casper->fillForm(
        'form[action="/search"]', [
            'q' => 'search'
        ], true);

// wait for 5 seconds
$casper->wait(5000);

// wait for text if needed for 3 seconds
$casper->waitForText('Yahoo', 3000);

// or wait for selector
$casper->waitForSelector('.gbqfb', 3000);

// make a screenshot of the google logo
$casper->captureSelector('/tmp/logo.png', '#hplogo');

// or take a screenshot of a custom area
$casper->capture('/tmp/custom-capture.png', [
        'top' => 0,
        'left' => 0,
        'width' => 800,
        'height' => 600
]);

// click the first result
$casper->click('h3.r a');

// switch to the first iframe
$casper->switchToChildFrame(0);

// make some stuff inside the iframe
$casper->fillForm('#myForm', array(
    'search' => 'my search',
));

// get back to parent
$casper->switchToParentFrame();

// run the casper script
$casper->run();

// check the urls casper get throught
var_dump($casper->getRequestedUrls());

// need to debug? just check the casper output
var_dump($casper->getOutput());

```

You can also create your own snippets to use
```php
$step = <<<FRAGMENT
casper.then(function () {
    this.echo(this.fetchText('h3'));
});
FRAGMENT;

$casper->addStep($step);
```

Or you cause use the `then` function
```php
$thenCode = <<<FRAGMENT;
    this.echo(this.fetchText('h3'));
FRAGMENT;

$casper->then($thenCode);
```

Want to store data, for you to access after the run?
```php
$pageCountVarName = '[PAGE_COUNT]';
$casper->setCustomVar($pageCountVarName);

$step = <<<FRAGMENT
var pageCount = this.evaluate(function () {
    return document.getElementById('page_count').innerHTML;
});
this.echo("$pageCountVarName" + pageCount);
FRAGMENT;

$casper->addStep($step);
$casper->run();

$pageCount = $casper->getCustomVar($pageCountVarName);
```
