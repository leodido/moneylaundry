<?php
/**
 * MoneyLaundry
 *
 * @link        https://github.com/leodido/moneylaundry
 * @copyright   Copyright (c) 2015, Leo Di Donato
 * @license     http://opensource.org/licenses/MIT      MIT license
 */
namespace MoneyLaundryUnitTest\Validator;

use MoneyLaundry\Filter\Uncurrency;
use MoneyLaundry\Validator\Currency as CurrencyValidator;
use MoneyLaundryTestAsset\Combin;
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
            Uncurrency::DEFAULT_SCALE_CORRECTNESS,
            $validator->getScaleCorrectness()
        );
        $this->assertEquals(
            Uncurrency::DEFAULT_CURRENCY_CORRECTNESS,
            $validator->getCurrencyCorrectness()
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
            'currency_code' => 'EUR',
            'scale_correctness' => false,
            'currency_correctness' => false,
            'negative_allowed' => false
        ]);
        $this->assertFalse($validator->getScaleCorrectness());
        $this->assertFalse($validator->getCurrencyCorrectness());
        $this->assertFalse($validator->isNegativeAllowed());

        // Testing traversable
        $traversableOpts = new ArrayObject([
                'locale' => 'it_IT',
                'currency_code' => 'EUR',
                'scale_correctness' => true,
                'currency_correctness' => false,
                'negative_allowed' => true
        ]);
        $validator = new CurrencyValidator();
        $validator->setOptions($traversableOpts);
        $this->assertEquals('it_IT', $validator->getLocale());
        $this->assertTrue($validator->getScaleCorrectness());
        $this->assertFalse($validator->getCurrencyCorrectness());
        $this->assertTrue($validator->isNegativeAllowed());
    }

    public function testCurrencyCodeOption()
    {
        $v = new CurrencyValidator(['locale' => 'it_IT']);
        $this->assertNull($v->getCurrencyCode());
        $v->isValid('1.234,61 €');
        $formatter = \NumberFormatter::create('it_IT', \NumberFormatter::CURRENCY);
        $this->assertEquals(
            $v->getCurrencyCode(),
            $formatter->getTextAttribute(\NumberFormatter::CURRENCY_CODE)
        );

        $v = new CurrencyValidator(['locale' => 'it_IT']);
        $v->setCurrencyCode('USD');
        $this->assertEquals('USD', $v->getCurrencyCode());
    }

    public function testLocaleOption()
    {
        $v = new CurrencyValidator;
        $this->assertInstanceOf('MoneyLaundry\Validator\Currency', $v->setLocale('it_IT'));
        $this->assertEquals($v->getLocale(), 'it_IT');
    }

    public function testCurrencyCorrectnessOption()
    {
        $v = new CurrencyValidator;
        $this->assertInstanceOf('MoneyLaundry\Validator\Currency', $v->setCurrencyCorrectness(false));
        $this->assertFalse($v->getCurrencyCorrectness());
    }

    public function testScaleCorrectnessOption()
    {
        $v = new CurrencyValidator;
        $this->assertInstanceOf('MoneyLaundry\Validator\Currency', $v->setScaleCorrectness(false));
        $this->assertFalse($v->getScaleCorrectness());
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
     * @param $currencyCode
     * @param $options
     * @dataProvider validationProvider
     */
    public function testValidation($value, $expected, $locale, $currencyCode, array $options)
    {
        $v = new CurrencyValidator();
        $v->setLocale($locale);
        $v->setCurrencyCode($currencyCode);
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
            [123, false, 'it_IT', 'EUR', $opts['000']],
            // exact number of fraction digits required but not provided
            ['1.234,619', false, 'it_IT', 'EUR', $opts['100']],
            ['1.234,619', false, 'it_IT', 'EUR', $opts['101']],
            ['1.234,619 €', false, 'it_IT', 'EUR', $opts['111']],
            ['1.234,619 €', false, 'it_IT', 'EUR', $opts['110']],
            // currency symbol required but not provided
            ['1.234,61', false, 'it_IT', 'EUR', $opts['111']],
            ['1.234,61', false, 'it_IT', 'EUR', $opts['110']],
            ['1.234,61', false, 'it_IT', 'EUR', $opts['010']],
            ['1.234,61', false, 'it_IT', 'EUR', $opts['011']],
            ['1.234,61 EUR', false, 'it_IT', 'EUR', $opts['110']],
            // negative currency amount NOT allowed but provided
            ['-1.234,61', false, 'it_IT', 'EUR', $opts['000']],
            ['-1.234,61 €', false, 'it_IT', 'EUR', $opts['010']],
            ['-1.234,61 €', false, 'it_IT', 'EUR', $opts['110']],
            ['-1.234,61', false, 'it_IT', 'EUR', $opts['100']],
            // exact number of fraction digits NOT required
            ['1.234,619', true, 'it_IT', 'EUR', $opts['000']],
            ['1.234,619', true, 'it_IT', 'EUR', $opts['001']],
            ['1.234,619 €', true, 'it_IT', 'EUR', $opts['011']],
            ['1.234,619 €', true, 'it_IT', 'EUR', $opts['010']],
            // currency symbol NOT required
            ['1.234,61', true, 'it_IT', 'EUR', $opts['101']],
            ['1.234,61', true, 'it_IT', 'EUR', $opts['100']],
            ['1.234,61', true, 'it_IT', 'EUR', $opts['000']],
            ['1.234,61', true, 'it_IT', 'EUR', $opts['001']],
            ['1.234,61 EUR', true, 'it_IT', 'EUR', $opts['001']],
            // negative currency amount allowed
            ['-1.234,61', true, 'it_IT', 'EUR', $opts['001']],
            ['-1.234,61 €', true, 'it_IT', 'EUR', $opts['011']],
            ['-1.234,61 €', true, 'it_IT', 'EUR', $opts['111']],
            ['-1.234,61', true, 'it_IT', 'EUR', $opts['101']],
            // strict validation
            ['1.234,61 €', true, 'it_IT', 'EUR', $opts['110']],
            ['1.234 €', false, 'it_IT', 'EUR', $opts['110']], // because there isn't digital places
            ['-1.234.10 €', false, 'it_IT', 'EUR', $opts['110']], // because negative amount
            ['1.234,61', false, 'it_IT', 'EUR', $opts['110']], // because no currency symbol
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
                ['scale_correctness', 'currency_correctness', 'negative_allowed'],
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
