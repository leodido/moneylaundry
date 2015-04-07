<?php
/**
 * MoneyLaundry
 *
 * @link        https://github.com/leodido/moneylaundry
 * @copyright   Copyright (c) 2015, Leo Di Donato
 * @license     http://opensource.org/licenses/MIT      MIT license
 */
namespace MoneyLaundry\Filter;

use Zend\I18n\Filter\AbstractLocale;
use Zend\I18n\Exception;

/**
 * Class AbstractFilter
 */
abstract class AbstractFilter extends AbstractLocale
{
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
            $formatter = \NumberFormatter::create($this->getLocale(), \NumberFormatter::CURRENCY);
            if (!$formatter) {
                throw new Exception\RuntimeException(
                    'Can not create NumberFormatter instance; ' . intl_get_error_message()
                );
            }
            $this->setFormatter($formatter);
        }

        return $this->formatter;
    }

    /**
     * Set a number formatter
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
     * Set the currency code
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
     * Retrieve the currency code
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
     * Use the set currency code if any or the default from NumberFormatter.
     * Note that it creates a NumberFormatter instance if it is not yet instantiated.
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
     * Set the locale
     *
     * @param  string|null $locale
     * @return $this
     */
    public function setLocale($locale = null)
    {
        $this->formatter = null;
        return parent::setLocale($locale);
    }
}
