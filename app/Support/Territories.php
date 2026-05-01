<?php

namespace App\Support;

class Territories
{
    /** @var array<string, string> */
    public const US = [
        'US-AL' => 'Alabama',
        'US-AK' => 'Alaska',
        'US-AZ' => 'Arizona',
        'US-AR' => 'Arkansas',
        'US-CA' => 'California',
        'US-CO' => 'Colorado',
        'US-CT' => 'Connecticut',
        'US-DE' => 'Delaware',
        'US-DC' => 'District of Columbia',
        'US-FL' => 'Florida',
        'US-GA' => 'Georgia',
        'US-HI' => 'Hawaii',
        'US-ID' => 'Idaho',
        'US-IL' => 'Illinois',
        'US-IN' => 'Indiana',
        'US-IA' => 'Iowa',
        'US-KS' => 'Kansas',
        'US-KY' => 'Kentucky',
        'US-LA' => 'Louisiana',
        'US-ME' => 'Maine',
        'US-MD' => 'Maryland',
        'US-MA' => 'Massachusetts',
        'US-MI' => 'Michigan',
        'US-MN' => 'Minnesota',
        'US-MS' => 'Mississippi',
        'US-MO' => 'Missouri',
        'US-MT' => 'Montana',
        'US-NE' => 'Nebraska',
        'US-NV' => 'Nevada',
        'US-NH' => 'New Hampshire',
        'US-NJ' => 'New Jersey',
        'US-NM' => 'New Mexico',
        'US-NY' => 'New York',
        'US-NC' => 'North Carolina',
        'US-ND' => 'North Dakota',
        'US-OH' => 'Ohio',
        'US-OK' => 'Oklahoma',
        'US-OR' => 'Oregon',
        'US-PA' => 'Pennsylvania',
        'US-RI' => 'Rhode Island',
        'US-SC' => 'South Carolina',
        'US-SD' => 'South Dakota',
        'US-TN' => 'Tennessee',
        'US-TX' => 'Texas',
        'US-UT' => 'Utah',
        'US-VT' => 'Vermont',
        'US-VA' => 'Virginia',
        'US-WA' => 'Washington',
        'US-WV' => 'West Virginia',
        'US-WI' => 'Wisconsin',
        'US-WY' => 'Wyoming',
    ];

    /** @var array<string, string> */
    public const CANADA = [
        'CA-AB' => 'Alberta',
        'CA-BC' => 'British Columbia',
        'CA-MB' => 'Manitoba',
        'CA-NB' => 'New Brunswick',
        'CA-NL' => 'Newfoundland and Labrador',
        'CA-NS' => 'Nova Scotia',
        'CA-NT' => 'Northwest Territories',
        'CA-NU' => 'Nunavut',
        'CA-ON' => 'Ontario',
        'CA-PE' => 'Prince Edward Island',
        'CA-QC' => 'Quebec',
        'CA-SK' => 'Saskatchewan',
        'CA-YT' => 'Yukon',
    ];

    /** @var array<string, string> */
    public const REGIONS = [
        'REG-EMEA' => 'EMEA',
        'REG-APAC' => 'APAC',
    ];

    /**
     * @return array<string, string>
     */
    public static function all(): array
    {
        return self::US + self::CANADA + self::REGIONS;
    }

    /**
     * @return array<string, string>
     */
    public static function choices(): array
    {
        return self::all();
    }

    public static function name(string $code): string
    {
        return self::all()[strtoupper($code)] ?? strtoupper($code);
    }

    public static function normalize(string $code): string
    {
        $code = strtoupper(trim($code));

        if (isset(self::US[$code]) || isset(self::CANADA[$code]) || isset(self::REGIONS[$code])) {
            return $code;
        }

        $legacyUsCode = 'US-'.$code;

        if (strlen($code) === 2 && isset(self::US[$legacyUsCode])) {
            return $legacyUsCode;
        }

        return $code;
    }

    public static function isValid(string $code): bool
    {
        return isset(self::all()[self::normalize($code)]);
    }
}
