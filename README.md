# mt940

An composer extension to the MT940 parser from the jejik/mt940 library that fixes parsing ING Bank statements in the MT940 format and adds support for Rabobank CSV statements

## Installation

You can install cyberwizzard/MT940 using Composer. You can read more about Composer and its main repository at
[http://packagist.org](http://packagist.org "Packagist"). First install Composer for your project using the instructions on the
Packagist home page, then define your dependency on cyberwizzard/MT940 in your `composer.json` file.

```json
    {
        "require": {
            "cyberwizzard/mt940": ">=0.3"
        }
    }
```

This library follows the [PSR-0 standard](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-0.md). You will need
a PSR-0 compliant autoloader to load the cyberwizzard/MT940 classes. Composer provides one for you in your `vendor/.composer/autoload.php`.

## Usage

```php
<?php

use Jejik\MT940\Reader;
use cyberwizzard\MT940\Parser\RabobankCSV;

$reader = new Reader();

// Load the Rabobank CSV parser
$reader->addParser( 'RabobankCSV', 'cyberwizzard\MT940\Parser\RabobankCSV' );
// Append the list of default bank parsers (optional)
$reader->addParsers( $reader->getDefaultParsers() );

$statements = $reader->getStatements(file_get_contents('mt940.txt'));

foreach ($statements as $statement) {
    echo $statement->getOpeningBalance()->getAmount() . "\n";

    foreach ($statement->getTransactions() as $transaction) {
        echo $transaction->getAmount() . "\n";
    }

    echo $statement->getClosingBalance()->getAmount() . "\n";
}
```

For more information, see the [jejik/MT940 repository](https://github.com/sandermarechal/jejik-mt940).

## Disclaimer
Use this parser at your own risk. While the utmost care is taken to design parsers which are fully compliant with the
official bank statement formats, most of the implementations from the banks themselves seem to have quirks.

This parser is in use in a private project and as such is tested regularly.
