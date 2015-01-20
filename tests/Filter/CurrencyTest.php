<?php
/**
 * MoneyLaundry
 *
 * @link        https://github.com/leodido/moneylaundry
 * @copyright   Copyright (c) 2015, Leo Di Donato
 * @license     http://opensource.org/licenses/MIT      MIT license
 */
namespace MoneyLaundryTest\Filter;

use MoneyLaundry\Filter\Currency as CurrencyFilter;

/**
 * Class CurrencyTest
 * @group filters
 */
class CurrencyTest extends \PHPUnit_Framework_TestCase
{
    protected $defaultLocale;

    public function setUp()
    {
        $this->defaultLocale = ini_get('intl.default_locale');
        ini_set('intl.default_locale', 'en_US');
    }

    public function tearDown()
    {
        ini_set('intl.default_locale', $this->defaultLocale);
    }

    public function testJustATry()
    {
        $filter = new CurrencyFilter;

        $this->assertEquals('1.234,61 €', $filter->filter(1234.61));
        $this->assertEquals('1.234,61 €', $filter->filter('1234,61'));
        // FIXME? input '1234.61'
        $this->assertEquals('1E10', $filter->filter('1E10'));
        $this->assertEquals('1.5E10', $filter->filter('1.5E10'));
    }
}
