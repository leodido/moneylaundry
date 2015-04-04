<?php
/**
 * MoneyLaundry
 *
 * @link        https://github.com/leodido/moneylaundry
 * @copyright   Copyright (c) 2015, Leo Di Donato
 * @license     http://opensource.org/licenses/MIT      MIT license
 */
namespace MoneyLaundry\Filter;

use MoneyLaundry\Validator\ScientificNotation;
use Zend\I18n\Filter\AbstractLocale;
use Zend\I18n\Filter\NumberFormat;

/**
 * Class Currency
 *
 * Given an integer and a locale it returns the corresponding well-formatted currency amount.
 * TODO: complete (dominio-codominio, NAN, INF, exponential notation)
 */
class Currency extends AbstractLocale
{
    const DEFAULT_LOCALE = null;
    const DEFAULT_CURRENCY_CODE = null;

    /**
     * Default options
     *
     * Meanings:
     * - Key 'locale' contains the locale string (e.g., <language>[_<country>][.<charset>]) you desire
     *
     * @var array
     */
    protected $options = [
        'locale' => self::DEFAULT_LOCALE,
        'currency_code' => self::DEFAULT_CURRENCY_CODE
    ];

    /**
     * Ctor
     *
     * @param array|\Traversable|string|null $localeOrOptions
     * @param string|null                    $currencyCode
     */
    public function __construct(
        $localeOrOptions = self::DEFAULT_LOCALE,
        $currencyCode = self::DEFAULT_CURRENCY_CODE
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

    public function setCurrencyCode($currencyCode = null)
    {
        $this->options['currency_code'] = $currencyCode;
        return $this;
    }

    public function getCurrencyCode()
    {
        return $this->options['currency_code'];
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

        if (is_float($value) || is_int($value)) {
            // FIXME: internally it uses format(), not formatCurrency(), substitute it with \NumberFormatter
            $formatter = new NumberFormat(
                $this->getLocale(),
                \NumberFormatter::CURRENCY,
                \NumberFormatter::TYPE_DOUBLE
            );
            return $formatter->filter($value);
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
