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
use MoneyLaundry\Filter\Uncurrency;

/**
 * Class DomainTest
 */
class DomainTest extends AbstractTest
{

    public function getStrictDomainDataProvider()
    {

        ini_set('memory_limit', '6G');

        // All available locales
        $locales = \ResourceBundle::getLocales('');  // FIXME: debug

        // All available currencies
        $currencies = [];
        $currencyResources = \ResourceBundle::create('en', 'ICUDATA-curr', true);
        $currencySymbols = $currencyResources->get('Currencies');
        foreach ($currencySymbols as $currencyCode => $bundle) {
            $currencies[] = $currencyCode;
        }

//        $currencies = ['KMF']; // FIXME: debug

        $data = [];

        foreach ($locales as $locale) {
            foreach ($currencies as $currencyCode) {
                $data[] = [$locale, $currencyCode];
            }
        }

        return $data;
    }



    protected function explodeTestData($locale, $currencyCode)
    {
        // valid values for all currencies
        $validDomainValues = [
            (float) -10,
            (float) 0,
            (float) 10,
        ];

        // invalid values for all currencies
        $invalidDomainValues = [
            -1, // int not allowed
            0, // int not allowed
            1, // int not allowed
            null,
            false,
            true,
            "",
            "123",
            "foo",
            [],
            ['foo' => 'bar'],
            new \stdClass,
            NAN,
            INF,
            -INF,
        ];

        $tmpValid   = $validDomainValues;
        $tmpInvalid = $invalidDomainValues;

        // Build fraction digits test data
        $formatter = new \NumberFormatter($locale, \NumberFormatter::CURRENCY);
        $formatter->setTextAttribute($formatter::CURRENCY_CODE, $currencyCode);
        $fractionDigits = $formatter->getAttribute(\NumberFormatter::FRACTION_DIGITS);
        $tmpValid[]   = (float) (1 + pow(10, -$fractionDigits));
        $tmpValid[] = (float) (1 + pow(10, -($fractionDigits - 1)));
        $tmpInvalid[] = (float) (1 + pow(10, -($fractionDigits + 1)));

        $return = [];

        foreach ($tmpValid as $value) {
            $return[] = [$value, true];
        }

        foreach ($tmpInvalid as $value) {
            $return[] = [$value, false];
        }

        return $return;
    }

    public function getStrictCodomainDataProvider()
    {
        ini_set('memory_limit', '1G');
        return [
            // locale, currency code, value, is a valid domain value?
            ['en_GB', 'GBP', '£11.33', true],
            ['en_GB', 'GBP', '£11.00', true],
            ['it_IT', 'EUR', "11,33 €", true],
            ['en_GB', 'GBP', '£1E3', false],
            ['en_GB', 'GBP', '£11', false], // GBP has only 2 fraction digits
            ['en_GB', 'GBP', '£11.333', false], // GBP has only 2 fraction digits
            ['it_IT', 'EUR', "€ 11,33", false], // Wrong currency position
            ['en_US', 'GBP', 'GBP11.33', false],
            ['it_IT', 'GBP', "11,33 GBP", false],
        ];
    }


    protected function assertSameOrNaN($value, $expected)
    {
        if (is_float($value) && is_nan($value)) { // NaN != NaN
            return $this->assertTrue(is_nan($expected));
        }
        return $this->assertSame($value, $expected);
    }

    protected function assertNotSameNorNaN($value, $expected)
    {
        if (is_float($value) && is_nan($value)) { // NaN != NaN
            return $this->assertFalse(is_nan($expected));
        }
        return $this->assertNotSame($value, $expected);
    }

    protected function assertInDomain($locale, $currencyCode, $value)
    {
        $this->assertInternalType('double', $value);

        $formatter = new \NumberFormatter($locale, \NumberFormatter::CURRENCY);
        $formatter->setTextAttribute(\NumberFormatter::CURRENCY_CODE, $currencyCode);

        $codomainValue = $formatter->formatCurrency($value, $currencyCode);
        $testValue     = $formatter->parse($codomainValue, \NumberFormatter::TYPE_DOUBLE);

        $this->assertSame($value, $testValue);
    }

    protected function assertInCodomain($locale, $currencyCode, $value)
    {
        $this->assertInternalType('string', $value);

        $formatter = new \NumberFormatter($locale, \NumberFormatter::CURRENCY);
        $formatter->setTextAttribute(\NumberFormatter::CURRENCY_CODE, $currencyCode);

        $domainValue = $formatter->parse($value, \NumberFormatter::TYPE_DOUBLE);
        $testValue   = $formatter->formatCurrency($domainValue, $currencyCode);

        $this->assertSame($value, $testValue);
    }

    /**
     * @param string $locale
     * @param string $currencyCode
     *
     * @dataProvider getStrictDomainDataProvider
     */
    public function testStrictDomain($locale, $currencyCode)
    {
        $currency = new Currency($locale, $currencyCode);
        $uncurrency = new Uncurrency($locale, $currencyCode);

        $data = $this->explodeTestData($locale, $currencyCode);

        foreach ($data as $testValues) {

            list($value, $isValid) = $testValues;

            $codomainValue = $currency->filter($value);
            $domainValue = $uncurrency->filter($codomainValue);

//            if (is_float($value) && $value == -10) {
//                echo '--------------'. PHP_EOL;
//                var_dump($testValues);
//                var_dump($codomainValue);
//                var_dump($domainValue);
//            }

            $this->assertSameOrNaN($value, $domainValue);

            if ($isValid) {
                $this->assertNotSameNorNaN($value, $codomainValue);
                $this->assertInCodomain($locale, $currencyCode, $codomainValue);
                $this->assertInDomain($locale, $currencyCode, $domainValue);
            } else {
                $this->assertSameOrNaN($value, $codomainValue);
            }


        }
    }

    /**
     * @param string $locale
     * @param string $currencyCode
     * @param string $value
     * @param bool $isValid
     *
     * @dataProvider getStrictCodomainDataProvider
     */
    public function testStrictCodomain($locale, $currencyCode, $value, $isValid = true)
    {
        $currency = new Currency($locale, $currencyCode);
        $uncurrency = new Uncurrency($locale, $currencyCode);

        $domainValue = $uncurrency->filter($value);
        $codomainValue = $currency->filter($domainValue);
//        echo PHP_EOL . PHP_EOL;
//        var_dump($value);
//        var_dump($domainValue);
//        var_dump($codomainValue);
//        echo PHP_EOL;

        $this->assertSameOrNaN($value, $codomainValue);

        if ($isValid) {
            $this->assertNotSameNorNaN($value, $domainValue);
            $this->assertInCodomain($locale, $currencyCode, $codomainValue);
            $this->assertInDomain($locale, $currencyCode, $domainValue);
        } else {
            $this->assertSameOrNaN($value, $domainValue);
        }
    }
}
