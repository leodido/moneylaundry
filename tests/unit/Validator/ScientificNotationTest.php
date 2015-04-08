<?php
namespace MoneyLaundryUnitTest\Validator;

use MoneyLaundry\Validator\ScientificNotation as ScientificNotationValidator;
use Zend\Stdlib\ArrayObject;

/**
 * Class ScientificNotationTest
 * @group validators
 */
class ScientificNotationTest extends \PHPUnit_Framework_TestCase
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

    public function testConstructor()
    {
        $validator = new ScientificNotationValidator;
        $this->assertEquals('en_US', $validator->getLocale());

        $validator = new ScientificNotationValidator(['locale' => 'it_IT']);
        $this->assertEquals('it_IT', $validator->getLocale());

        $validator = new ScientificNotationValidator(new ArrayObject(['locale' => 'de_DE']));
        $this->assertEquals('de_DE', $validator->getLocale());
    }

    public function testSetLocaleOption()
    {
        $v = new ScientificNotationValidator;
        $this->assertInstanceOf('MoneyLaundry\Validator\ScientificNotation', $v->setLocale('it_IT'));
        $this->assertEquals($v->getLocale(), 'it_IT');
    }

    public function testValidation()
    {
        $validator = new ScientificNotationValidator('it_IT');

        $this->assertTrue($validator->isValid('1E3'));
        $this->assertTrue($validator->isValid('1e-3'));
        $this->assertTrue($validator->isValid('1e+3'));
        $this->assertTrue($validator->isValid('1E+3'));
        $this->assertTrue($validator->isValid('1.5E10'));
        $this->assertTrue($validator->isValid('.5E10'));

        $this->assertFalse($validator->isValid('.5E10 ABC'));
        $this->assertFalse($validator->isValid('1E+3 ABC'));
        $this->assertFalse($validator->isValid('1AE+3'));
        $this->assertFalse($validator->isValid(1E3));
        $this->assertFalse($validator->isValid(1000));
        $this->assertFalse($validator->isValid(NAN));
        $this->assertFalse($validator->isValid(0.5));
        $this->assertFalse($validator->isValid([]));
    }
}
