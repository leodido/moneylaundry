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
 *
 * Validate the input as a valid and well-formatted currency amount for the given locale.
 *
 * The validation process can be tuned according to the user preferences and needs.
 * Infact it can also accept currency amounts without the currency symbol and/or
 * currency amounts whose number of decimal places does not match the one specified by the locale pattern.
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
        self::INVALID => "Invalid input given: '%value%' is not a string",
        self::NOT_CURRENCY => "The '%value%' is not a well-formatted currency; the requested format is %format%",
        self::NOT_POSITIVE => "The '%value%' value does not appear to be a positive currency",
    ];

    protected $pattern;

    protected $messageVariables = [
        'format' => 'pattern'
    ];


    /**
     * Scale correctness option.
     *
     * @var bool
     */
    protected $scaleCorrectness = Uncurrency::DEFAULT_SCALE_CORRECTNESS;

    /**
     * Set whether to check if the the number of decimal places is equal to the that of the current locale pattern.
     *
     * @param  bool $scaleCorrectness
     * @return $this
     */
    public function setScaleCorrectness($scaleCorrectness)
    {
        $this->scaleCorrectness = (bool) $scaleCorrectness;
        return $this;
    }

    /**
     * The number of decimal places have to be correct?
     *
     * @return bool
     */
    public function getScaleCorrectness()
    {
        return $this->scaleCorrectness;
    }

    /**
     * Currency correctness (and presence) option.
     *
     * @var bool
     */
    protected $currencyCorrectness = Uncurrency::DEFAULT_CURRENCY_CORRECTNESS;

    /**
     * Set whether the currency presence and correctness is mandatory or not.
     *
     * @param $currencyCorrectness
     * @return $this
     */
    public function setCurrencyCorrectness($currencyCorrectness)
    {
        $this->currencyCorrectness = (bool) $currencyCorrectness;
        return $this;
    }

    /**
     * Is the currency presence and correctness mandatory?
     *
     * @return bool
     */
    public function getCurrencyCorrectness()
    {
        return $this->currencyCorrectness;
    }

    protected $currencyCode;

    /**
     * Set the currency code.
     *
     * @param   string|null $currencyCode
     * @return  $this
     */
    public function setCurrencyCode($currencyCode = null)
    {
        $this->currencyCode = $currencyCode;
        return $this;
    }

    /**
     * Retrieve the currency code.
     *
     * @return string|null
     */
    public function getCurrencyCode()
    {
        return $this->currencyCode; // FIXME
    }

    /**
     * Locale option.
     *
     * @var string|null
     */
    protected $locale;

    /**
     * Retrieve the locale.
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
     * Set the locale.
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
     * Whether to allow negative currency amount or not.
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
     * Set the negative allowed option.
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
     * Constructor for the currency validator.
     *
     * @param array|\Traversable $options
     * @throws I18nException\ExtensionNotLoadedException Extension intl is not present
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
     * a currency different than the locale's default,
     * a currency string that could not contain currency symbol,
     * a currency string that could contain an inexact number of decimal places.
     *
     * @param  string $value
     * @return bool
     * @throws I18nException\InvalidArgumentException
     */
    public function isValid($value)
    {
        $this->setValue($value);
        if (!is_string($value)) {
            $this->error(self::INVALID);
            return false;
        }

        // Setup filter
        $filter = new Uncurrency(
            $this->getLocale(),
            $this->getCurrencyCode()
        );
        $filter->setScaleCorrectness($this->getScaleCorrectness());
        $filter->setCurrencyCorrectness($this->getCurrencyCorrectness());
        // Filtering
        $result = $filter->filter($this->getValue());
        // Retrieve updated currency code
        $this->currencyCode = $filter->getCurrencyCode();
        if ($result !== $this->getValue()) {
            // Filter succedeed
            if (!$this->isNegativeAllowed() && $result < 0) {
                $this->error(self::NOT_POSITIVE);
                return false;
            }
            return true;
        }
        $this->pattern = $filter->getFormatter()->getPattern();
        $this->error(self::NOT_CURRENCY);
        return false;
    }
}
