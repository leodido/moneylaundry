<?php
namespace MoneyLaundryIntegrationTest\Validator;

use MoneyLaundryIntegrationTest\AbstractIntegration;
use MoneyLaundry\Validator\ScientificNotation as ScientificNotationValidator;
use SphinxSearch\Tool\Source\Writer\TSV;

/**
 * Class ScientificNotationTest
 */
class ScientificNotationTest extends AbstractIntegration
{
    /**
     * {@inheritdoc}
     */
    protected static $header = [
        'Locale', 'Value', 'Scientific Notation'
    ];

    /**
     * @var ScientificNotationValidator
     */
    protected $validator;

    /**
     * @var array
     */
    protected $values = [1000, -2000, +398.00, 0.04, -0.5, .6, -.70, 8E10, -9.3456E-2, 10.23E6, 123.12345678909876];

    /**
     * {@inheritdoc}
     */
    public function __construct($name = null, array $data = [], $dataName = '')
    {
        // FIXME: excluded locales do not work
        $this->excludeLocales([
                'lo_LA'
        ]);

        parent::__construct($name, $data, $dataName);
    }

    public function setUp()
    {
        $this->validator = new ScientificNotationValidator;
    }

    /**
     * @param $expected
     * @param $value
     * @param $locale
     * @dataProvider valuesProvider
     */
    public function testAllValues($expected, $value, $locale)
    {
        $this->validator->setLocale($locale);

        $this->assertEquals(
            $expected,
            $this->validator->isValid($value),
            sprintf(
                "'Failed expecting '%s' being %s (locale: %s, type: %s)",
                $value,
                $expected ? 'TRUE' : 'FALSE',
                $locale,
                gettype($value)
            )
        );

        self::writeData(
            [
                'locale' => $locale,
                'value' => $value,
                'valid' => $expected ? 'true' : 'false'
            ]
        );
    }

    /**
     * @return array
     */
    public function valuesProvider()
    {
        $data = [];
        foreach ($this->locales as $locale) {
            foreach ($this->values as $i) {
                $e = \NumberFormatter::create($locale, \NumberFormatter::SCIENTIFIC)
                    ->format($i, \NumberFormatter::TYPE_DEFAULT);
                $n = \NumberFormatter::create($locale, \NumberFormatter::DECIMAL)
                    ->format($i, \NumberFormatter::TYPE_DEFAULT);

                $data[] = [true, $e, $locale];
                $data[] = [false, $n, $locale];
            }
        }

        return $data;
    }
}
