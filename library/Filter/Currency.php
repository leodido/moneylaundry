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
 * Given an integer and a locale it returns the corresponding well-formatted currency amount.
 * TODO: complete (dominio-codominio, NAN, INF, exponential notation)
 */
class Currency extends AbstractFilter
{
    /**
     * Default options
     *
     * Meanings:
     * - Key 'locale' contains the locale string (e.g., <language>[_<country>][.<charset>]) you desire
     * - Key 'currency_code' ... // TODO: doc
     *
     * @var array
     */
    protected $options = [
        'locale' => null,
        'currency_code' => null
    ];

    /**
     * Ctor
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
        if (!is_scalar($value) || is_bool($value)) {
            return $value;
        }


        if (is_int($value) || (is_float($value) && !is_nan($value) && !is_infinite($value))) {
            ErrorHandler::start();

            $formatter = $this->getFormatter();
            $result = $formatter->formatCurrency($value, $this->getCurrencyCodeOrDefault());
            ErrorHandler::stop();

            // FIXME: in strict mode, $result should pass only if the currency's fraction digits
            // can accomodate the $value decimal precision
            // i.e. EUR (franction digits = 2) must NOT allow double(1.23432423432)
            return false !== $result ? $result : $value;
        }

        return $value;

//        // Check it is not a numeric written in scientific notation
//        $validator = new ScientificNotation(['locale' => $this->getLocale()]);
//        if ($validator->isValid($value)) {
//            return $value;
//        }
//
//        // From string to number
//        $formatter = new NumberFormat($this->getLocale(), \NumberFormatter::DECIMAL);
//        $decimal = $formatter->filter($value);
//        if ($decimal === $value) {
//            return $value;
//        }
//        // From number to locale formatted string
//        $formatter = new NumberFormat($this->getLocale(), \NumberFormatter::CURRENCY);
//        return $formatter->filter($decimal);
    }
}
