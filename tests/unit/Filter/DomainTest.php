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
        // valid values for all currencies
        $validDomainValues = [
           (float) -10,
           (float) 0,
           (float) 10,
        ];

        $validDomainValuesByCurrencyCode = [
            'EUR' => [
                -4.5,
                2.3,
                22.11,
            ],
            'GBP' => [
                -4.5,
                2.3,
                22.11,
            ],
            'VND' => [
            ]
        ];

        array_walk(
            $validDomainValuesByCurrencyCode,
            function (&$array) use ($validDomainValues) {
                $array = array_merge($array, $validDomainValues);
            }
        );

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

        $invalidDomainValuesByCurrencyCode = [
            'EUR' => [
                0.123456789123456789 // EUR has only 2 fraction digits
            ],
            'GBP' => [
                0.123456789123456789 // GBP has only 2 fraction digits
            ],
            'VND' => [
                0.123456789123456789, // VND has only 2 fraction digits
                0.5 // VND has only 0 fraction digits
            ]
        ];


        array_walk(
            $invalidDomainValuesByCurrencyCode,
            function (&$array) use ($invalidDomainValues) {
                $array = array_merge($array, $invalidDomainValues);
            }
        );

        // All available locales
        $locales = \ResourceBundle::getLocales('');

        $data = [

            // SPECIAL CASEs
            //locale, currency code, value, is a valid domain value?
        ];

        foreach ($locales as $locale) {
            foreach ($validDomainValuesByCurrencyCode as $currencyCode => $values) {
                foreach ($values as $value) {
                    $data[] = [$locale, $currencyCode, $value, true];
                }
            }
            foreach ($invalidDomainValuesByCurrencyCode as $currencyCode => $values) {
                foreach ($values as $value) {
                    $data[] = [$locale, $currencyCode, $value, false];
                }
            }
        }


        return $data;
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

    /**
     * @param string $locale
     * @param string $currencyCode
     * @param string $value
     * @param bool $isValid
     *
     * @dataProvider getStrictDomainDataProvider
     */
    public function testStrictDomain($locale, $currencyCode, $value, $isValid = true)
    {
        ini_set('memory_limit', '1G');
        $currency = new Currency($locale, $currencyCode);
        $uncurrency = new Uncurrency($locale, $currencyCode);

        $codomainValue = $currency->filter($value);
        $resultValue = $uncurrency->filter($codomainValue);

        $this->assertSameOrNaN($value, $resultValue);

        if ($isValid) {
            $this->assertNotSameNorNaN($value, $codomainValue);
        } else {
            $this->assertSameOrNaN($value, $codomainValue);
        }
    }
}
