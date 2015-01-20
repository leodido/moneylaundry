<?php
/**
 * MoneyLaundry
 *
 * @link        https://github.com/leodido/moneylaundry
 * @copyright   Copyright (c) 2015, Leo Di Donato
 * @license     http://opensource.org/licenses/MIT      MIT license
 */
namespace MoneyLaundry\Filter;

use MoneyLaundry\Validator\ScientificNotation;
use Zend\I18n\Filter\AbstractLocale;
use Zend\I18n\Filter\NumberFormat;

/**
 * Class Currency
 *
 * Given an integer and a locale it returns the corresponding well-formatted currency amount.
 * TODO: finish
 */
class Currency extends AbstractLocale
{
    /**
     * @var array
     */
    protected $options = [
        'locale' => 'it_IT', // FIXME: default to null, set via constructor and setter
    ];

    /**
     * @var \NumberFormatter
     */
    protected $formatter = null;

    /**
     * TODO: docs
     * @param mixed $value
     * @return mixed
     */
    public function filter($value)
    {
        if (!is_scalar($value) || is_bool($value)) {
            return $value;
        }
        // TODO: exclude number expressed with scientific notation

        if (is_float($value) || is_int($value)) {
            if (is_nan($value)) {
                return $value;
            }
            $formatter = new NumberFormat($this->getLocale(), \NumberFormatter::CURRENCY);
            return $formatter->filter($value);
        }

        // Check it is not a numeric written in scientific notation
        $validator = new ScientificNotation(['locale' => $this->getLocale()]);
        if ($validator->isValid($value)) {
            return $value;
        }

        // From string to number
        $formatter = new NumberFormat($this->getLocale(), \NumberFormatter::DECIMAL);
        $decimal = $formatter->filter($value);
        if ($decimal === $value) {
            return $value;
        }
        // From number to locale formatted string
        $formatter = new NumberFormat($this->getLocale(), \NumberFormatter::CURRENCY);
        return $formatter->filter($decimal);
    }
}
