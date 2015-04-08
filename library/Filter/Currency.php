<?php
/**
 * MoneyLaundry
 *
 * @link        https://github.com/leodido/moneylaundry
 * @copyright   Copyright (c) 2015, Leo Di Donato
 * @license     http://opensource.org/licenses/MIT      MIT license
 */
namespace MoneyLaundry\Filter;

use Zend\Stdlib\ErrorHandler;

/**
 * Class Currency
 *
 * Given a locale and a currency code it formats float numbers to the corresponding well-formatted currency amount.
 * The filtering process in the default mode accepts only
 * floats which number of decimals accomodates the locale and currency number of fraction digits.
 * This behaviour can be disabled setting the scale correctness option to false.
 */
class Currency extends AbstractFilter
{
    /**
     * Default option values
     *
     * @var array
     */
    protected $options = [
        'locale' => null,
        'currency_code' => null,
        'scale_correctness' => self::DEFAULT_SCALE_CORRECTNESS
    ];

    /**
     * Ctor
     *
     * Available options are:
     * - Key 'locale' contains the locale string (e.g., <language>[_<country>][.<charset>]) you desire
     * - Key 'currency_code' contains an ISO 4217 currency code string
     *
     * @param array|\Traversable|string|null $localeOrOptions
     * @param string|null                    $currencyCode
     */
    public function __construct(
        $localeOrOptions = null,
        $currencyCode = null
    ) {
        parent::__construct();

        if ($localeOrOptions !== null) {
            if (static::isOptions($localeOrOptions)) {
                $this->setOptions($localeOrOptions);
            } else {
                $this->setLocale($localeOrOptions);
                $this->setCurrencyCode($currencyCode);
            }
        }
    }

    /**
     * Returns the result of filtering $value
     *
     * @param mixed $value
     * @return mixed
     */
    public function filter($value)
    {
        $unfilteredValue = $value;
        if (is_float($value) && !is_nan($value) && !is_infinite($value)) {
            ErrorHandler::start();

            $formatter = $this->getFormatter();
            $currencyCode = $this->setupCurrencyCode();
            $result = $formatter->formatCurrency($value, $currencyCode);

            ErrorHandler::stop();

            if ($result === false) {
                return $unfilteredValue;
            }

            // Retrieve the precision internally used by the formatter (i.e., depends from locale and currency code)
            $precision = $formatter->getAttribute(\NumberFormatter::FRACTION_DIGITS);

            // $result is considered valid if the currency's fraction digits can accomodate the $value decimal precision
            // i.e. EUR (fraction digits = 2) must NOT allow double(1.23432423432)
            $isFloatScalePrecise = $this->isFloatScalePrecise(
                $value,
                $precision,
                $this->formatter->getAttribute(\NumberFormatter::ROUNDING_MODE)
            );
            if ($this->getScaleCorrectness() && !$isFloatScalePrecise) {
                return $unfilteredValue;
            }

            return $result;
        }

        return $unfilteredValue;
    }
}
