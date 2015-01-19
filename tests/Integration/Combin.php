<?php
/**
 * MoneyLaundry
 *
 * @link        https://github.com/leodido/moneylaundry
 * @copyright   Copyright (c) 2015, Leo Di Donato
 * @license     http://opensource.org/licenses/MIT      MIT license
 */
namespace MoneyLaundryTest\Integration;

/**
 * Class Combin
 */
class Combin
{
    /**
     * Generate all combinations of a specific size
     *
     * @param array $chars          The character to combine
     * @param int $n                The size
     * @param array $combinations   Previous combinations
     * @return array
     */
    public static function combn(array $chars, $n, array $combinations = [])
    {
        // if it's the first iteration, the first set of combinations is the same as the set of characters
        if (empty($combinations)) {
            $combinations = $chars;
        }
        // we're done if we're at size 1
        if ($n == 1) {
            return $combinations;
        }
        // initialise array to put new values in
        $new_combinations = [];
        // loop through existing combinations and character set to create strings
        foreach ($combinations as $combination) {
            foreach ($chars as $char) {
                $new_combinations[] = $combination . $char;
            }
        }
        // call same function again for the next iteration
        return self::combn($chars, $n - 1, $new_combinations);
    }
}
