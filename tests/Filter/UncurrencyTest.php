<?php
/**
 * MoneyLaundry
 *
 * @link        https://github.com/leodido/moneylaundry
 * @copyright   Copyright (c) 2015, Leo Di Donato
 * @license     http://opensource.org/licenses/MIT      MIT license
 */
namespace MoneyLaundryTest\Filter;

use MoneyLaundry\Filter\Uncurrency;
use Zend\Stdlib\StringUtils;

/**
 * Class UncurrencyTest
 * @group filters
 */
class UncurrencyTest extends \PHPUnit_Framework_TestCase
{
    protected $defaultLocale;

    public function setUp()
    {
        if (!extension_loaded('mbstring')) {
            $this->markTestSkipped('The mbstring extension is not installed/enabled');
        }
        //
        $this->defaultLocale = ini_get('intl.default_locale');
        ini_set('intl.default_locale', 'en_US');
    }

    public function tearDown()
    {
        ini_set('intl.default_locale', $this->defaultLocale);
    }

//    public function testCtor()
//    {
//        $filter = new Uncurrency();
//        $this->assertEquals('en_US', $filter->getLocale());
//        $this->assertEquals(Uncurrency::DEFAULT_FRACTION_DIGITS_OBLIGATORINESS, $filter->isFractionDigitsMandatory());
//        $this->assertEquals(Uncurrency::DEFAULT_CURRENCY_SYMBOL_OBLIGATORINESS, $filter->isCurrencySymbolMandatory());
//
//        $filter = new Uncurrency('it_IT');
//        $this->assertEquals('it_IT', $filter->getLocale());
//        $this->assertEquals(Uncurrency::DEFAULT_FRACTION_DIGITS_OBLIGATORINESS, $filter->isFractionDigitsMandatory());
//        $this->assertEquals(Uncurrency::DEFAULT_CURRENCY_SYMBOL_OBLIGATORINESS, $filter->isCurrencySymbolMandatory());
//
//        $filter = new Uncurrency('it_IT', false, false);
//        $this->assertEquals('it_IT', $filter->getLocale());
//        $this->assertFalse($filter->isFractionDigitsMandatory());
//        $this->assertFalse($filter->isCurrencySymbolMandatory());
//
//        $filter = new Uncurrency('it_IT', true, false);
//        $this->assertEquals('it_IT', $filter->getLocale());
//        $this->assertTrue($filter->isFractionDigitsMandatory());
//        $this->assertFalse($filter->isCurrencySymbolMandatory());
//
//        $filter = new Uncurrency('it_IT', false, true);
//        $this->assertEquals('it_IT', $filter->getLocale());
//        $this->assertFalse($filter->isFractionDigitsMandatory());
//        $this->assertTrue($filter->isCurrencySymbolMandatory());
//
//        $filter = new Uncurrency('it_IT', true, true);
//        $this->assertEquals('it_IT', $filter->getLocale());
//        $this->assertTrue($filter->isFractionDigitsMandatory());
//        $this->assertTrue($filter->isCurrencySymbolMandatory());
//
//        $filter = new Uncurrency([
//            'locale' => 'it_IT',
//            'fraction_digits_mandatory' => false,
//            'currency_symbol_mandatory' => false
//        ]);
//        $this->assertEquals('it_IT', $filter->getLocale());
//        $this->assertFalse($filter->isFractionDigitsMandatory());
//        $this->assertFalse($filter->isCurrencySymbolMandatory());
//    }

//    public function testSetFormatter()
//    {
//        $filter = new Uncurrency('it_IT');
//        $formatter = new \NumberFormatter($filter->getLocale(), \NumberFormatter::CURRENCY);
//        $filter->setFormatter($formatter);
//        $this->assertEquals($filter->getFormatter(), $formatter);
//    }
//
//    public function testGetFormatter()
//    {
//        $filter = new Uncurrency();
//        $this->assertInstanceOf('NumberFormatter', $filter->getFormatter());
//    }
//
//    public function testLocaleOption()
//    {
//        $filter = new Uncurrency;
//        $this->assertInstanceOf('MoneyLaundry\Filter\Uncurrency', $filter->setLocale('it_IT'));
//        $this->assertEquals($filter->getLocale(), 'it_IT');
//    }
//
//    public function testCurrencySymbolMandatoryOption()
//    {
//        $filter = new Uncurrency;
//        $this->assertInstanceOf('MoneyLaundry\Filter\Uncurrency', $filter->setCurrencySymbolMandatory(false));
//        $this->assertFalse($filter->isCurrencySymbolMandatory());
//    }
//
//    public function testFractionDigitsMandatoryOption()
//    {
//        $filter = new Uncurrency;
//        $this->assertInstanceOf('MoneyLaundry\Filter\Uncurrency', $filter->setFractionDigitsMandatory(false));
//        $this->assertFalse($filter->isFractionDigitsMandatory());
//    }
//
//    /**
//     * @expectedException \Zend\I18n\Exception\RuntimeException
//     */
//    public function testGetSymbolsShouldThrowRuntimeExceptionWhenFilterHasNotBeenInitialized()
//    {
//        $filter = new Uncurrency;
//        $filter->getSymbols();
//    }
//
//    /**
//     * @expectedException \Zend\I18n\Exception\RuntimeException
//     */
//    public function testGetRegexComponentsShouldThrowRuntimeExceptionWhenFilterHasNotBeenInitialized()
//    {
//        $filter = new Uncurrency;
//        $filter->getRegexComponents();
//    }
//
//    public function testGetSymbolsAndRegexComponents()
//    {
//        $filter = new Uncurrency('it_IT');
//        $filter->filter('1.234,61 €');
//        $this->assertInternalType('array', $filter->getSymbols());
//        $this->assertInternalType('array', $filter->getRegexComponents());
//
//        $formatter = $filter->getFormatter();
//        $this->assertEquals(
//            $formatter->getAttribute(\NumberFormatter::FRACTION_DIGITS),
//            $filter->getSymbol(Uncurrency::FRACTION_DIGITS)
//        );
//        $this->assertEquals(
//            $formatter->getSymbol(\NumberFormatter::CURRENCY_SYMBOL),
//            $filter->getSymbol(Uncurrency::CURRENCY_SYMBOL)
//        );
//        $this->assertEquals(
//            $formatter->getSymbol(\NumberFormatter::MONETARY_GROUPING_SEPARATOR_SYMBOL),
//            $filter->getSymbol(Uncurrency::GROUP_SEPARATOR_SYMBOL)
//        );
//        $this->assertEquals(
//            $formatter->getSymbol(\NumberFormatter::MONETARY_SEPARATOR_SYMBOL),
//            $filter->getSymbol(Uncurrency::SEPARATOR_SYMBOL)
//        );
//
//        if (StringUtils::hasPcreUnicodeSupport()) {
//            $this->assertEquals(
//                'u',
//                $filter->getRegexComponent(Uncurrency::REGEX_FLAGS)
//            );
//            $this->assertEquals(
//                '\p{N}',
//                $filter->getRegexComponent(Uncurrency::REGEX_NUMBERS)
//            );
//        } else {
//            $this->assertEquals(
//                '0-9',
//                $filter->getRegexComponent(Uncurrency::REGEX_NUMBERS)
//            );
//        }
//    }
//
//    /**
//     * @expectedException \Zend\I18n\Exception\InvalidArgumentException
//     */
//    public function testGetSymbolsShouldThrowInvalidArgumentExceptionWhenSymbolDoesNotExists()
//    {
//        $filter = new Uncurrency('it_IT');
//        $filter->filter('1.234,61 €');
//        $filter->getSymbol(-1);
//    }
//
//    /**
//     * @expectedException \Zend\I18n\Exception\InvalidArgumentException
//     */
//    public function testGetRegexComponentsShouldThrowInvalidArgumentExceptionWhenRegexComponentDoesNotExists()
//    {
//        $filter = new Uncurrency('it_IT');
//        $filter->filter('1.234,61 €');
//        $filter->getRegexComponent(-1);
//    }

    public function testFilter()
    {
        // (1) italian - italy, (2) correct number of decimal places not mandatory, (3) currency symbol not mandatory
        $filter = new Uncurrency('it_IT', false, false);
//        $this->assertEquals(123, $filter->filter(123));

//        // Allowed
//        $this->assertEquals(1234.61, $filter->filter('1.234,61 €'));
//        $this->assertEquals(1234.61, $filter->filter('1.234,61€'));
//        $this->assertEquals(1234.61, $filter->filter('1.234,61'));
//        $this->assertEquals(1234.61, $filter->filter('1234,61'));
        $this->assertEquals(1234.61, $filter->filter('1234,61 EUR')); // FIXME: does not work with ICU 4.8.1.1
//        $this->assertEquals(1234.61, $filter->filter('1234,61 EURO'));
//        $this->assertEquals(1234.619, $filter->filter('1234,619'));
//        $this->assertEquals(1234.619, $filter->filter('1234,619 €'));
//        $this->assertEquals(-0.01, $filter->filter('-0,01€'));
//
//        // Not allowed
//        $this->assertEquals('1234,61 EUROOO', $filter->filter('1234,61 EUROOO'));
//        $this->assertEquals('1E-2 €', $filter->filter('1E-2 €'));
//
//        // (2) correct number of decimal places required/mandatory
//        $filter->setFractionDigitsMandatory(true);
//
//        // No more allowed
//        $this->assertEquals('1.234,619 €', $filter->filter('1.234,619 €'));
//        $this->assertEquals('1.234,619', $filter->filter('1.234,619'));
//
//        // (3) currency symbol (and correct formatting) required
//        $filter->setCurrencySymbolMandatory(true);
//
//        // No more allowed
//        $this->assertEquals('1234,61', $filter->filter('1234,61'));
//        $this->assertEquals('1234,61€', $filter->filter('1234,61€'));
//
//        // Allowed
//        $this->assertEquals(1234.61, $filter->filter('1234,61 €'));
//        $this->assertEquals(-0.01, $filter->filter('-0,01 €'));
//
//        // bengali - bangladesh, (2) correct number of decimal places not mandatory, (3) currency symbol not mandatory
//        $filter = new Uncurrency('bn_BD', false, false);
//
//        // Allowed
//        $this->assertEquals(0, $filter->filter('০.০'));
//        $this->assertEquals(0.01, $filter->filter('০.০১০'));
//        $this->assertEquals(0, $filter->filter('(০.০)'));
//        $this->assertEquals(-0.01, $filter->filter('(০.০১)'));
    }
}
