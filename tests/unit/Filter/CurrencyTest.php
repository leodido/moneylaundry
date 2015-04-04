<?php
/**
 * MoneyLaundry
 *
 * @link        https://github.com/leodido/moneylaundry
 * @copyright   Copyright (c) 2015, Leo Di Donato
 * @license     http://opensource.org/licenses/MIT      MIT license
 */
namespace MoneyLaundryUnitTest\Filter;

use MoneyLaundry\Filter\Currency;
use MoneyLaundryUnitTest\AbstractTest;

/**
 * Class CurrencyTest
 */
class CurrencyTest extends AbstractTest
{
    public function testCtor()
    {
        $filter = new Currency();
        $this->assertEquals('en_US', $filter->getLocale());

        $filter = new Currency('it_IT');
        $this->assertEquals('it_IT', $filter->getLocale());

        $filter = new Currency([ 'locale' => 'ar_AE' ]);
        $this->assertEquals('ar_AE', $filter->getLocale());
    }

    public function testLocale()
    {
        $filter = new Currency();
        $filter->setLocale(null);
        $this->assertEquals('en_US', $filter->getLocale());

        $filter = new Currency();
        $filter->setLocale('ru_RU');
        $this->assertEquals('ru_RU', $filter->getLocale());
    }

    public function testFilter()
    {
        $filter = new Currency('it_IT');

        // Filter number represented in scientific notation
        $this->assertInternalType('string', $filter->filter(1e-2));
        $this->assertEquals('0,01 €', $filter->filter(1e-2));
        $this->assertInternalType('string', $filter->filter(1e2));
        $this->assertEquals('100,00 €', $filter->filter(1e2));
        // Filter usual numbers
        $this->assertInternalType('string', $filter->filter(0.01));
        $this->assertEquals('0,01 €', $filter->filter(0.01));
        $this->assertInternalType('string', $filter->filter(100));
        $this->assertEquals('100,00 €', $filter->filter(100));
        $this->assertInternalType('string', $filter->filter(1234.61));
        $this->assertEquals('1.234,61 €', $filter->filter(1234.61));
        // NaN values
        $this->assertInternalType('string', $filter->filter(NAN));
        $this->assertEquals('NaN', $filter->filter(NAN));
        $this->assertInternalType('string', $filter->filter(acos(1.01)));
        $this->assertEquals('NaN', $filter->filter(acos(1.01)));
        // Infinity values
        $this->assertInternalType('string', $filter->filter(INF));
        $this->assertEquals('∞ €', $filter->filter(INF));
    }
}
