<?php
/**
 * MoneyLaundry
 *
 * @link        https://github.com/leodido/moneylaundry
 * @copyright   Copyright (c) 2015, Leo Di Donato
 * @license     http://opensource.org/licenses/MIT      MIT license
 */
namespace MoneyLaundryUnitTest\Filter;

use MoneyLaundryUnitTest\AbstractTest;

/**
 * Class AbstractFilter
 */
class AbstractFilter extends AbstractTest
{

    public function testSetFormatter()
    {
        // Test that setFormatter() updates locale (calling <numberformatter>::getLocale(\Locale::VALID_LOCALE))
        /** @var $mock \MoneyLaundry\Filter\AbstractFilter */
        $mock = $this->getMockForAbstractClass('MoneyLaundry\Filter\AbstractFilter');

        $formatterMock = $this->getMockBuilder('\NumberFormatter')
                              ->disableOriginalConstructor()
                              ->getMock();

        $formatterMock->expects($this->once())
                      ->method('getLocale')
                      ->with($this->equalTo(\Locale::VALID_LOCALE));

        /** @var $f \NumberFormatter */
        $f = $formatterMock;

        $this->assertInstanceOf('MoneyLaundry\Filter\AbstractFilter', $mock->setFormatter($f));
        $this->assertSame($f, $mock->getFormatter());
    }

    public function testGetFormatter()
    {
        $m = $this->getMockBuilder('MoneyLaundry\Filter\AbstractFilter')
                  ->setMethods(['setFormatter'])
                  ->getMockForAbstractClass();

        $m->expects($this->once())
            ->method('setFormatter')
            ->with($this->isInstanceOf('NumberFormatter'));

        $m->getFormatter();
    }

    public function testCurrencyCode()
    {
        $mock = $this->getMockBuilder('MoneyLaundry\Filter\AbstractFilter')
                     ->enableProxyingToOriginalMethods()
                     ->getMockForAbstractClass();

        $formatter = \NumberFormatter::create('it_IT', \NumberFormatter::CURRENCY);

        $mock->expects($this->any())
            ->method('getFormatter')
            ->will(
                $this->returnValue($formatter)
            );

        $this->assertNull($mock->getCurrencyCode());

        $mock->setLocale('it_IT');
        $mock->getFormatter();

        $this->assertSame(
            $mock->getCurrencyCode(),
            $formatter->getTextAttribute(\NumberFormatter::CURRENCY_CODE)
        );

        $mock->setCurrencyCode('USD');

        $this->assertSame(
            $mock->getCurrencyCode(),
            'USD'
        );
    }

    public function testScaleCorrectness()
    {
        $mock = $this->getMockBuilder('MoneyLaundry\Filter\AbstractFilter')
            ->enableProxyingToOriginalMethods()
            ->getMockForAbstractClass();
        $mock->setScaleCorrectness(false);
        $this->assertFalse($mock->getScaleCorrectness());
    }

    public function testCurrencyCorrectness()
    {
        $mock = $this->getMockBuilder('MoneyLaundry\Filter\AbstractFilter')
            ->enableProxyingToOriginalMethods()
            ->getMockForAbstractClass();
        $mock->setCurrencyCorrectness(false);
        $this->assertFalse($mock->getCurrencyCorrectness());
    }

    public function testIsFloatScalePrecise()
    {
        $reflMethod = new \ReflectionMethod('MoneyLaundry\Filter\AbstractFilter', 'isFloatScalePrecise');
        $reflMethod->setAccessible(true);

        $mock = $this->getMockBuilder('MoneyLaundry\Filter\AbstractFilter')
            ->enableProxyingToOriginalMethods()
            ->getMockForAbstractClass();

        $this->assertTrue($reflMethod->invokeArgs($mock, [1.25, 2]));
        $this->assertFalse($reflMethod->invokeArgs($mock, [1.255, 2]));

        $reflMethod->setAccessible(false);
    }

    public function testSetupCurrencyCode()
    {
        $reflMethod = new \ReflectionMethod('MoneyLaundry\Filter\AbstractFilter', 'setupCurrencyCode');
        $reflMethod->setAccessible(true);

        $mock = $this->getMockBuilder('MoneyLaundry\Filter\AbstractFilter')
            ->enableProxyingToOriginalMethods()
            ->setMethods(['getFormatter', 'getCurrencyCode'])
            ->getMockForAbstractClass();

        $formatter = \NumberFormatter::create('it_IT', \NumberFormatter::CURRENCY);

        $mock->expects($this->at(0))
             ->method('getFormatter')
             ->willReturn($formatter);

        $mock->expects($this->at(1))
             ->method('getCurrencyCode')
             ->willReturn($formatter->getTextAttribute(\NumberFormatter::CURRENCY_CODE));

        $this->assertSame(
            $formatter->getTextAttribute(\NumberFormatter::CURRENCY_CODE),
            $reflMethod->invoke($mock)
        );

        $reflMethod->setAccessible(false);
    }
}
