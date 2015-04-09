<?php
/**
 * MoneyLaundry
 *
 * @link        https://github.com/leodido/moneylaundry
 * @copyright   Copyright (c) 2015, Leo Di Donato
 * @license     http://opensource.org/licenses/MIT      MIT license
 */
namespace MoneyLaundry\Filter;

use Zend\I18n\Exception;
use Zend\I18n\Filter\AbstractLocale;

/**
 * Class AbstractFilter
 */
abstract class AbstractFilter extends AbstractLocale
{
    const DEFAULT_SCALE_CORRECTNESS = true;
    const DEFAULT_CURRENCY_CORRECTNESS = true;

    /**
     * @var \NumberFormatter
     */
    protected $formatter = null;

    /**
     * Retrieve (and lazy load) the number formatter
     *
     * @return \NumberFormatter
     */
    public function getFormatter()
    {
        if ($this->formatter === null) {
            $formatter = new \NumberFormatter($this->getLocale(), \NumberFormatter::CURRENCY);
            $this->setFormatter($formatter);
        }

        return $this->formatter;
    }

    /**
     * Set a number formatter.
     *
     * @param  \NumberFormatter $formatter
     * @return $this
     */
    public function setFormatter(\NumberFormatter $formatter)
    {
        $this->formatter = $formatter;
        $this->options['locale'] = $formatter->getLocale(\Locale::VALID_LOCALE);

        return $this;
    }

    /**
     * Set the currency code.
     *
     * @param   string|null $currencyCode
     * @return  $this
     */
    public function setCurrencyCode($currencyCode = null)
    {
        $this->options['currency_code'] = $currencyCode;
        return $this;
    }

    /**
     * Retrieve the currency code.
     *
     * @return string|null
     */
    public function getCurrencyCode()
    {
        if (!isset($this->options['currency_code'])) {
            if ($this->formatter) {
                return $this->formatter->getTextAttribute(\NumberFormatter::CURRENCY_CODE);
            }
            return null;
        }
        return $this->options['currency_code'];
    }

    /**
     * Setup the formatter's currency code, then return it.
     *
     * It uses the currency code user has set or the default one from NumberFormatter.
     * Infact it also creates a NumberFormatter instance if it is not yet instantiated.
     *
     * @return string
     */
    protected function setupCurrencyCode()
    {
        $formatter = $this->getFormatter();
        $currencyCode = $this->getCurrencyCode();
        $formatter->setTextAttribute(\NumberFormatter::CURRENCY_CODE, $currencyCode);
        return $currencyCode;
    }

    /**
     * Set the locale.
     *
     * @param  string|null $locale
     * @return $this
     */
    public function setLocale($locale = null)
    {
        $this->formatter = null;
        return parent::setLocale($locale);
    }

    /**
     * Set whether to check if the number of decimal places is equal to that requested by the current locale pattern.
     *
     * @param  bool $scaleCorrectness
     * @return $this
     */
    public function setScaleCorrectness($scaleCorrectness)
    {
        $this->options['scale_correctness'] = (bool) $scaleCorrectness;
        return $this;
    }

    /**
     * The number of decimal places have to be correct?
     *
     * @return bool
     */
    public function getScaleCorrectness()
    {
        return $this->options['scale_correctness'];
    }

    /**
     * Set whether the currency presence and correctness is mandatory or not.
     *
     * @param $currencyCorrectness
     * @return $this
     */
    public function setCurrencyCorrectness($currencyCorrectness)
    {
        $this->options['currency_correctness'] = (bool) $currencyCorrectness;
        return $this;
    }

    /**
     * Is the currency presence and correctness mandatory?
     *
     * @return bool
     */
    public function getCurrencyCorrectness()
    {
        return $this->options['currency_correctness'];
    }

    /**
     * Check wether the scale of the given float accomodates the given precision.
     *
     * @param float     $floatValue     The float which scale is to be checked
     * @param int       $precision      The number of decimal digits to round to
     * @param int       $roundingMode   How to round the float
     * @return bool     The check result
     */
    protected function isFloatScalePrecise($floatValue, $precision, $roundingMode = PHP_ROUND_HALF_UP)
    {
        $testValue = round($floatValue, $precision, $roundingMode);
        return $floatValue === $testValue;
    }
}
