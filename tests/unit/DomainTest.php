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
            INF,
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
        ];


        $currencies = [
            'EUR',
//            'GBP',
//            'USD'
        ];

        // All available locales
        $locales = ['vi_VN']; // \ResourceBundle::getLocales('');

//        $data = [
//            // SPECIAL CASEs
//            //locale, currency code, value, is a valid domain value?
//            ['it_IT', 'EUR', 0.123456789123456789, false], // EUR has only 2 fraction digits
//        ];

        foreach ($locales as $locale) {
            foreach ($currencies as $currencyCode) {
                foreach ($validDomainValues as $value) {
                    $data[] = [$locale, $currencyCode, $value, true];
                }
//                foreach ($invalidDomainValues as $value) {
//                    $data[] = [$locale, $currencyCode, $value, false];
//                }
            }
        }


        return $data;
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
        $currency = new Currency($locale, $currencyCode);
        $uncurrency = new Uncurrency($locale, $currencyCode);

        var_dump($locale);
        var_dump($currency->filter($value));

        $resultValue = $uncurrency->filter($currency->filter($value));

        if ($isValid) {
            $this->assertEquals($value, $resultValue);
        } else {
            $this->assertNotEquals($value, $resultValue);
        }
    }
}