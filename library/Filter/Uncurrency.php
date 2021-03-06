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
    const DEFAULT_BREAKING_SPACE_ALLOWED = false;

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

    /**
     * Default options
     *
     * Meanings:
     *
     * @var array
     */
    protected $options = [
        'locale' => null,
        'currency_code' => null,
        'scale_correctness' => self::DEFAULT_SCALE_CORRECTNESS,
        'currency_correctness' => self::DEFAULT_CURRENCY_CORRECTNESS,
        'breaking_space_allowed' => self::DEFAULT_BREAKING_SPACE_ALLOWED,
    ];

    /**
     * @var array
     */
    protected $regexComponents = [];

    /**
     * Ctor
     *
     * Available options are:
     * - Key 'locale' contains the locale string (e.g., <language>[_<country>][.<charset>]) you desire
     * - Key 'currency_code' contains an ISO 4217 currency code string
     * - Key 'scale_correctness' contains a boolean value indicating
     *   if the scale (i.e., the number of digits to the right of the decimal point in a number) have to match
     *   the scale specified by the pattern of the current locale
     * - Key 'currency_correctness' contains a boolean value indicating
     *   if the presence and the correctness of the currency is mandatory or not
     *
     * @param array|\Traversable|string|null $localeOrOptions
     * @param string|null $currencyCode
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
     * @return bool
     */
    public function getBreakingSpaceAllowed()
    {
        return $this->options['breaking_space_allowed'];
    }

    /**
     * @param bool $allow
     * @return $this
     */
    public function setBreakingSpaceAllowed($allow)
    {
        $this->options['breaking_space_allowed'] = (bool)$allow;
        return $this;
    }

    /**
     * Returns the result of filtering $value
     *
     * @param  mixed $value
     * @return mixed
     */
    public function filter($value)
    {
        // Store original value
        $unfilteredValue = $value;

        if (is_string($value)) {
            // Initialization
            $formatter = $this->getFormatter();

            // Disable scientific notation
            $formatter->setSymbol(\NumberFormatter::EXPONENTIAL_SYMBOL, null);

            if ($this->getBreakingSpaceAllowed()) {
                // Replace spaces with NBSP (non breaking spaces)
                $value = str_replace("\x20", "\xC2\xA0", $value); // FIXME? can be removed
            }

            // Parse as currency
            ErrorHandler::start();
            $position = 0;
            $currencyCode = $this->setupCurrencyCode();

            if ($this->getCurrencyCorrectness()) {
                // The following parsing mode allows the predefined currency code ONLY.
                // Also it should be more strict and faster than parseCurrency.
                $result = $formatter->parse($value, \NumberFormatter::TYPE_DOUBLE, $position);
            } else {
                // The following parsing mode can work with multiple currencies.
                $result = $formatter->parseCurrency($value, $resultCurrencyCode, $position);
            }

            $fractionDigits = $formatter->getAttribute(\NumberFormatter::FRACTION_DIGITS);

            // Input is a valid currency and the result is within the codomain?
            if ($result !== false && ((is_float($result) && !is_infinite($result) && !is_nan($result)))) {
                ErrorHandler::stop();

                // Exit if the parsing has finished before the end of the input
                if ($position < grapheme_strlen($value)) {
                    return $unfilteredValue;
                }

                // Retrieve currency symbol for the given locale and currency code
                $currencySymbol = $this->getFirstCurrencySymbol($this->getLocale(), $currencyCode);

                // Exit if the currency correctness is mandatory and the currency symbol is not present in the input
                if ($this->getCurrencyCorrectness() && grapheme_strpos($value, $currencySymbol) === false) {
                    return $unfilteredValue;
                }

                if ($this->getScaleCorrectness()) {
                    $countedDecimals = $this->countDecimalDigits(
                        $value,
                        $formatter->getSymbol(\NumberFormatter::MONETARY_SEPARATOR_SYMBOL),
                        $currencySymbol
                    );

                    // Exit if the number of decimal digits (i.e., the scale) does not match the requirement
                    if ($fractionDigits !== $countedDecimals) {
                        return $unfilteredValue;
                    }
                }

                // Here we have a perfectly parsed (pattern correct, currency correct, scale correct) currency amount
                return $result;
            }

            // At this stage result is FALSE and input probably is a not canonical currency amount

            // Check if the currency symbol is mandatory (assiming 'parse MODE')
            if ($this->getCurrencyCorrectness()) {
                ErrorHandler::stop();
                return $unfilteredValue;
            }

            // Retrieve symbols
            $symbolKeys = [
                self::CURRENCY_SYMBOL,
                self::GROUP_SEPARATOR_SYMBOL,
                self::SEPARATOR_SYMBOL,
                self::INFINITY_SYMBOL,
                self::NAN_SYMBOL,
                self::POSITIVE_PREFIX,
                self::POSITIVE_SUFFIX,
                self::NEGATIVE_PREFIX,
                self::NEGATIVE_SUFFIX,
                self::FRACTION_DIGITS,
            ];

            $symbols = [];
            foreach ($symbolKeys as $symbol) {
                $symbols[$symbol] = $this->getSymbol($symbol);
            }

            // Regex components
            $regexSymbols = array_filter(array_unique(array_values($symbols)));
            $numbers = $this->getRegexComponent(self::REGEX_NUMBERS);
            $flags = $this->getRegexComponent(self::REGEX_FLAGS);

            // Build allowed chars regex
            $allowedChars = sprintf(
                '#^[%s]+$#%s',
                $numbers . implode('', array_map('preg_quote', $regexSymbols)),
                $flags
            ); // FIXME: pay attention to NaN and INF symbols here

            // Check that value contains only allowed characters (digits, group and decimal separator)
            $result = false;
            if (preg_match($allowedChars, $value)) {
                $decimal = \NumberFormatter::create($this->getLocale(), \NumberFormatter::DECIMAL);

                // Get decimal place info
                // FIXME: parse and parseCurrancy could use different symbols
                // when used with non default currency code
                $currencySymbol = $this->getFirstCurrencySymbol($this->getLocale(), $currencyCode);
                $numDecimals = $this->countDecimalDigits(
                    $value,
                    $symbols[self::SEPARATOR_SYMBOL],
                    $currencySymbol
                );

                // Check if the number of decimal digits match the requirement
                if ($this->getScaleCorrectness() && $numDecimals !== $fractionDigits) {
                    return $unfilteredValue;
                }

                // Ignore spaces
                $value = str_replace("\xC2\xA0", '', $value);

                // Substitute negative currency representation with negative number representation
                $decimalNegPrefix = $decimal->getTextAttribute(\NumberFormatter::NEGATIVE_PREFIX);
                $decimalNegSuffix = $decimal->getTextAttribute(\NumberFormatter::NEGATIVE_SUFFIX);
                $currencyNegPrefix = $symbols[self::NEGATIVE_PREFIX];
                $currencyNegSuffix = $symbols[self::NEGATIVE_SUFFIX];
                if ($decimalNegPrefix !== $currencyNegPrefix && $decimalNegSuffix !== $currencyNegSuffix) {
                    $regex = sprintf(
                        '#^%s([%s%s%s]+)%s$#%s',
                        preg_quote($currencyNegPrefix),
                        $numbers,
                        preg_quote($symbols[self::SEPARATOR_SYMBOL]),
                        preg_quote($symbols[self::GROUP_SEPARATOR_SYMBOL]),
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
        return $unfilteredValue;
    }

    /**
     * Retrieve single symbol by its constant identifier
     *
     * @param int $symbol
     * @return string
     */
    protected function getSymbol($symbol)
    {
        if (!$this->formatter) {
            throw new I18nException\RuntimeException('An instance of NumberFormatted is required.');
        }

        $formatter = $this->formatter;
        switch ($symbol) {
            case self::CURRENCY_SYMBOL:
                return $formatter->getSymbol(\NumberFormatter::CURRENCY_SYMBOL);
            case self::GROUP_SEPARATOR_SYMBOL:
                return $formatter->getSymbol(
                    \NumberFormatter::MONETARY_GROUPING_SEPARATOR_SYMBOL
                );
            case self::SEPARATOR_SYMBOL:
                return $formatter->getSymbol(
                    \NumberFormatter::MONETARY_SEPARATOR_SYMBOL
                );
            case self::INFINITY_SYMBOL:
                return $formatter->getSymbol(\NumberFormatter::INFINITY_SYMBOL);
            case self::NAN_SYMBOL:
                return $formatter->getSymbol(\NumberFormatter::NAN_SYMBOL);
            case self::POSITIVE_PREFIX:
                // FIXME? remove currency symbol from pattern is needed
                return str_replace(
                    $formatter->getSymbol(\NumberFormatter::CURRENCY_SYMBOL),
                    '',
                    $formatter->getTextAttribute(\NumberFormatter::POSITIVE_PREFIX)
                );
            case self::POSITIVE_SUFFIX:
                // FIXME? remove currency symbol from pattern is needed
                return str_replace(
                    $formatter->getSymbol(\NumberFormatter::CURRENCY_SYMBOL),
                    '',
                    $formatter->getTextAttribute(\NumberFormatter::POSITIVE_SUFFIX)
                );
            case self::NEGATIVE_PREFIX:
                // FIXME? remove currency symbol from pattern is needed
                return str_replace(
                    $formatter->getSymbol(\NumberFormatter::CURRENCY_SYMBOL),
                    '',
                    $formatter->getTextAttribute(\NumberFormatter::NEGATIVE_PREFIX)
                );
            case self::NEGATIVE_SUFFIX:
                // FIXME? remove currency symbol from pattern is needed
                return str_replace(
                    $formatter->getSymbol(\NumberFormatter::CURRENCY_SYMBOL),
                    '',
                    $formatter->getTextAttribute(\NumberFormatter::NEGATIVE_SUFFIX)
                );
            case self::FRACTION_DIGITS:
                return $this->formatter->getAttribute(\NumberFormatter::FRACTION_DIGITS);
        }

        throw new I18nException\InvalidArgumentException('Invalid symbol');
    }

    /**
     * Get all the regex components
     *
     * @return array
     */
    public function getRegexComponents()
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
     * Retrieve single regex component by its constant identifier
     *
     * @param int $regexComponent
     * @return string
     */
    public function getRegexComponent($regexComponent)
    {
        $regexComponents = $this->getRegexComponents();

        if (!isset($regexComponents[$regexComponent])) {
            throw new I18nException\InvalidArgumentException(
                sprintf(
                    'Regex component not found; received "%s"',
                    $regexComponent
                )
            );
        }

        return $regexComponents[$regexComponent];
    }

    /**
     * Count the number of decimals that $number contains
     *
     * @param string $number
     * @param string $separatorSymbol
     * @param string $currencySymbol
     * @return int
     */
    protected function countDecimalDigits($number, $separatorSymbol, $currencySymbol)
    {
        // Remove currency symbol (if any) from string
        $number = str_replace($currencySymbol, '', $number);
        // Retrieve last occurence of monetary separator symbol
        $lastOccurence = grapheme_strrpos($number, $separatorSymbol);
        if ($lastOccurence === false) {
            return 0;
        }
        $decimals = grapheme_substr($number, $lastOccurence + 1);
        return preg_match_all(
            sprintf(
                '#%s#%s',
                $this->getRegexComponent(self::REGEX_NUMBERS),
                $this->getRegexComponent(self::REGEX_FLAGS)
            ),
            $decimals
        );
    }

    /**
     * Retrieve the currency symbol of the given locale anche currency code.
     *
     * @param $locale
     * @param $currencyCode
     * @return string
     */
    protected function getFirstCurrencySymbol($locale, $currencyCode)
    {

        $currencySymbol = null;
        $parent = null;

        // Check first in passed locale
        $currencyResources = \ResourceBundle::create($locale, 'ICUDATA-curr', false);
        if ($currencyResources instanceof \ResourceBundle) {
            $currencySymbols = $currencyResources->get('Currencies');
            $parent = $currencyResources->get('%%Parent');
            if ($currencySymbols instanceof \ResourceBundle) {
                $currencyCodeSymbols = $currencySymbols->get($this->getCurrencyCode());
                if ($currencyCodeSymbols instanceof \ResourceBundle) {
                    if ($currencySymbol = $currencyCodeSymbols->get(0)) {
                        return $currencySymbol; // found
                    }
                }
            }
        }

        // If root, no other fallbacks are available. Return the ISO currency code as default.
        if ($locale === 'root') {
            return $currencyCode;
        }

        // If any, check in parent
        if ($parent) {
            $currencyResources = \ResourceBundle::create($parent, 'ICUDATA-curr', false);
            if ($currencyResources instanceof \ResourceBundle) {
                $currencySymbols = $currencyResources->get('Currencies');
                if ($currencySymbols instanceof \ResourceBundle) {
                    $currencyCodeSymbols = $currencySymbols->get($this->getCurrencyCode());
                    if ($currencyCodeSymbols instanceof \ResourceBundle) {
                        if ($currencySymbol = $currencyCodeSymbols->get(0)) {
                            return $currencySymbol; // Found
                        }
                    }
                }
            }

            // If root, no other fallbacks are available. Return the ISO currency code as default.
            if ($parent === 'root') {
                return $currencyCode;
            }
        }

        // Fallback locale up to root
        if (strpos($locale, '_') !== false) {
            $locale = explode('_', $locale);
            array_pop($locale);
            $locale = implode('_', $locale);
        } else {
            $locale = 'root';
        }

        return $this->getFirstCurrencySymbol($locale, $currencyCode);
    }
}
