MoneyLaundry
============

[![Latest Stable Version](http://img.shields.io/packagist/v/leodido/moneylaundry.svg?style=flat-square)](https://packagist.org/packages/leodido/moneylaundry)

Collection of utilities to filter and validate money with **i18n** in mind.

This library includes a series of classes aimed at **filtering**, **validating**, **formatting**, and **cleaning up** of monetary and currency values.

![breaking bad laundry](bb.jpg)

Components
----------

### Filters

Residing in `MoneyLaundry\Filter` namespace.

1. `Uncurrency`

    Give him a currency and get the corresponding amount, if the input was correctly formatted according to the chosen locale and filter options

2. `Currency`

    Give him a number, choose a locale and get back a localized currency amount

### Validators

Residing in `MoneyLaundry\Validator` namespace.

1. `Currency`

    Validate the input as a valid and well-formatted currency amount for the given locale.

Examples
--------

**WIP**

Installation
------------

Add `leodido/moneylaundry` to your `composer.json`.

```json
{
   "require": {
       "leodido/moneylaundry": "v0.2.0"
   }
}
```

Development
-----------

First of all give it a `composer install`.

Then you can run tests:

1. Unit tests suite

    ```
    vendor/bin/phpunit -c $PWD/unit.xml
    ```
    
    - Almost completely covered.

2. Integration tests suite [**WIP**]

    ```
    vendor/bin/phpunit -c $PWD/integration.xml
    ```
    
    - More than 45K tests will be executed
    
    - Results available in `data` directory

---

[![Analytics](https://ga-beacon.appspot.com/UA-49657176-1/moneylaundry)](https://github.com/igrigorik/ga-beacon)