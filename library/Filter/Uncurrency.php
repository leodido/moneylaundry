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
 * The filtering process can be tuned according to the user preferences and needs:
 * - it can accept amounts formatted according to the locale pattern but WITH a currency different from the default one
 * - it can accept amounts whose number of decimal places (i.e., the scale) does NOT match that specified by the locale
 * - it can accept amounts WITHOUT the currency (symbol, code, or display names) // FIXME: display names?
 */
class Uncurrency extends AbstractFilter
{
    const DEFAULT_SCALE_CORRECTNESS = true;
    const DEFAULT_CURRENCY_OBLIGATORINESS = true;

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
    const INFINITY_SYMBOL = 1008;
    const NAN_SYMBOL = 1009;
    const CURRENCY_CODE = 1010;

    /**
     * Default options
     *
     * Meanings:
     * - Key 'locale' contains the locale string (e.g., <language>[_<country>][.<charset>]) you desire
     * - Key 'currency_code' contains an ISO 4217 currency code string
     * - Key 'scale_correctness' contains a boolean value indicating
     *   if the scale (i.e., the number of digits to the right of the decimal point in a number) have to match
     *   the scale specified by the pattern of the current locale
     * - Key 'currency_obligatoriness' contains a boolean value indicating
     *   if the presence of the currency is mandatory or not
     *
     * @var array
     */
    protected $options = [
        'locale' => null,
        'currency_code' => null,
        'scale_correctness' => self::DEFAULT_SCALE_CORRECTNESS,
        'currency_obligatoriness' => self::DEFAULT_CURRENCY_OBLIGATORINESS
    ];

    /**
     * @var array
     */
    protected $symbols = [];

    /**
     * @var array
     */
    protected $regexComponents = [];

    /**
     * Ctor
     *
     * @param array|\Traversable|string|null    $localeOrOptions
     * @param string|null                       $currencyCode
     * @param bool                              $scaleCorrectness
     * @param bool                              $currencyObligatoriness
     */
    public function __construct(
        $localeOrOptions = null,
        $currencyCode = null,
        $scaleCorrectness = self::DEFAULT_SCALE_CORRECTNESS,
        $currencyObligatoriness = self::DEFAULT_CURRENCY_OBLIGATORINESS
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
                $this->setCurrencyCode($currencyCode); // FIXME: feature(currencycode)
                $this->setScaleCorrectness($scaleCorrectness);
                $this->setCurrencyObligatoriness($currencyObligatoriness);
            }
        }
    }

    /**
     * Initialize settings.
     */
    protected function initialize()
    {
        if (!$this->isInitialized) {
            parent::initialize();
            // Disable scientific notation
            $this->getFormatter()->setSymbol(\NumberFormatter::EXPONENTIAL_SYMBOL, null);
            // Setup symbols
            $this->initSymbols();
            // Setup regex components
            $this->initRegexComponents();
            $this->isInitialized = true;
        }
    }

    /**
     * Teardown settings.
     */
    protected function teardown()
    {
        parent::teardown();

        $this->symbols = [];
        $this->regexComponents = [];
    }

    /**
     * Init formatter symbols
     *
     * @return array
     */
    protected function initSymbols()
    {
        if ($this->symbols == null) {
            $formatter = $this->getFormatter();
            // Retrieve and process symbols
            $this->symbols[self::CURRENCY_SYMBOL] = $formatter->getSymbol(\NumberFormatter::CURRENCY_SYMBOL);
            $this->symbols[self::GROUP_SEPARATOR_SYMBOL] = $formatter->getSymbol(
                \NumberFormatter::MONETARY_GROUPING_SEPARATOR_SYMBOL
            );
            $this->symbols[self::SEPARATOR_SYMBOL] = $formatter->getSymbol(
                \NumberFormatter::MONETARY_SEPARATOR_SYMBOL
            );
            $this->symbols[self::INFINITY_SYMBOL] = $formatter->getSymbol(\NumberFormatter::INFINITY_SYMBOL);
            $this->symbols[self::NAN_SYMBOL] = $formatter->getSymbol(\NumberFormatter::NAN_SYMBOL);
            // FIXME? remove currency symbol from pattern is needed
            $this->symbols[self::POSITIVE_PREFIX] = str_replace(
                $this->symbols[self::CURRENCY_SYMBOL],
                '',
                $formatter->getTextAttribute(\NumberFormatter::POSITIVE_PREFIX)
            );
            // FIXME? remove currency symbol from pattern is needed
            $this->symbols[self::POSITIVE_SUFFIX] = str_replace(
                $this->symbols[self::CURRENCY_SYMBOL],
                '',
                $formatter->getTextAttribute(\NumberFormatter::POSITIVE_SUFFIX)
            );
            // FIXME? remove currency symbol from pattern is needed
            $this->symbols[self::NEGATIVE_PREFIX] = str_replace(
                $this->symbols[self::CURRENCY_SYMBOL],
                '',
                $formatter->getTextAttribute(\NumberFormatter::NEGATIVE_PREFIX)
            );
            // FIXME? remove currency symbol from pattern is needed
            $this->symbols[self::NEGATIVE_SUFFIX] = str_replace(
                $this->symbols[self::CURRENCY_SYMBOL],
                '',
                $formatter->getTextAttribute(\NumberFormatter::NEGATIVE_SUFFIX)
            );
            $this->symbols[self::FRACTION_DIGITS] = $this->formatter->getAttribute(\NumberFormatter::FRACTION_DIGITS);
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

//    /**
//     * Set a number formatter
//     *
//     * Note that using a custom formatter will probably void the class functionalities.
//     *
//     * @param  \NumberFormatter $formatter
//     * @return $this
//     */
//    public function setFormatter(\NumberFormatter $formatter)
//    {
//        $this->teardown();
//
//        return parent::setFormatter($formatter);
//    }

    /**
     * Set the locale
     *
     * @param  string|null $locale
     * @return $this
     */
    public function setLocale($locale = null)
    {
        $this->teardown();

        return parent::setLocale($locale);
    }

    /**
     * Set whether to check or not that the number of decimal places is as requested by current locale pattern
     *
     * @param  bool $exactFractionDigits
     * @return $this
     */
    public function setScaleCorrectness($exactFractionDigits)
    {
        $this->options['scale_correctness'] = (bool) $exactFractionDigits;
        return $this;
    }

    /**
     * Set whether the currency symbol is mandatory or not
     *
     * @param $currencySymbolMandatory
     * @return $this
     */
    public function setCurrencyObligatoriness($currencySymbolMandatory)
    {
        $this->options['currency_obligatoriness'] = (bool) $currencySymbolMandatory;
        return $this;
    }

    /**
     * The fraction digits have to be spiecified and exact?
     *
     * @return bool
     */
    public function getScaleCorrectness()
    {
        return $this->options['scale_correctness'];
    }

    /**
     * Is the currency symbol mandatory?
     * 
     * @return bool
     */
    public function getCurrencyObligatoriness()
    {
        return $this->options['currency_obligatoriness'];
    }

    /**
     * Returns the result of filtering $value
     *
     * @param  mixed $value
     * @return mixed
     */
    public function filter($value)
    {
        if (is_string($value)) {
            // Init
            $this->initialize();
            $unfilteredValue = $value;

            // Replace spaces with NBSP (non breaking spaces)
            $value = str_replace("\x20", "\xC2\xA0", $value);

            // Get decimal place info
            $numFractionDigits = $this->getSymbol(self::FRACTION_DIGITS);
            $numDecimals = $this->countDecimalDigits($value);

            // Parse as currency
            ErrorHandler::start();
            $currency = $this->getFormatter();
            $position = 0;
            $result = $currency->parseCurrency($value, $resultCurrencyCode, $position);

            // Input is a valid currency?
            if ($result !== false) {
                ErrorHandler::stop();
                // FIXME: feature(currencycode)
                // Check that detect currency matches with specified currency
                if ($resultCurrencyCode !== $this->getCurrencyCode()) { // FIXME? && $this->getCurrencyObligatoriness()
                    return $unfilteredValue;
                }

                // Check if the parsing finished before the end of the input
                if ($position !== mb_strlen($value, 'UTF-8')) {
                    return $unfilteredValue;
                }
                // Check if the number of decimal digits match the requirement
                if ($this->getScaleCorrectness() && $numDecimals !== $numFractionDigits) {
                    return $unfilteredValue;
                }

                return $result;
            }

            // NAN handling
            if ($this->getSymbol(self::NAN_SYMBOL) === $value) { // FIXME? && !$this->getCurrencyObligatoriness()
                return NAN; // Return the double NAN
            }

            // At this stage result is FALSE and input probably is not a well-formatted currency

            // Check if the currency symbol is mandatory
            if ($this->getCurrencyObligatoriness()) {
                ErrorHandler::stop();
                return $unfilteredValue;
            }

            // Regex components
            $symbols = array_filter(array_unique(array_values($this->getSymbols())));
            $numbers = $this->getRegexComponent(self::REGEX_NUMBERS);
            $flags = $this->getRegexComponent(self::REGEX_FLAGS);

            // Build allowed chars regex
            $allowedChars = sprintf('#^[%s]+$#%s', $numbers . implode('', array_map('preg_quote', $symbols)), $flags);

            // Check that value contains only allowed characters (digits, group and decimal separator)
            $result = false;
            if (preg_match($allowedChars, $value)) {
                $decimal = \NumberFormatter::create($this->getLocale(), \NumberFormatter::DECIMAL);

                // Check if the number of decimal digits match the requirement
                if ($this->getScaleCorrectness() && $numDecimals !== $numFractionDigits) {
                    return $unfilteredValue;
                }

                // Ignore spaces
                $value = str_replace("\xC2\xA0", '', $value);

                // Substitute negative currency representation with negative number representation
                $decimalNegPrefix = $decimal->getTextAttribute(\NumberFormatter::NEGATIVE_PREFIX);
                $decimalNegSuffix = $decimal->getTextAttribute(\NumberFormatter::NEGATIVE_SUFFIX);
                $currencyNegPrefix = $this->getSymbol(self::NEGATIVE_PREFIX);
                $currencyNegSuffix = $this->getSymbol(self::NEGATIVE_SUFFIX);
                if ($decimalNegPrefix !== $currencyNegPrefix && $decimalNegSuffix !== $currencyNegSuffix) {
                    $regex = sprintf(
                        '#^%s([%s%s%s]+)%s$#%s',
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
            return $result !== false ? $result : $unfilteredValue; // FIXME? strict check that it is a double
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
        $decimals = mb_substr(mb_strrchr($number, $this->getSymbol(self::SEPARATOR_SYMBOL), false), 1);
        return preg_match_all(
            sprintf(
                '#%s#%s',
                $this->getRegexComponent(self::REGEX_NUMBERS),
                $this->getRegexComponent(self::REGEX_FLAGS)
            ),
            $decimals
        );
    }
}
