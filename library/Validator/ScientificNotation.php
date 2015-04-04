<?php
namespace MoneyLaundry\Validator;

use Zend\I18n\Exception as I18nException;
use Zend\I18n\Validator\Float;
use Zend\Stdlib\ArrayUtils;
use Zend\Stdlib\StringUtils;
use Zend\Validator\AbstractValidator;

/**
 * Class ScientificNotation
 *
 * Validates the input as a valid number expressed in scientific notation for the given locale.
 */
class ScientificNotation extends AbstractValidator
{
    const INVALID_INPUT = 'invalid_input';
    const NOT_SCIENTIFIC = 'not_scientific';
    const NOT_NUMBER = 'not_number';

    /**
     * @var array
     */
    protected $messageTemplates = [
        self::INVALID_INPUT => "Invalid input given: '%value%' is not a string",
        self::NOT_SCIENTIFIC => "The '%value%' value does not appear to be a number expressed in scientific notation",
        self::NOT_NUMBER => "The '%value%' value is not a valid number"
    ];

    /**
     * Constructor
     *
     * @param array|\Traversable $options
     * @throws I18nException\ExtensionNotLoadedException if ext/intl is not present
     */
    public function __construct($options = [])
    {
        // @codeCoverageIgnoreStart
        if (!extension_loaded('intl')) {
            throw new I18nException\ExtensionNotLoadedException(
                sprintf('%s component requires the intl PHP extension', __NAMESPACE__)
            );
        }
        // @codeCoverageIgnoreEnd
        if ($options instanceof \Traversable) {
            $options = ArrayUtils::iteratorToArray($options);
        }

        parent::__construct($options);
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
     * Returns true if and only if $value is a number correctly expressed with the scientific notation
     *
     * Note that it can only validate string inputs.
     *
     * @param mixed $value
     * @return bool
     */
    public function isValid($value)
    {
        if (!is_scalar($value) || is_bool($value)) {
            $this->error(self::INVALID_INPUT);
            return false;
        }

        $formatter = new \NumberFormatter($this->getLocale(), \NumberFormatter::SCIENTIFIC);
        $flags = 'i';
        $expSymbol = 'E';
        if (StringUtils::hasPcreUnicodeSupport()) {
            $expSymbol = preg_quote($formatter->getSymbol(\NumberFormatter::EXPONENTIAL_SYMBOL));
            $flags .= 'u';
        }

        // Check that exponentation symbol is present
        $search = str_replace("\xE2\x80\x8E", '', sprintf('/%s/%s', $expSymbol, $flags));
        $value = str_replace("\xE2\x80\x8E", '', $value);
        if (!preg_match($search, $value)) {
            $this->error(self::NOT_SCIENTIFIC);
            return false;
        }

        // Check that the number expressed in scientific notation is a valid number
        $float = new Float(['locale' => $this->getLocale()]);
        if (!$float->isValid($value)) {
            $this->error(self::NOT_NUMBER);
            return false;
        }

        return true;
    }
}
