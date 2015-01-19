<?php
/**
 * MoneyLaundry
 *
 * @link        https://github.com/leodido/moneylaundry
 * @copyright   Copyright (c) 2015, Leo Di Donato
 * @license     http://opensource.org/licenses/MIT      MIT license
 */
namespace MoneyLaundry\Filter;

use Zend\I18n\Exception as I18nException;
use Zend\I18n\Filter\AbstractLocale;
use Zend\Stdlib\ErrorHandler;
use Zend\Stdlib\StringUtils;

/**
 * Uncurrency filter
 *
 * Given a string containing a well-formatted currency (according to the chosen locale) it extracts the amount,
 * otherwise it return the input value.
 *
 * The filtering process can be tuned according to the user preferences and needs.
 * Infact it can also accept amounts without the currency symbol and/or
 * amounts whose number of decimal places does not match the one specified by the locale pattern.
 */
class Uncurrency extends AbstractLocale
{
    const DEFAULT_FRACTION_DIGITS_OBLIGATORINESS = true;
    const DEFAULT_CURRENCY_SYMBOL_OBLIGATORINESS = true;

    const REGEX_NUMBERS = 0;
    const REGEX_FLAGS = 1;

    const FRACTION_DIGITS = 1000;
    const POSITIVE_PREFIX = 1001;
    const POSITIVE_SUFFIX = 1002;
    const NEGATIVE_PREFIX = 1003;
    const NEGATIVE_SUFFIX = 1004;
    const CURRENCY_SYMBOL = 1005;
    const SEPARATOR_SYMBOL = 1006;
    const GROUP_SEPARATOR_SYMBOL = 1007;

    /**
     * @var array
     */
    protected $options = [
        'locale' => null,
        'fraction_digits_mandatory' => self::DEFAULT_FRACTION_DIGITS_OBLIGATORINESS,
        'currency_symbol_mandatory' => self::DEFAULT_CURRENCY_SYMBOL_OBLIGATORINESS
    ];

    /**
     * @var \NumberFormatter
     */
    protected $formatter = null;

    /**
     * @var array
     */
    protected $symbols = [];

    /**
     * @var array
     */
    protected $regexComponents = [];

    /**
     * @var bool
     */
    protected $isInitialized = false;

    /**
     * @param array|\Traversable|string|null $localeOrOptions
     * @param bool $fractionDigitsMandatory
     * @param bool $currencySymbolMandatory
     */
    public function __construct(
        $localeOrOptions = null,
        $fractionDigitsMandatory = self::DEFAULT_FRACTION_DIGITS_OBLIGATORINESS,
        $currencySymbolMandatory = self::DEFAULT_CURRENCY_SYMBOL_OBLIGATORINESS
    ) {
        parent::__construct();
        // @codeCoverageIgnoreStart
        if (!extension_loaded('mbstring')) {
            throw new I18nException\ExtensionNotLoadedException(sprintf(
                '%s component requires the mbstring PHP extension',
                __NAMESPACE__
            ));
        }
        // @codeCoverageIgnoreEnd

        if ($localeOrOptions !== null) {
            if (static::isOptions($localeOrOptions)) {
                $this->setOptions($localeOrOptions);
            } else {
                $this->setLocale($localeOrOptions);
                $this->setFractionDigitsMandatory($fractionDigitsMandatory);
                $this->setCurrencySymbolMandatory($currencySymbolMandatory);
            }
        }
    }

    protected function initialize()
    {
        if (!$this->isInitialized) {
            $formatter = $this->getFormatter();
            // Disable scientific notation
            $formatter->setSymbol(\NumberFormatter::EXPONENTIAL_SYMBOL, null);
            // Setup symbols
            $this->initSymbols($formatter);
            // Setup regex components
            $this->initRegexComponents();
            $this->isInitialized = true;
        }
    }

    /**
     * Init formatter symbols
     *
     * @param \NumberFormatter $f
     * @return array
     */
    protected function initSymbols($f)
    {
        if ($this->symbols == null) {
            // Retrieve and process symbols
            $this->symbols[self::CURRENCY_SYMBOL] = $f->getSymbol(\NumberFormatter::CURRENCY_SYMBOL);
            $this->symbols[self::GROUP_SEPARATOR_SYMBOL] = $f->getSymbol(
                \NumberFormatter::MONETARY_GROUPING_SEPARATOR_SYMBOL
            );
            $this->symbols[self::SEPARATOR_SYMBOL] = $f->getSymbol(
                \NumberFormatter::MONETARY_SEPARATOR_SYMBOL
            );
            $this->symbols[self::POSITIVE_PREFIX] = str_replace(
                $this->symbols[self::CURRENCY_SYMBOL],
                '',
                $f->getTextAttribute(\NumberFormatter::POSITIVE_PREFIX)
            );
            $this->symbols[self::POSITIVE_SUFFIX] = str_replace(
                $this->symbols[self::CURRENCY_SYMBOL],
                '',
                $f->getTextAttribute(\NumberFormatter::POSITIVE_SUFFIX)
            );
            $this->symbols[self::NEGATIVE_PREFIX] = str_replace(
                $this->symbols[self::CURRENCY_SYMBOL],
                '',
                $f->getTextAttribute(\NumberFormatter::NEGATIVE_PREFIX)
            );
            $this->symbols[self::NEGATIVE_SUFFIX] = str_replace(
                $this->symbols[self::CURRENCY_SYMBOL],
                '',
                $f->getTextAttribute(\NumberFormatter::NEGATIVE_SUFFIX)
            );
            $this->symbols[self::FRACTION_DIGITS] = $f->getAttribute(\NumberFormatter::FRACTION_DIGITS);
        }

        return $this->symbols;
    }

    /**
     * Init regex components
     *
     * @return array
     */
    protected function initRegexComponents()
    {
        if ($this->regexComponents == null) {
            $this->regexComponents[self::REGEX_NUMBERS] = '0-9';
            $this->regexComponents[self::REGEX_FLAGS] = '';
            if (StringUtils::hasPcreUnicodeSupport()) {
                $this->regexComponents[self::REGEX_NUMBERS] = '\p{N}';
                $this->regexComponents[self::REGEX_FLAGS] .= 'u';
            }
        }

        return $this->regexComponents;
    }

    /**
     * @param  \NumberFormatter $formatter
     * @return $this
     */
    public function setFormatter(\NumberFormatter $formatter)
    {
        $this->formatter = $formatter;
        $this->isInitialized = false;

        return $this;
    }

    /**
     * @return \NumberFormatter
     */
    public function getFormatter()
    {
        if ($this->formatter === null) {
            $this->setFormatter(\NumberFormatter::create($this->getLocale(), \NumberFormatter::CURRENCY));
        }

        return $this->formatter;
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
        $this->isInitialized = false;
        return parent::setLocale($locale);
    }

    /**
     * Set whether to check or not that the number of decimal places is as requested by current locale pattern
     *
     * @param  bool $exactFractionDigits
     * @return $this
     */
    public function setFractionDigitsMandatory($exactFractionDigits)
    {
        $this->options['fraction_digits_mandatory'] = (bool) $exactFractionDigits;
        return $this;
    }

    /**
     * Set whether the currency symbol is mandatory or not
     *
     * @param $currencySymbolMandatory
     * @return $this
     */
    public function setCurrencySymbolMandatory($currencySymbolMandatory)
    {
        $this->options['currency_symbol_mandatory'] = (bool) $currencySymbolMandatory;
        return $this;
    }

    /**
     * The fraction digits have to be spiecified and exact?
     *
     * @return bool
     */
    public function isFractionDigitsMandatory()
    {
        return $this->options['fraction_digits_mandatory'];
    }

    /**
     * Is the currency symbol mandatory?
     * 
     * @return bool
     */
    public function isCurrencySymbolMandatory()
    {
        return $this->options['currency_symbol_mandatory'];
    }

    /**
     * Returns the result of filtering $value
     *
     * @param  mixed $value
     * @return string
     */
    public function filter($value)
    {
        if (is_string($value)) {
            // Init
            $this->initialize();
            $unfilteredValue = $value;

            // Replace spaces with non breaking spaces
            $value = str_replace("\x20", "\xC2\xA0", $value);

            // Get decimal place info
            $numFractionDigits = $this->getSymbol(self::FRACTION_DIGITS);
            $numDecimals = $this->countDecimalDigits($value);

            // Parse as currency
            ErrorHandler::start();
            $currency = $this->getFormatter();
            $position = 0;
            $result = $currency->parseCurrency($value, $isoCurrencySym, $position);

            // Input is a valid currency?
            if ($result !== false) {
                ErrorHandler::stop();
                // Check if the parsing finished before the end of the input
                if ($position !== mb_strlen($value, 'UTF-8')) {
                    return $unfilteredValue;
                }
                // Check if the number of decimal digits match the requirement
                if ($this->isFractionDigitsMandatory() && $numDecimals !== $numFractionDigits) {
                    return $unfilteredValue;
                }

                return $result;
            }
            // At this stage input is not a well-formatted currency

            // Check if the currency symbol is mandatory
            if ($this->isCurrencySymbolMandatory()) {
                ErrorHandler::stop();
                return $unfilteredValue;
            }

            // Regex components
            $symbols = array_filter(array_unique(array_values($this->getSymbols())));
            $numbers = $this->getRegexComponent(self::REGEX_NUMBERS);
            $flags = $this->getRegexComponent(self::REGEX_FLAGS);

            // Build allowed chars regex
            $allowedChars = sprintf('/^[%s]+$/%s', $numbers . implode('', array_map('preg_quote', $symbols)), $flags);

            // Check that value contains only allowed characters (digits, group and decimal separator)
            $result = false;
            if (preg_match($allowedChars, $value)) {
                $decimal = \NumberFormatter::create($this->getLocale(), \NumberFormatter::DECIMAL);

                // Check if the number of decimal digits match the requirement
                if ($this->isFractionDigitsMandatory() && $numDecimals !== $numFractionDigits) {
                    return $unfilteredValue;
                }

                // Ignore spaces
                $value =  str_replace("\xC2\xA0", '', $value); // FIXME? use one (' ') space

                // Substitute negative currency representation with negative number representation
                $decimalNegPrefix = $decimal->getTextAttribute(\NumberFormatter::NEGATIVE_PREFIX);
                $decimalNegSuffix = $decimal->getTextAttribute(\NumberFormatter::NEGATIVE_SUFFIX);
                $currencyNegPrefix = $this->getSymbol(self::NEGATIVE_PREFIX);
                $currencyNegSuffix = $this->getSymbol(self::NEGATIVE_SUFFIX);
                if ($decimalNegPrefix !== $currencyNegPrefix && $decimalNegSuffix !== $currencyNegSuffix) {
                    $regex = sprintf(
                        '/^%s([%s%s%s]+)%s$/%s',
                        preg_quote($currencyNegPrefix),
                        $numbers,
                        preg_quote($this->getSymbol(self::SEPARATOR_SYMBOL)),
                        preg_quote($this->getSymbol(self::GROUP_SEPARATOR_SYMBOL)),
                        preg_quote($currencyNegSuffix),
                        $flags
                    );
                    $value = preg_replace($regex, $decimalNegPrefix . '\\1' . $decimalNegSuffix, $value);
                }

                // Try to parse as a simple decimal (formatted) number
                $result = $decimal->parse($value, \NumberFormatter::TYPE_DOUBLE);
            }

            ErrorHandler::stop();
            return $result !== false ? $result : $unfilteredValue;
        }
        // At this stage input is not a string

        return $value;
    }

    /**
     * Get all the symbols
     *
     * @return array
     */
    public function getSymbols()
    {
        if (count($this->symbols) == 0) {
            throw new I18nException\RuntimeException(
                'Symbols are not present because the filter has not been initialized'
            );
        }

        return $this->symbols;
    }

    /**
     * Get all the regex components
     *
     * @return array
     */
    public function getRegexComponents()
    {
        if (count($this->regexComponents) == 0) {
            throw new I18nException\RuntimeException(
                'Regex components are not present because the filter has not been initialized'
            );
        }

        return $this->regexComponents;
    }

    /**
     * Retrieve single symbol by its constant identifier
     *
     * @param int $symbol
     * @return string
     */
    public function getSymbol($symbol)
    {
        $symbols = $this->getSymbols();

        if (!isset($symbols[$symbol])) {
            throw new I18nException\InvalidArgumentException(sprintf(
                'Symbol not found; received "%s"',
                $symbol
            ));
        }

        return $symbols[$symbol];
    }

    /**
     * Retrieve single regex component by its constant identifier
     *
     * @param int $regexComponent
     * @return string
     */
    public function getRegexComponent($regexComponent)
    {
        $regexComponents = $this->getRegexComponents();

        if (!isset($regexComponents[$regexComponent])) {
            throw new I18nException\InvalidArgumentException(sprintf(
                'Regex component not found; received "%s"',
                $regexComponent
            ));
        }

        return $regexComponents[$regexComponent];
    }

    /**
     * Count the number of decimals that $number contains
     *
     * @param string $number
     * @return int
     */
    protected function countDecimalDigits($number)
    {
        $decimals = explode($this->getSymbol(self::SEPARATOR_SYMBOL), $number);
        return preg_match_all(
            '/' . $this->getRegexComponent(self::REGEX_NUMBERS) . '/' . $this->getRegexComponent(self::REGEX_FLAGS),
            isset($decimals[1]) ? $decimals[1] : ''
        );
    }
}
