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


    public function testFilterWithDefaults()
    {
        $filter = new Currency('it_IT');

        // Check currency symbol position (for oldier ICU versions)
        $filter->filter(1.1); // Init formatter
        $prefix = $filter->getFormatter()->getTextAttribute(\NumberFormatter::POSITIVE_PREFIX);
        $suffix = $filter->getFormatter()->getTextAttribute(\NumberFormatter::POSITIVE_SUFFIX);


        // Filter float numbers
        $this->assertInternalType('string', $filter->filter(0.01));
        $this->assertEquals($prefix.'0,01'.$suffix, $filter->filter(0.01));
        $this->assertInternalType('string', $filter->filter((float) 100));
        $this->assertEquals($prefix.'100,00'.$suffix, $filter->filter((float) 100));
        $this->assertInternalType('string', $filter->filter(1234.61));
        $this->assertEquals($prefix.'1.234,61'.$suffix, $filter->filter(1234.61));

        // Passthrough
        $this->assertSame(100, $filter->filter(100)); // int test
        $this->assertSame(INF, $filter->filter(INF));
        $this->assertTrue(is_nan($filter->filter(NAN)));
        $this->assertSame(null, $filter->filter(null));
        $this->assertSame(true, $filter->filter(true));
        $this->assertSame("", $filter->filter(""));
        $this->assertSame([], $filter->filter([]));
        $this->assertSame($filter, $filter->filter($filter)); // testing with an object
    }

    public function testFilterWithCustomCurrencyCode()
    {
        $filter = new Currency('it_IT');
        $filter->setCurrencyCode('GBP');

        // Check currency symbol position (for oldier ICU versions)
        $filter->filter(1.1); // Init formatter
        $prefix = $filter->getFormatter()->getTextAttribute(\NumberFormatter::POSITIVE_PREFIX);
        $suffix = $filter->getFormatter()->getTextAttribute(\NumberFormatter::POSITIVE_SUFFIX);

        // Filter usual numbers
        $this->assertInternalType('string', $filter->filter(1.10));
        $this->assertEquals($prefix.'1,10'.$suffix, $filter->filter(1.10));
    }

    public function testFilterInfinityValues()
    {
        $filter = new Currency('it_IT', 'USD');

        $this->assertSame(INF, $filter->filter(INF));
        $this->assertSame(-INF, $filter->filter(-INF));
    }

    public function testFilterNaNValues()
    {
        $filter = new Currency('ar_QA', 'EUR');

        $this->assertTrue(is_nan($filter->filter(NAN)));
    }

    public function testFloatWithImproperScale()
    {
        $filter = new Currency('it_IT');
        $filter->setScaleCorrectness(true);

        $this->assertEquals(123456789.123456789, $filter->filter(123456789.123456789));
    }
}
