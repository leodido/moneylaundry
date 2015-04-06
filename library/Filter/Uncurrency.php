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
        if ($localeOrOptions !== null) {
            if (static::isOptions($localeOrOptions)) {
                $this->setOptions($localeOrOptions);
            } else {
                $this->setLocale($localeOrOptions);
                $this->setCurrencyCode($currencyCode);
                $this->setScaleCorrectness($scaleCorrectness);
                $this->setCurrencyObligatoriness($currencyObligatoriness);
            }
        }
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
     * The fraction digits have to be spiecified and exact?
     *
     * @return bool
     */
    public function getScaleCorrectness()
    {
        return $this->options['scale_correctness'];
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
            // Initialization
            $formatter = $this->getFormatter();


            // Disable scientific notation
            $formatter->setSymbol(\NumberFormatter::EXPONENTIAL_SYMBOL, null);
            // Retrieve symbols
            $symbols = $this->getSymbols(); // FIXME: parse and parseCurrancy could use different symbols
                                            // when used with non default currency code

            // Store original value
            $unfilteredValue = $value;

            // Replace spaces with NBSP (non breaking spaces)
            $value = str_replace("\x20", "\xC2\xA0", $value);

            // Get decimal place info
            // FIXME: parse and parseCurrancy could use different symbols
            // when used with non default currency code
            $numFractionDigits = $symbols[self::FRACTION_DIGITS];
            $numDecimals = $this->countDecimalDigits($value, $symbols[self::SEPARATOR_SYMBOL]);

            // Parse as currency
            ErrorHandler::start();
            $position = 0;
            /*
             * parseCurrency MODE
             *
             * The following parsing mode can work with a multiple currencies.
             * TODO: could be usefull if
             * Also it should be more strict and faster than parseCurrency getCurrencyObligatoriness() == false
             */
//            $result = $formatter->parseCurrency($value, $resultCurrencyCode, $position);

            /*
             * parse MODE
             *
             * The following parsing mode can work with a predefined currency code ONLY.
             * Also it should be more strict and faster than parseCurrency
             */
            $resultCurrencyCode = $this->getCurrencyCodeOrDefault();
            $formatter->setTextAttribute(\NumberFormatter::CURRENCY_CODE, $resultCurrencyCode);
            $result = $formatter->parse($value, \NumberFormatter::TYPE_DOUBLE, $position);

            // Input is a valid currency and the result is within the codomain?
            if ($result !== false && ((is_float($result) && !is_infinite($result) && !is_nan($result)))) {
                ErrorHandler::stop();

                // Check that detect currency matches with specified currency
                // NOT used with 'parse MODE'
//                 if ($resultCurrencyCode !== $this->getCurrencyCodeOrDefault()) {
//                     return $unfilteredValue;
//                 }

                // Check if the parsing finished before the end of the input
                if ($position !== grapheme_strlen($value)) {
                    return $unfilteredValue;
                }

                /*
                // FIXME: currenty, it doesn't work because parse and parseCurrancy could use different symbols
                // Check if the number of decimal digits match the requirement (unless the result is not finite)
                if ($this->getScaleCorrectness() && ($numDecimals !== $numFractionDigits && is_finite($result))) {
                    return $unfilteredValue;
                }
                */

                return $result;
            }

            // At this stage result is FALSE and input probably is not a well-formatted currency

            // Check if the currency symbol is mandatory (assiming 'parse MODE')
            if ($this->getCurrencyObligatoriness()) {
                ErrorHandler::stop();
                return $unfilteredValue;
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
            );

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

        return $value;
    }

    /**
     * Get all the symbols
     *
     * @return array
     */
    public function getSymbols()
    {
        if (!$this->formatter) {
            throw new I18nException\RuntimeException('An instance of NumberFormatted is required.');
        }

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

        return $symbols;
    }

    /**
     * Retrieve single symbol by its constant identifier
     *
     * @param int $symbol
     * @return string
     */
    public function getSymbol($symbol)
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
     * @param string $separatorSymbol
     * @return int
     */
    protected function countDecimalDigits($number, $separatorSymbol)
    {
        // FIXME: double check decimal extraction logic
        $lastOccurence = grapheme_strrpos($number, $separatorSymbol);
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
}
