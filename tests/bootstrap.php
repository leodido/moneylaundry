<?php
/**
 * MoneyLaundry
 *
 * @link        https://github.com/leodido/moneylaundry
 * @copyright   Copyright (c) 2015, Leo Di Donato
 * @license     http://opensource.org/licenses/MIT      MIT license
 */
chdir(__DIR__);
if (!file_exists('../vendor/autoload.php')) {
    throw new \RuntimeException('vendor/autoload.php not found. Run a composer install.');
}

echo 'Settings:' . PHP_EOL;
echo sprintf('intl: %s', phpversion('intl')) . PHP_EOL;
echo sprintf('icu: %s', constant('INTL_ICU_VERSION')) . PHP_EOL;
echo sprintf('LC_MONETARY: %s', setlocale(LC_MONETARY, '0')) . PHP_EOL;
echo sprintf('LC_NUMERIC: %s', setlocale(LC_NUMERIC, '0')) . PHP_EOL;
echo PHP_EOL;
