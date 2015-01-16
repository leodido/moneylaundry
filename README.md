MoneyLaundry
============

[![Latest Stable Version](http://img.shields.io/packagist/v/leodido/moneylaundry.svg?style=flat-square)](https://packagist.org/packages/leodido/moneylaundry) [![Build Status](https://img.shields.io/travis/leodido/moneylaundry.svg?style=flat-square)](https://travis-ci.org/leodido/moneylaundry) [![Coverage](http://img.shields.io/coveralls/leodido/moneylaundry.svg?style=flat-square)](https://coveralls.io/r/leodido/moneylaundry)

Collection of utilities to filter and validate money.

This library includes (will include) a series of classes aimed at **filtering**, **validating**, **formatting**, and **cleaning up** of monetary and currency values.

![breaking bad laundry](bb.jpg)

Components
----------

### Filters

Residing in `MoneyLaundry\Filter` namespace.

1. `Uncurrency`

    Give him a currency and get the corresponding amount, if the input was correctly formatted according to the chosen locale and filter options

2. `Currency` **(*)**

    Give him a number, choose a locale and get back a localized currency amount

### Validators

Residing in `MoneyLaundry\Validator` namespace.

1. Currency **(*)**

Examples
--------

**WIP**

Installation
------------

Add `leodido/moneylaundry` to your `composer.json`.

```json
{
   "require": {
       "leodido/moneylaundry": "v0.1.0"
   }
}
```

To-do
-----

See **(*)**.

---

[![Analytics](https://ga-beacon.appspot.com/UA-49657176-1/moneylaundry)](https://github.com/igrigorik/ga-beacon)