<?php
/**
 * MoneyLaundry
 *
 * @link        https://github.com/leodido/moneylaundry
 * @copyright   Copyright (c) 2015, Leo Di Donato
 * @license     http://opensource.org/licenses/MIT      MIT license
 */
namespace MoneyLaundryTest\Validator;

use MoneyLaundry\Filter\Uncurrency;
use MoneyLaundry\Validator\Currency as CurrencyValidator;
use MoneyLaundryTest\Integration\Combin;
use Zend\Stdlib\ArrayObject;

/**
 * Class CurrencyTest
 * @group validators
 */
class CurrencyTest extends \PHPUnit_Framework_TestCase
{
    protected $defaultLocale;

    public function setUp()
    {
        if (!extension_loaded('intl')) {
            $this->markTestSkipped('The intl extension is not installed/enabled');
        }
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

    public function testConstructor()
    {
        // Testing defaults
        $validator = new CurrencyValidator;
        $this->assertEquals('en_US', $validator->getLocale());
        $this->assertEquals(
            Uncurrency::DEFAULT_FRACTION_DIGITS_OBLIGATORINESS,
            $validator->isFractionDigitsMandatory()
        );
        $this->assertEquals(
            Uncurrency::DEFAULT_CURRENCY_SYMBOL_OBLIGATORINESS,
            $validator->isCurrencySymbolMandatory()
        );
        $this->assertEquals(CurrencyValidator::DEFAULT_NEGATIVE_ALLOWED, $validator->isNegativeAllowed());

        // Testing application locale
        \Locale::setDefault('de_DE');
        $validator = new CurrencyValidator;
        $this->assertEquals('de_DE', $validator->getLocale());

        // Testing locale
        $validator = new CurrencyValidator(['locale' => 'it_IT']);
        $this->assertEquals('it_IT', $validator->getLocale());

        // Testing options
        $validator = new CurrencyValidator([
            'locale' => 'it_IT',
            'fraction_digits_mandatory' => false,
            'currency_symbol_mandatory' => false,
            'negative_allowed' => false
        ]);
        $this->assertFalse($validator->isFractionDigitsMandatory());
        $this->assertFalse($validator->isCurrencySymbolMandatory());
        $this->assertFalse($validator->isNegativeAllowed());

        // Testing traversable
        $traversableOpts = new ArrayObject([
                'locale' => 'it_IT',
                'fraction_digits_mandatory' => true,
                'currency_symbol_mandatory' => false,
                'negative_allowed' => true
        ]);
        $validator = new CurrencyValidator();
        $validator->setOptions($traversableOpts);
        $this->assertEquals('it_IT', $validator->getLocale());
        $this->assertTrue($validator->isFractionDigitsMandatory());
        $this->assertFalse($validator->isCurrencySymbolMandatory());
        $this->assertTrue($validator->isNegativeAllowed());
    }

    public function testSetLocaleOption()
    {
        $v = new CurrencyValidator;
        $this->assertInstanceOf('MoneyLaundry\Validator\Currency', $v->setLocale('it_IT'));
        $this->assertEquals($v->getLocale(), 'it_IT');
    }

    public function testCurrencySymbolMandatoryOption()
    {
        $v = new CurrencyValidator;
        $this->assertInstanceOf('MoneyLaundry\Validator\Currency', $v->setCurrencySymbolMandatory(false));
        $this->assertFalse($v->isCurrencySymbolMandatory());
    }

    public function testFractionDigitsMandatoryOption()
    {
        $v = new CurrencyValidator;
        $this->assertInstanceOf('MoneyLaundry\Validator\Currency', $v->setFractionDigitsMandatory(false));
        $this->assertFalse($v->isFractionDigitsMandatory());
    }

    public function testNegativeAllowedOption()
    {
        $v = new CurrencyValidator;
        $this->assertInstanceOf('MoneyLaundry\Validator\Currency', $v->setNegativeAllowed(false));
        $this->assertFalse($v->isNegativeAllowed());
    }

    /**
     * @param $value
     * @param $expected
     * @param $locale
     * @param $options
     * @dataProvider validationProvider
     */
    public function testValidation($value, $expected, $locale, array $options)
    {
        $v = new CurrencyValidator();
        $v->setLocale($locale);
        $v->setOptions($options);
        $this->assertEquals(
            $expected,
            $v->isValid($value),
            sprintf(
                'Failed expecting "%s" being %s w/ options: %s',
                $value,
                $expected ? 'TRUE' : 'FALSE',
                $this->printArray(['locale' => $locale] + $options)
            )
        );
    }

    /**
     * @return array
     */
    public function validationProvider()
    {
        // fraction digits obligatoriness, currency symbol obligatorines, negative allowed
        $opts = $this->getAllAvailableOpts();

        //
        $data = [
            // not string input
            [123, false, 'it_IT', $opts['000']],
            // exact number of fraction digits required but not provided
            ['1.234,619', false, 'it_IT', $opts['100']],
            ['1.234,619', false, 'it_IT', $opts['101']],
            ['1.234,619 €', false, 'it_IT', $opts['111']],
            ['1.234,619 €', false, 'it_IT', $opts['110']],
            // currency symbol required but not provided
            ['1.234,61', false, 'it_IT', $opts['111']],
            ['1.234,61', false, 'it_IT', $opts['110']],
            ['1.234,61', false, 'it_IT', $opts['010']],
            ['1.234,61', false, 'it_IT', $opts['011']],
            // negative currency amount NOT allowed but provided
            ['-1.234,61', false, 'it_IT', $opts['000']],
            ['-1.234,61 €', false, 'it_IT', $opts['010']],
            ['-1.234,61 €', false, 'it_IT', $opts['110']],
            ['-1.234,61', false, 'it_IT', $opts['100']],
            // exact number of fraction digits NOT required
            ['1.234,619', true, 'it_IT', $opts['000']],
            ['1.234,619', true, 'it_IT', $opts['001']],
            ['1.234,619 €', true, 'it_IT', $opts['011']],
            ['1.234,619 €', true, 'it_IT', $opts['010']],
            // currency symbol NOT required
            ['1.234,61', true, 'it_IT', $opts['101']],
            ['1.234,61', true, 'it_IT', $opts['100']],
            ['1.234,61', true, 'it_IT', $opts['000']],
            ['1.234,61', true, 'it_IT', $opts['001']],
            // negative currency amount allowed
            ['-1.234,61', true, 'it_IT', $opts['001']],
            ['-1.234,61 €', true, 'it_IT', $opts['011']],
            ['-1.234,61 €', true, 'it_IT', $opts['111']],
            ['-1.234,61', true, 'it_IT', $opts['101']],
            // strict validation
            ['1.234,61 €', true, 'it_IT', $opts['110']],
            ['1.234,61 EUR', true, 'it_IT', $opts['110']],
            ['1.234 €', false, 'it_IT', $opts['110']], // because there isn't digital places
            ['-1.234.10 €', false, 'it_IT', $opts['110']], // because negative amount
            ['1.234,61', false, 'it_IT', $opts['110']], // because no currency symbol
        ];

        return $data;
    }

    /**
     * @return array
     */
    private function getAllAvailableOpts()
    {
        $combs = Combin::combn([0, 1], 3);
        $opts = [];
        foreach ($combs as $values) {
            $opts[$values] = array_combine(
                ['fraction_digits_mandatory', 'currency_symbol_mandatory', 'negative_allowed'],
                array_map(
                    function ($x) {
                        return (bool)$x;
                    },
                    str_split($values)
                )
            );
        }
        return $opts;
    }

    /**
     * Pretty print an array
     *
     * @param array $array
     * @return string
     */
    private function printArray(array $array)
    {
        return '[' .
        implode(
            ', ',
            array_map(
                function ($k, $v) {
                    $value = is_bool($v) ? ($v ? 'true' : 'false') : $v;
                    return $k . ': ' . $value;
                },
                array_keys($array),
                array_values($array)
            )
        ) .
        ']';
    }
}
