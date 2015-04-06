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

        $validDomainValues = [
           -4.5,
           -1,
           0,
           1,
           2.3,
           22.11,
        ];

        $invalidDomainValues = [
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
        ];


        $currencies = [
            'EUR',
            'GBP',
            'USD',
//             'VND' // Not working due to fraction digits
        ];

        // All available locales
        $locales   = \ResourceBundle::getLocales('');

        $data = [

            // SPECIAL CASEs
            //locale, currency code, value, is a valid domain value?
//             ['it_IT', 'EUR', 0.123456789123456789, false], // EUR has only 2 fraction digits
        ];

        foreach ($locales as $locale) {
            foreach ($currencies as $currencyCode) {
                foreach ($validDomainValues as $value) {
                    $data[] = [$locale, $currencyCode, $value, true];
                }
                foreach ($invalidDomainValues as $value) {
                    $data[] = [$locale, $currencyCode, $value, false];
                }
            }
        }


        return $data;
    }


    protected function _assertSame($value, $expected)
    {
        if (is_float($value) && is_nan($value)) { // NaN != NaN
            return $this->assertTrue(is_nan($expected));
        }
        return $this->assertSame($value, $expected);
    }

    protected function _assertNotSame($value, $expected)
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
     * @param string $isValid
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

        $this->_assertSame($value, $resultValue);

        if ($isValid) {
            $this->_assertNotSame($value, $codomainValue);
        } else {
            $this->_assertSame($value, $codomainValue);
        }
    }
}
