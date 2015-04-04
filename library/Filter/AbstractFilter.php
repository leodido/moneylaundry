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
     * @var bool
     */
    protected $isInitialized = false;

    /**
     * Retrieve (and lazy load) the number formatter
     *
     * @return \NumberFormatter
     */
    public function getFormatter()
    {
        if ($this->formatter === null) {
            // FIXME: assign created formatted to a var
            // FIXME: throw exception if !$formatter
            $this->setFormatter(\NumberFormatter::create($this->getLocale(), \NumberFormatter::CURRENCY));
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
        $this->teardown();
        $this->formatter = $formatter;
        $this->options['locale'] = $formatter->getLocale(\Locale::VALID_LOCALE);

        return $this;
    }

    /**
     * TODO: doc
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
     * TODO: doc
     *
     * @return string
     */
    public function getCurrencyCode()
    {
        return $this->options['currency_code'];
    }

    /**
     * Initialize settings
     */
    protected function initialize()
    {
        if (!$this->isInitialized) {
            // Initialize formatter
            $this->formatter = $this->getFormatter();
            // Retrieve current intl currency code
            if (!$this->getCurrencyCode()) {
                $this->setCurrencyCode($this->formatter->getTextAttribute(\NumberFormatter::CURRENCY_CODE));
            }
            $this->isInitialized = true;
        }
    }

    /**
     * Teardown settings.
     */
    protected function teardown()
    {
        $this->formatter = null;
        $this->options['currency_code'] = null;
        $this->isInitialized = false;
    }
}
