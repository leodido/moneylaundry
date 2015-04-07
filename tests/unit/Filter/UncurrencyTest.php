<?php
/**
 * MoneyLaundry
 *
 * @link        https://github.com/leodido/moneylaundry
 * @copyright   Copyright (c) 2015, Leo Di Donato
 * @license     http://opensource.org/licenses/MIT      MIT license
 */
namespace MoneyLaundryUnitTest\Filter;

use MoneyLaundry\Filter\Uncurrency;
use MoneyLaundryUnitTest\AbstractTest;
use Zend\Stdlib\StringUtils;

/**
 * Class UncurrencyTest
 */
class UncurrencyTest extends AbstractTest
{
    public function testCtor()
    {
        // FIXME: assertions related to currency code
        $filter = new Uncurrency();
        $this->assertEquals('en_US', $filter->getLocale());
        $this->assertEquals(Uncurrency::DEFAULT_SCALE_CORRECTNESS, $filter->getScaleCorrectness());
        $this->assertEquals(Uncurrency::DEFAULT_CURRENCY_OBLIGATORINESS, $filter->getCurrencyObligatoriness());

        $filter = new Uncurrency(null);
        $this->assertEquals('en_US', $filter->getLocale());
        $this->assertEquals(Uncurrency::DEFAULT_SCALE_CORRECTNESS, $filter->getScaleCorrectness());
        $this->assertEquals(Uncurrency::DEFAULT_CURRENCY_OBLIGATORINESS, $filter->getCurrencyObligatoriness());

        $filter = new Uncurrency('it_IT');
        $this->assertEquals('it_IT', $filter->getLocale());
        $this->assertEquals(Uncurrency::DEFAULT_SCALE_CORRECTNESS, $filter->getScaleCorrectness());
        $this->assertEquals(Uncurrency::DEFAULT_CURRENCY_OBLIGATORINESS, $filter->getCurrencyObligatoriness());

        $filter = new Uncurrency('it_IT', null, false, false);
        $this->assertEquals('it_IT', $filter->getLocale());
        $this->assertFalse($filter->getScaleCorrectness());
        $this->assertFalse($filter->getCurrencyObligatoriness());

        $filter = new Uncurrency('it_IT', null, true, false);
        $this->assertEquals('it_IT', $filter->getLocale());
        $this->assertTrue($filter->getScaleCorrectness());
        $this->assertFalse($filter->getCurrencyObligatoriness());

        $filter = new Uncurrency('it_IT', null, false, true);
        $this->assertEquals('it_IT', $filter->getLocale());
        $this->assertFalse($filter->getScaleCorrectness());
        $this->assertTrue($filter->getCurrencyObligatoriness());

        $filter = new Uncurrency('it_IT', null, true, true);
        $this->assertEquals('it_IT', $filter->getLocale());
        $this->assertTrue($filter->getScaleCorrectness());
        $this->assertTrue($filter->getCurrencyObligatoriness());

        $filter = new Uncurrency([
            'locale' => 'it_IT',
            'scale_correctness' => false,
            'currency_obligatoriness' => false
        ]);
        $this->assertEquals('it_IT', $filter->getLocale());
        $this->assertFalse($filter->getScaleCorrectness());
        $this->assertFalse($filter->getCurrencyObligatoriness());
    }

    public function testSetFormatter()
    {
        $filter = new Uncurrency('it_IT');
        $formatter = new \NumberFormatter($filter->getLocale(), \NumberFormatter::CURRENCY);
        $filter->setFormatter($formatter);
        $this->assertEquals($filter->getFormatter(), $formatter);
    }

    public function testGetFormatter()
    {
        $filter = new Uncurrency();
        $this->assertInstanceOf('NumberFormatter', $filter->getFormatter());
    }

    public function testLocaleOption()
    {
        $filter = new Uncurrency;
        $this->assertInstanceOf('MoneyLaundry\Filter\Uncurrency', $filter->setLocale('it_IT'));
        $this->assertEquals($filter->getLocale(), 'it_IT');
    }

    public function testCurrencyCodeOption()
    {
        // FIXME: to be done
    }

    public function testCurrencyObligatorinessOption()
    {
        $filter = new Uncurrency;
        $this->assertInstanceOf('MoneyLaundry\Filter\Uncurrency', $filter->setCurrencyObligatoriness(false));
        $this->assertFalse($filter->getCurrencyObligatoriness());
    }

    public function testScaleCorrectnessOption()
    {
        $filter = new Uncurrency;
        $this->assertInstanceOf('MoneyLaundry\Filter\Uncurrency', $filter->setScaleCorrectness(false));
        $this->assertFalse($filter->getScaleCorrectness());
    }

    /**
     * @expectedException \Zend\I18n\Exception\RuntimeException
     */
    public function testGetSymbolsShouldThrowRuntimeExceptionWhenFilterHasNotBeenInitialized()
    {
        $filter = new Uncurrency;
        $filter->getSymbols();
    }

    public function testGetSymbolsAndRegexComponents()
    {
        $filter = new Uncurrency('it_IT');
        $filter->filter('1.234,61 €');
        $this->assertInternalType('array', $filter->getSymbols());
        $this->assertInternalType('array', $filter->getRegexComponents());

        $formatter = $filter->getFormatter();
        $this->assertEquals(
            $formatter->getAttribute(\NumberFormatter::FRACTION_DIGITS),
            $filter->getSymbol(Uncurrency::FRACTION_DIGITS)
        );
        $this->assertEquals(
            $formatter->getSymbol(\NumberFormatter::CURRENCY_SYMBOL),
            $filter->getSymbol(Uncurrency::CURRENCY_SYMBOL)
        );
        $this->assertEquals(
            $formatter->getSymbol(\NumberFormatter::MONETARY_GROUPING_SEPARATOR_SYMBOL),
            $filter->getSymbol(Uncurrency::GROUP_SEPARATOR_SYMBOL)
        );
        $this->assertEquals(
            $formatter->getSymbol(\NumberFormatter::MONETARY_SEPARATOR_SYMBOL),
            $filter->getSymbol(Uncurrency::SEPARATOR_SYMBOL)
        );
        $this->assertEquals(
            $formatter->getSymbol(\NumberFormatter::INFINITY_SYMBOL),
            $filter->getSymbol(Uncurrency::INFINITY_SYMBOL)
        );
        $this->assertEquals(
            $formatter->getSymbol(\NumberFormatter::NAN_SYMBOL),
            $filter->getSymbol(Uncurrency::NAN_SYMBOL)
        );

        if (StringUtils::hasPcreUnicodeSupport()) {
            $this->assertEquals(
                'u',
                $filter->getRegexComponent(Uncurrency::REGEX_FLAGS)
            );
            $this->assertEquals(
                '\p{N}',
                $filter->getRegexComponent(Uncurrency::REGEX_NUMBERS)
            );
        } else {
            $this->assertEquals(
                '',
                $filter->getRegexComponent(Uncurrency::REGEX_FLAGS)
            );
            $this->assertEquals(
                '0-9',
                $filter->getRegexComponent(Uncurrency::REGEX_NUMBERS)
            );
        }
    }

    /**
     * @expectedException \Zend\I18n\Exception\InvalidArgumentException
     */
    public function testGetSymbolsShouldThrowInvalidArgumentExceptionWhenSymbolDoesNotExists()
    {
        $filter = new Uncurrency('it_IT');
        $filter->filter('1.234,61 €');
        $filter->getSymbol(-1);
    }

    /**
     * @expectedException \Zend\I18n\Exception\InvalidArgumentException
     */
    public function testGetRegexComponentsShouldThrowInvalidArgumentExceptionWhenRegexComponentDoesNotExists()
    {
        $filter = new Uncurrency('it_IT');
        $filter->filter('1.234,61 €');
        $filter->getRegexComponent(-1);
    }

    // FIXME: move this in the AbstractFilterTest
//    public function testSetLocaleShouldVoidFormatter()
//    {
//        $filter = new Uncurrency('it_IT');
//        $reflector = new \ReflectionClass($filter);
//        $property = $reflector->getProperty('formatter');
//        $property->setAccessible(true);
//
//        $filter->getFormatter();
//
//        $this->assertNotNull($property->getValue($filter));
//
//        $filter->setLocale('en_US');
//
//        $this->assertNull($property->getValue($filter));
//
//        $property->setAccessible(false);
//    }

    public function testChangeFormatterOnFly()
    {
        $filter = new Uncurrency('it_IT');
        $custom = \NumberFormatter::create('en_US', \NumberFormatter::CURRENCY);
        $filter->setFormatter($custom);
        $this->assertEquals('en_US', $filter->getLocale());
    }

    public function testChangeLocaleOnFly()
    {
        // Store symbols and regex components from it_IT
        $itLocale = 'it_IT';
        $itFilter = new Uncurrency($itLocale);
        $itFormatter = $itFilter->getFormatter();
        $itOpts = $itFilter->getOptions();
        $itSymbols = $itFilter->getSymbols();
        $itRegexComponents = $itFilter->getRegexComponents();
        // Store symbols and regex components from ar_AE
        $aeLocale = 'ar_AE';
        $aeFilter = new Uncurrency($aeLocale);
        $aeFormatter = $aeFilter->getFormatter();
        $aeOpts = $aeFilter->getOptions();
        $aeSymbols = $aeFilter->getSymbols();
        $aeRegexComponents = $aeFilter->getRegexComponents();

        // We instantiate a single filter
        $filter = new Uncurrency();
        $filter->setLocale('it_IT');
        $this->assertEquals($itLocale, $filter->getLocale());
        $this->assertEquals($itFormatter, $filter->getFormatter());
        $this->assertEquals($itSymbols, $filter->getSymbols());
        $this->assertEquals($itRegexComponents, $filter->getRegexComponents());
        $this->assertEquals($itOpts, $filter->getOptions());
        // Now we change its locale on fly
        $filter->setLocale('ar_AE');
        $this->assertEquals($aeLocale, $filter->getLocale());
        $this->assertEquals($aeFormatter, $filter->getFormatter());
        $this->assertEquals($aeSymbols, $filter->getSymbols());
        $this->assertEquals($aeRegexComponents, $filter->getRegexComponents());
        $this->assertEquals($aeOpts, $filter->getOptions());
    }

    public function testTryingToFilterNotAllowedInputsShouldSilentlyReturnThem()
    {
        $filter = new Uncurrency('it_IT');
        $this->assertEquals([], $filter->filter([]));
        $this->assertEquals(2, $filter->filter(2));
        $this->assertEquals(3.14, $filter->filter(3.14));
        $this->assertEquals(true, $filter->filter(true));
    }

    public function testFiltersInfinityValues()
    {
        $filter = new Uncurrency('ar_AE', null, false, true);
        $formatter = $filter->getFormatter();

        $this->assertEquals(INF, $filter->filter($formatter->format(INF)));
        $this->assertEquals('∞', $filter->filter('∞'));

        $filter->setCurrencyObligatoriness(false);

        $this->assertEquals(INF, $filter->filter($formatter->format(INF)));
        $this->assertEquals(INF, $filter->filter('∞'));

        $filter = new Uncurrency('ru_RU', null, false, true);
        $formatter = $filter->getFormatter();

        $this->assertEquals(INF, $filter->filter($formatter->format(INF)));
        $this->assertEquals('∞', $filter->filter('∞'));

        $filter->setCurrencyObligatoriness(false);

        $this->assertEquals(INF, $filter->filter($formatter->format(INF)));
        $this->assertEquals(INF, $filter->filter('∞'));

        $filter = new Uncurrency('bn_IN', null, false, true);
        $formatter = $filter->getFormatter();

        $this->assertEquals(INF, $filter->filter($formatter->format(INF)));
        $this->assertEquals('∞', $filter->filter('∞'));

        $filter->setCurrencyObligatoriness(false);

        $this->assertEquals(INF, $filter->filter($formatter->format(INF)));
        $this->assertEquals(INF, $filter->filter('∞'));
    }

    public function testFiltersNaNValues()
    {

        $filter = new Uncurrency('ar_AE');
        $formatter = $filter->getFormatter();
        $this->assertTrue(is_nan($filter->filter($formatter->format(NAN)))); // "ليس رقم"
        $this->assertTrue(is_nan($filter->filter($formatter->format(acos(1.01))))); // "ليس رقم"

        $filter->setLocale('bn_IN');
        $formatter = $filter->getFormatter();
        $this->assertTrue(is_nan($filter->filter($formatter->format(NAN)))); // "সংখ্যা না"
        $this->assertTrue(is_nan($filter->filter($formatter->format(acos(1.01))))); // "সংখ্যা না"

        $filter->setLocale('it_IT');
        $formatter = $filter->getFormatter();
        $this->assertTrue(is_nan($filter->filter($formatter->format(NAN)))); // "NaN"
        $this->assertTrue(is_nan($filter->filter($formatter->format(acos(1.01))))); // "NaN
        $this->assertEquals("NaN\xC2\xA0\xE2\x82\xAC", $filter->filter("NaN\xC2\xA0\xE2\x82\xAC"));

        $filter->setLocale('ru_RU');
        $formatter = $filter->getFormatter();
        $this->assertTrue(is_nan($filter->filter($formatter->format(NAN)))); // "не число"
        $this->assertTrue(is_nan($filter->filter($formatter->format(acos(1.01))))); // "не число"
    }

    public function testFilter()
    {
        // (1) italian - italy, (2) correct number of decimal places not mandatory, (3) currency not mandatory
        $filter = new Uncurrency('it_IT', null, false, false);
        $formatter = $filter->getFormatter();

        // Allowed
        $this->assertSame((float) 123, $filter->filter($formatter->format((float)123))); // 1.234,61 €'
        $this->assertSame(1234.61, $filter->filter($formatter->format(1234.61))); // 1.234,61 €
        $this->assertSame(1234.61, $filter->filter('1.234,61€'));
        $this->assertSame(1234.61, $filter->filter('1.234,61'));
        $this->assertSame(1234.61, $filter->filter('1234,61'));
        $this->assertSame(1234.61, $filter->filter('1234,61 EURO'));
        $this->assertEquals(1234.619, $filter->filter('1234,619'));
        $this->assertEquals(1234.619, $filter->filter('1234,619 €'));
        $this->assertSame(-0.01, $filter->filter('-0,01€'));
        $this->assertSame(-0.5, $filter->filter('-0,5€'));

        if (version_compare($GLOBALS['INTL_ICU_VERSION'], '4.8.1.1') > 0) {
            $this->assertSame(1234.61, $filter->filter('1234,61 EUR')); // Because of (3)
        }

        // Not allowed
        $this->assertSame('1234,61 EUROOO', $filter->filter('1234,61 EUROOO'));
        $this->assertSame('1E-2 €', $filter->filter('1E-2 €'));

        // (2) correct number of decimal places required/mandatory
        $filter->setScaleCorrectness(true);

        // No more allowed
        $this->assertSame('1.234,619 €', $filter->filter('1.234,619 €'));
        $this->assertSame('1.234,619', $filter->filter('1.234,619'));
        $this->assertSame('1.234,1 €', $filter->filter('1.234,1 €'));

        // Allowed
        $this->assertSame(1234.10, $filter->filter('1.234,10 €'));

        // (3) currency symbol (and correct formatting) required
        $filter->setCurrencyObligatoriness(true);

        // No more allowed
        $this->assertSame('1234,61', $filter->filter('1234,61'));
        $this->assertSame('1234,61€', $filter->filter('1234,61€'));

        // Allowed
        $this->assertSame(1234.61, $filter->filter($formatter->format(1234.61))); // 1234,61 €
        $this->assertSame(-0.01, $filter->filter($formatter->format(-0.01))); // -0,01 €

        // bengali - bangladesh, (2) correct number of decimal places not mandatory, (3) currency not mandatory
        $filter = new Uncurrency('bn_BD', null, false, false);
        $formatter = $filter->getFormatter();

        // Allowed
        $this->assertSame((float) 0, $filter->filter($formatter->format((float) 0))); // '০.০০৳' (w/ currency symbol)
        $this->assertSame((float) 0, $filter->filter('০.০'));
        $this->assertSame(0.01, $filter->filter($formatter->format(0.01))); // '০.০১৳' (w/ currency simbol)
        $this->assertSame(0.01, $filter->filter('০.০১০'));
        $this->assertSame((float) 0, $filter->filter('(০.০)'));
        $this->assertSame(-0.01, $filter->filter($formatter->format(-0.01))); // (০.০১৳) (w/ currency symbol)
        $this->assertSame(-0.01, $filter->filter('(০.০১)'));
    }
}
