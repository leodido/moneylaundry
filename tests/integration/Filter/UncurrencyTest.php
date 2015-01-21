<?php
/**
 * MoneyLaundry
 *
 * @link        https://github.com/leodido/moneylaundry
 * @copyright   Copyright (c) 2015, Leo Di Donato
 * @license     http://opensource.org/licenses/MIT      MIT license
 */
namespace MoneyLaundryIntegrationTest\Filter;

use MoneyLaundryIntegrationTest\AbstractIntegration;
use MoneyLaundry\Filter\Uncurrency as UncurrencyFilter;

/**
 * Class UncurrencyTest
 */
class UncurrencyTest extends AbstractIntegration
{
    /**
     * {@inheritdoc}
     */
    protected static $header = [
        'Locale',
        'Fraction digits check',
        'Currency symbol check',
        'Currency',
        'Uncurrency filter result',
        'Filtered'
    ];

    /**
     * {@inheritdoc}
     */
    public function __construct($name = null, array $data = [], $dataName = '')
    {
        // FIXME: excluded locales do not work
        $this->excludeLocales(['nl_NL', 'si_LK', 'sv_FI', 'sv_SE', 'en_029', 'fa_IR', 'lo_LA']);

        parent::__construct($name, $data, $dataName);
    }

    /**
     * @param $locale
     * @param $fractionDigitsMandatory
     * @param $currencySymbolMandatory
     * @param $expected
     * @param $value
     * @dataProvider valuesProvider
     */
    public function testAllValues($locale, $fractionDigitsMandatory, $currencySymbolMandatory, $expected, $value)
    {
        $filter = new UncurrencyFilter($locale, $fractionDigitsMandatory, $currencySymbolMandatory);
        $this->assertEquals($expected, $filter->filter($value));

        self::writeData(
            [
                'locale' => $locale,
                'fraction_digits' => $fractionDigitsMandatory ? 'Y' : 'N',
                'currency_symbol' => $currencySymbolMandatory ? 'Y' : 'N',
                'currency' => $value,
                'expected' => $expected,
                'filtered' => $value === $expected ? 'N' : 'Y'
            ]
        );
    }

    /**
     * Generate dataset.
     *
     * Formats:
     * - (positive and negative) currency amounts with their own currency symbol
     * - (positive and negative) currency amounts with ISO currency symbol
     * - (positive and negative) numbers (without currency symbol)
     * - (positive and negative) numbers expressed in scientific notation (without currency symbol)
     *
     * @return array
     */
    public function valuesProvider()
    {
        mb_internal_encoding('UTF-8');

        $data = [];
        $values = [0, 0.1, 0.01, 1000, 1234.61, 12345678.90];
        $values = array_unique(
            array_merge(
                $values,
                array_map(
                    function ($i) {
                        return -$i;
                    },
                    $values
                )
            )
        );

        foreach ($this->locales as $locale) {
            $formatter = \NumberFormatter::create($locale, \NumberFormatter::CURRENCY);
            $currencySymbol = $formatter->getSymbol(\NumberFormatter::CURRENCY_SYMBOL);
            $isoSymbol = $formatter->getTextAttribute(\NumberFormatter::CURRENCY_CODE);
            $groupSep = $formatter->getSymbol(\NumberFormatter::MONETARY_GROUPING_SEPARATOR_SYMBOL);
            $numDecimals = $formatter->getAttribute(\NumberFormatter::FRACTION_DIGITS);
            $posPre = $formatter->getTextAttribute(\NumberFormatter::POSITIVE_PREFIX);
            $negPre = $formatter->getTextAttribute(\NumberFormatter::NEGATIVE_PREFIX);
            $posSuf = $formatter->getTextAttribute(\NumberFormatter::POSITIVE_SUFFIX);
            $negSuf = $formatter->getTextAttribute(\NumberFormatter::NEGATIVE_SUFFIX);
            $exponantiatior = \NumberFormatter::create($locale, \NumberFormatter::SCIENTIFIC);

            foreach ($values as $value) {
                // Restore currency symbol
                $formatter->setSymbol(\NumberFormatter::CURRENCY_SYMBOL, $currencySymbol);

                if (is_float($value)) {
                    // If value is float and current currency does not have cents, jump it
                    if ($numDecimals === 0) {
                        continue;
                    }

                    // Create a currency with less decimal places then required (w/ currency symbol)
                    $formatter->setAttribute(\NumberFormatter::FRACTION_DIGITS, $numDecimals - 1);
                    $currency = preg_replace('/^[\xC2\xA0\s]+|[\xC2\xA0\s]+$/u', '', $formatter->format($value));
//                    echo $currency . PHP_EOL;
                    $data[] = [$locale, true, true, $currency, $currency]; // Not filtered
                    $data[] = [$locale, true, false, $currency, $currency]; // Not filtered
                    $data[] = [
                        $locale,
                        false,
                        false,
                        (double) sprintf('%.' . ($numDecimals - 1) . 'f', $value),
                        $currency
                    ]; // Filtered
                    $data[] = [
                        $locale,
                        false,
                        true,
                        (double) sprintf('%.' . ($numDecimals - 1) . 'f', $value),
                        $currency
                    ]; // Filtered

                    // Create a currency with less decimal places then required (w/o currency symbol)
                    $currency = preg_replace('#' . preg_quote($currencySymbol) . '#u', '', $currency);
                    $currency = preg_replace('/^[\xC2\xA0\s]+|[\xC2\xA0\s]+$/u', '', $currency);
//                    echo $currency . PHP_EOL;
                    $data[] = [$locale, true, true, $currency, $currency]; // Not filtered
                    $data[] = [$locale, true, false, $currency, $currency]; // Not filtered
                    $data[] = [
                        $locale,
                        false,
                        false,
                        (double) sprintf('%.' . ($numDecimals - 1) . 'f', $value),
                        $currency
                    ]; // Filtered
                    $data[] = [$locale, false, true, $currency, $currency]; // Not filtered

                    // Create a currency with more decimal places then required (w/ currency symbol)
                    $formatter->setAttribute(\NumberFormatter::FRACTION_DIGITS, $numDecimals + 1);
                    $currency = preg_replace('/^[\xC2\xA0\s]+|[\xC2\xA0\s]+$/u', '', $formatter->format($value));
//                    echo $currency . PHP_EOL;
                    $data[] = [$locale, true, true, $currency, $currency]; // Not filtered
                    $data[] = [$locale, true, false, $currency, $currency]; // Not filtered
                    $data[] = [
                        $locale,
                        false,
                        false,
                        (double) sprintf('%.' . ($numDecimals + 1) . 'f', $value),
                        $currency
                    ]; // Filtered
                    $data[] = [
                        $locale,
                        false,
                        true,
                        (double) sprintf('%.' . ($numDecimals + 1) . 'f', $value),
                        $currency
                    ]; // Filtered

                    // Create a currency with more decimal places then required (w/o currency symbol)
                    $currency = preg_replace('#' . preg_quote($currencySymbol) . '#u', '', $currency);
                    $currency = preg_replace('/^[\xC2\xA0\s]+|[\xC2\xA0\s]+$/u', '', $currency);
//                    echo $currency . PHP_EOL;
                    $data[] = [$locale, true, true, $currency, $currency]; // Not filtered
                    $data[] = [$locale, true, false, $currency, $currency]; // Not filtered
                    $data[] = [
                        $locale,
                        false,
                        false,
                        (double) sprintf('%.' . ($numDecimals + 1) . 'f', $value),
                        $currency
                    ]; // Filtered
                    $data[] = [$locale, false, true, $currency, $currency]; // Not filtered

                }
                // Restore correct number of maximum decimal places
                $formatter->setAttribute(\NumberFormatter::FRACTION_DIGITS, $numDecimals);

                // Create completely formatted currency value (w/ currency symbol)
                $currency = $formatter->formatCurrency($value, $isoSymbol);
//                echo $currency . PHP_EOL;
                $data[] = [$locale, true, true, $value, $currency]; // Filtered

                // Create currency value with letters inside
                $randomPos = rand(0, mb_strlen($currency) - 1);
                $currency = mb_substr($currency, 0, $randomPos) . 'X' . mb_substr($currency, $randomPos);
//                echo $currency . PHP_EOL;
                $daa[] = [$locale, true, true, $currency, $currency]; // Not filtered

                // Create currency value (w/ currency symbol) (w/o group separators)
                if (mb_strpos($currency, $groupSep) !== false) {
                    $formatter->setSymbol(\NumberFormatter::MONETARY_GROUPING_SEPARATOR_SYMBOL, null);
                    $currency = $formatter->formatCurrency($value, $isoSymbol);
//                    echo $currency . PHP_EOL;
                    $data[] = [$locale, true, true, $value, $currency]; // Filtered
                    $formatter->setSymbol(\NumberFormatter::MONETARY_GROUPING_SEPARATOR_SYMBOL, $groupSep);
                }
                // Create currency value with ISO currency symbol
                $formatter->setSymbol(\NumberFormatter::CURRENCY_SYMBOL, $isoSymbol);
                $currency = $formatter->format($value);
//                echo $currency . PHP_EOL;
                $data[] = [$locale, true, true, $value, $currency]; // Filtered
                // Create currency value with ISO currency symbol (w/o group separators)
                if (mb_strpos($currency, $groupSep) !== false) {
                    $formatter->setSymbol(\NumberFormatter::MONETARY_GROUPING_SEPARATOR_SYMBOL, null);
                    $currency = $formatter->format($value);
//                    echo $currency . PHP_EOL;
                    $data[] = [$locale, true, true, $value, $currency]; // Filtered
                    $formatter->setSymbol(\NumberFormatter::MONETARY_GROUPING_SEPARATOR_SYMBOL, $groupSep);
                }
                // Create currency values with wrong ISO currency symbol or other text after it
                $currency = $currency . 'S';
//                echo $currency . PHP_EOL;
                $data[] = [$locale, true, true, $currency, $currency]; // Not filtered
                // Create currency value w/o any currency symbol
                $formatter->setSymbol(\NumberFormatter::CURRENCY_SYMBOL, null);
                $currency = $formatter->format($value); // preg_replace('/^[\xC2\xA0\s]+|[\xC2\xA0\s]+$/u', '', ...);
//                echo $currency . PHP_EOL;
                $data[] = [$locale, true, true, $currency, $currency]; // Not filtered
                $data[] = [$locale, true, false, $value, $currency]; // Filtered when currency symbol is not mandatory

                if ($value >= 0) {
                    // Create currency value expressed in scientific notation w/o any currency symbol
                    $currency = $exponantiatior->format($value, \NumberFormatter::TYPE_DOUBLE);
//                    echo $currency . PHP_EOL;
                    $data[] = [$locale, true, true, $currency, $currency]; // Not filtered
                    $data[] = [$locale, true, false, $currency, $currency]; // Not filtered
                    // Create currency value expressed in scientific notation with proper currency symbol
                    $currency = $posPre . $currency . $posSuf;
//                    echo  $currency . PHP_EOL;
                    $data[] = [$locale, true, true, $currency, $currency]; // Not filtered
                    $data[] = [$locale, true, false, $currency, $currency]; // Not filtered
                } else {
                    // Create negative currency value expressed in scientific notation with proper currency symbol
                    $currency = $exponantiatior->format(abs($value), \NumberFormatter::TYPE_DOUBLE);
                    $currency = $negPre . $currency . $negSuf;
//                    echo  $currency . PHP_EOL;
                    $data[] = [$locale, true, true, $currency, $currency]; // Not filtered
                    $data[] = [$locale, true, false, $currency, $currency]; // Not filtered
                }
            }

//            echo '---' . PHP_EOL;
        }
        return $data;
    }
}
