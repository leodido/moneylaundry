<?php
/**
 * MoneyLaundry
 *
 * @link        https://github.com/leodido/moneylaundry
 * @copyright   Copyright (c) 2015, Leo Di Donato
 * @license     http://opensource.org/licenses/MIT      MIT license
 */
namespace MoneyLaundryUnitTest;

/**
 * Class AbstractTest
 */
abstract class AbstractTest extends \PHPUnit_Framework_TestCase
{
    protected $defaultLocale;

    public function setUp()
    {
        if (!extension_loaded('intl')) {
            $this->markTestSkipped('The intl extension is not installed/enabled');
        }
        //
        $this->defaultLocale = ini_get('intl.default_locale');
        ini_set('intl.default_locale', 'en_US');
    }

    public function tearDown()
    {
        ini_set('intl.default_locale', $this->defaultLocale);
    }
}