<?php
/**
 * MoneyLaundry
 *
 * @link        https://github.com/leodido/moneylaundry
 * @copyright   Copyright (c) 2015, Leo Di Donato
 * @license     http://opensource.org/licenses/MIT      MIT license
 */
namespace MoneyLaundry\Validator;

use MoneyLaundry\Filter\Uncurrency;
use Zend\Stdlib\ArrayUtils;
use Zend\Validator\AbstractValidator;
use Zend\I18n\Exception as I18nException;

/**
 * Class Currency
 */
class Currency extends AbstractValidator
{
    const INVALID = 'currencyInvalid';
    const NOT_CURRENCY = 'notCurrency';
    const NOT_POSITIVE = 'notPositiveCurrency';

    const DEFAULT_NEGATIVE_ALLOWED = true;

    /**
     * @var array
     */
    protected $messageTemplates = [
        self::INVALID => "Invalid input given: string expected",
        self::NOT_CURRENCY => "The input is not a well-formatted currency",
        self::NOT_POSITIVE => "The input does not appear to be a positive currency",
    ];

    /**
     * Fraction digit obligatoriness option
     * @var bool
     */
    protected $fractionDigitsMandatory = Uncurrency::DEFAULT_FRACTION_DIGITS_OBLIGATORINESS;

    /**
     * Set whether to check or not that the number of decimal places is as requested by current locale pattern
     *
     * @param  bool $exactFractionDigits
     * @return $this
     */
    public function setFractionDigitsMandatory($exactFractionDigits)
    {
        $this->fractionDigitsMandatory = (bool) $exactFractionDigits;
        return $this;
    }

    /**
     * The fraction digits have to be spiecified and exact?
     *
     * @return bool
     */
    public function isFractionDigitsMandatory()
    {
        return $this->fractionDigitsMandatory;
    }

    /**
     * Currency symbol obligatoriness option
     * @var bool
     */
    protected $currencySymbolMandatory = Uncurrency::DEFAULT_CURRENCY_SYMBOL_OBLIGATORINESS;

    /**
     * Set whether the currency symbol is mandatory or not
     *
     * @param $currencySymbolMandatory
     * @return $this
     */
    public function setCurrencySymbolMandatory($currencySymbolMandatory)
    {
        $this->currencySymbolMandatory = (bool) $currencySymbolMandatory;
        return $this;
    }

    /**
     * Is the currency symbol mandatory?
     *
     * @return bool
     */
    public function isCurrencySymbolMandatory()
    {
        return $this->currencySymbolMandatory;
    }

    /**
     * Locale option
     *
     * @var string|null
     */
    protected $locale;

    /**
     * Returns the set locale
     *
     * @return string
     */
    public function getLocale()
    {
        if (null === $this->locale) {
            $this->locale = \Locale::getDefault();
        }
        return $this->locale;
    }

    /**
     * Set the locale to use
     *
     * @param string|null $locale
     * @return Float
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;
        return $this;
    }

    /**
     * Whether to allow negative currency amount or not
     *
     * @var bool
     */
    protected $negativeAllowed = Currency::DEFAULT_NEGATIVE_ALLOWED;

    /**
     * Is a negative currency amount allowed?
     *
     * @return boolean
     */
    public function isNegativeAllowed()
    {
        return $this->negativeAllowed;
    }

    /**
     * Set the negative allowed option
     *
     * @param boolean $negativeAllowed
     * @return $this
     */
    public function setNegativeAllowed($negativeAllowed)
    {
        $this->negativeAllowed = $negativeAllowed;
        return $this;
    }

    /**
     * Constructor for the currency validator
     *
     * @param array|\Traversable $options
     * @throws I18nException\ExtensionNotLoadedException if ext/intl is not present
     */
    public function __construct($options = [])
    {
        // @codeCoverageIgnoreStart
        if (!extension_loaded('intl')) {
            throw new I18nException\ExtensionNotLoadedException(sprintf(
                '%s component requires the intl PHP extension',
                __NAMESPACE__
            ));
        }
        if (!extension_loaded('mbstring')) {
            throw new I18nException\ExtensionNotLoadedException(sprintf(
                '%s component requires the mbstring PHP extension',
                __NAMESPACE__
            ));
        }
        // @codeCoverageIgnoreEnd
        // Set options
        parent::__construct($options);
    }

    /**
     * {@inheritdoc}
     */
    public function setOptions($options = [])
    {
        // Prepare options
        if ($options instanceof \Traversable) {
            $options = ArrayUtils::iteratorToArray($options);
        }
        $options = array_combine(
            array_map(
                function ($key) {
                    return str_replace(' ', '', ucwords(str_replace('_', ' ', $key)));
                },
                array_keys($options)
            ),
            array_values($options)
        );
        //
        parent::setOptions($options);
    }

    /**
     * Returns true if and only if $value is a currency amount well-formatted for the given locale
     *
     * It validates according to the specified options, i.e. whether to consider valid:
     * a negative currency amount,
     * a currency without currency symbol,
     * a currency with an inexact number of decimal places.
     *
     * @param  string $value
     * @return bool
     * @throws I18nException\InvalidArgumentException
     */
    public function isValid($value)
    {
        if (!is_string($value)) {
            $this->error(self::INVALID);
            return false;
        }

        $this->setValue($value);
        $filter = new Uncurrency(
            $this->getLocale(),
            $this->isFractionDigitsMandatory(),
            $this->isCurrencySymbolMandatory()
        );
        $result = $filter->filter($this->getValue());
        if ($result !== $this->getValue()) {
            if (!$this->isNegativeAllowed() && $result < 0) {
                $this->error(self::NOT_POSITIVE);
                return false;
            }
            return true;
        }
        $this->error(self::NOT_CURRENCY);
        return false;
    }
}
