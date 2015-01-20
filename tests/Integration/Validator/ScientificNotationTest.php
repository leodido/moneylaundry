<?php
namespace MoneyLaundryTest\Integration\Validator;

use MoneyLaundry\Validator\ScientificNotation as ScientificNotationValidator;
use SphinxSearch\Tool\Source\Writer\TSV;

/**
 * Class ScientificNotationTest
 *
 * @group integration
 */
class ScientificNotationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ScientificNotationValidator
     */
    protected $validator;

    /**
     * @var array
     */
    protected $values = [1000, -2000, +398.00, 0.04, -0.5, .6, -.70, 8E10, -9.3456E-2, 10.23E6, 123.12345678909876];

    /**
     * @var array
     */
    protected $locales = [
        'af_ZA',
        'am_ET',
        'ar_AE',
        'ar_BH',
        'ar_DZ',
        'ar_EG',
        'ar_IQ',
        'ar_JO',
        'ar_KW',
        'ar_LB',
        'ar_LY',
        'ar_MA',
        'arn_CL',
        'ar_OM',
        'ar_QA',
        'ar_SA',
        'ar_SY',
        'ar_TN',
        'ar_YE',
        'as_IN',
        'az_Cyrl_AZ',
        'az_Latn_AZ',
        'ba_RU',
        'be_BY',
        'bg_BG',
        'bn_BD',
        'bn_IN',
        'bo_CN',
        'br_FR',
        'bs_Cyrl_BA',
        'bs_Latn_BA',
        'ca_ES',
        'co_FR',
        'cs_CZ',
        'cy_GB',
        'da_DK',
        'de_AT',
        'de_CH',
        'de_DE',
        'de_LI',
        'de_LU',
        'dsb_DE',
        'dv_MV',
        'el_GR',
        'en_029',
        'en_AU',
        'en_BZ',
        'en_CA',
        'en_GB',
        'en_IE',
        'en_IN',
        'en_JM',
        'en_MY',
        'en_NZ',
        'en_PH',
        'en_SG',
        'en_TT',
        'en_US',
        'en_ZA',
        'en_ZW',
        'es_AR',
        'es_BO',
        'es_CL',
        'es_CO',
        'es_CR',
        'es_DO',
        'es_EC',
        'es_ES',
        'es_GT',
        'es_HN',
        'es_MX',
        'es_NI',
        'es_PA',
        'es_PE',
        'es_PR',
        'es_PY',
        'es_SV',
        'es_US',
        'es_UY',
        'es_VE',
        'et_EE',
        'eu_ES',
        'fa_IR',
        'fi_FI',
        'fil_PH',
        'fo_FO',
        'fr_BE',
        'fr_CA',
        'fr_CH',
        'fr_FR',
        'fr_LU',
        'fr_MC',
        'fy_NL',
        'ga_IE',
        'gd_GB',
        'gl_ES',
        'gsw_FR',
        'gu_IN',
        'ha_Latn_NG',
        'he_IL',
        'hi_IN',
        'hr_BA',
        'hr_HR',
        'hsb_DE',
        'hu_HU',
        'hy_AM',
        'id_ID',
        'ig_NG',
        'ii_CN',
        'is_IS',
        'it_CH',
        'it_IT',
        'iu_Cans_CA',
        'iu_Latn_CA',
        'ja_JP',
        'ka_GE',
        'kk_KZ',
        'kl_GL',
        'km_KH',
        'kn_IN',
        'kok_IN',
        'ko_KR',
        'ky_KG',
        'lb_LU',
//        'lo_LA', // NOTE: appear to not have scientific notation
        'lt_LT',
        'lv_LV',
        'mi_NZ',
        'mk_MK',
        'ml_IN',
        'mn_MN',
        'mn_Mong_CN',
        'moh_CA',
        'mr_IN',
        'ms_BN',
        'ms_MY',
        'mt_MT',
        'nb_NO',
        'ne_NP',
        'nl_BE',
        'nl_NL',
        'nn_NO',
        'nso_ZA',
        'oc_FR',
        'or_IN',
        'pa_IN',
        'pl_PL',
        'prs_AF',
        'ps_AF',
        'pt_BR',
        'pt_PT',
        'qut_GT',
        'quz_BO',
        'quz_EC',
        'quz_PE',
        'rm_CH',
        'ro_RO',
        'ru_RU',
        'rw_RW',
        'sah_RU',
        'sa_IN',
        'se_FI',
        'se_NO',
        'se_SE',
        'si_LK',
        'sk_SK',
        'sl_SI',
        'sma_NO',
        'sma_SE',
        'smj_NO',
        'smj_SE',
        'smn_FI',
        'sms_FI',
        'sq_AL',
        'sr_Cyrl_BA',
        'sr_Cyrl_CS',
        'sr_Cyrl_ME',
        'sr_Cyrl_RS',
        'sr_Latn_BA',
        'sr_Latn_CS',
        'sr_Latn_ME',
        'sr_Latn_RS',
        'sv_FI',
        'sv_SE',
        'sw_KE',
        'syr_SY',
        'ta_IN',
        'te_IN',
        'tg_Cyrl_TJ',
        'th_TH',
        'tk_TM',
        'tn_ZA',
        'tr_TR',
        'tt_RU',
        'tzm_Latn_DZ',
        'ug_CN',
        'uk_UA',
        'ur_PK',
        'uz_Cyrl_UZ',
        'uz_Latn_UZ',
        'vi_VN',
        'wo_SN',
        'xh_ZA',
        'yo_NG',
        'zh_CN',
        'zh_HK',
        'zh_MO',
        'zh_SG',
        'zh_TW',
        'zu_ZA',
    ];

    /**
     * @var TSV
     */
    protected static $writer;

    /**
     * @var int
     */
    protected static $line = 0;

    public function setUp()
    {
        $this->validator = new ScientificNotationValidator;
    }

    public static function setUpBeforeClass()
    {
        self::$writer = new TSV();
        self::$writer->openURI('../data/scientificnotation.tsv');
        self::$writer->beginOutput();
        self::$writer->addDocument(['id' => 'ID', 'locale' => 'Locale', 'value' => 'Value', 'Scientific Notation']);
    }

    public static function tearDownAfterClass()
    {
        self::$writer->endOutput();
    }

    /**
     * @param $expected
     * @param $value
     * @param $locale
     * @dataProvider valuesProvider
     */
    public function testValues($expected, $value, $locale)
    {
        $this->validator->setLocale($locale);

        self::$writer->addDocument(
            [
                'id' => self::$line,
                'locale' => $locale,
                'value' => $value,
                'valid' => $expected ? 'true' : 'false'
            ]
        );

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

        self::$line++;
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
