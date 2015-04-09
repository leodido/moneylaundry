<?php
/**
 * MoneyLaundry
 *
 * @link        https://github.com/leodido/moneylaundry
 * @copyright   Copyright (c) 2015, Leo Di Donato
 * @license     http://opensource.org/licenses/MIT      MIT license
 */
if (!file_exists('vendor/autoload.php')) {
    throw new \RuntimeException('vendor/autoload.php not found. Run a composer install.');
}

$GLOBALS['INTL_EXT_VERSION'] = phpversion('intl');
$GLOBALS['INTL_ICU_VERSION'] = constant('INTL_ICU_VERSION');

echo 'Settings:' . PHP_EOL;
echo sprintf('intl: %s', $GLOBALS['INTL_EXT_VERSION']) . PHP_EOL;
echo sprintf('icu: %s', $GLOBALS['INTL_ICU_VERSION']) . PHP_EOL;
echo PHP_EOL;
