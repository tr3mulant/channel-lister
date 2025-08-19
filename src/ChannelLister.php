<?php

declare(strict_types=1);

namespace IGE\ChannelLister;

use IGE\ChannelLister\Enums\InputType;
use IGE\ChannelLister\Models\ChannelListerField;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;

class ChannelLister
{
    /**
     * Get the CSS for the Telescope dashboard.
     */
    public static function css(): Htmlable
    {
        if (($app = @file_get_contents(__DIR__.'/../resources/css/styles.css')) === false) {
            throw new \RuntimeException('Unable to load the CSS styles.');
        }

        return new HtmlString(<<<HTML
            <style>{$app}</style>
        HTML);
    }

    /**
     * Builds a valid UPC code.
     *
     * @param  string  $upc_start  Optional starting point for UPC, must be under 12 characters
     * @return string the newly generated UPC
     */
    public static function createUpc(string $upc_start = ''): string
    {
        if (strlen($upc_start) > 11) {
            throw new \Exception("'$upc_start' too long, expecting a string or int less than 12 characters in length");
        }

        // check to see if upc is only digits
        if (in_array(preg_match('/^\d*$/', $upc_start), [0, false], true)) {
            throw new \Exception("'$upc_start' must be only digits");
        }

        $upc = $upc_start;
        if (strlen($upc) < 1) {
            do {
                $upc = random_int(1, 8);
            } while ($upc == 2 || $upc == 3 || $upc == 4 || $upc == 5);
        }

        $upc = (string) $upc;

        while (strlen($upc) < 11) {
            $upc .= (string) random_int(0, 9);
        }

        $checkdigit = 3 * ((int) $upc[0] + (int) $upc[2] + (int) $upc[4] + (int) $upc[6] + (int) $upc[8] + (int) $upc[10]);
        $checkdigit += ((int) $upc[1] + (int) $upc[3] + (int) $upc[5] + (int) $upc[7] + (int) $upc[9]);
        $checkdigit = $checkdigit % 10 == 0 ? '0' : (string) (10 - $checkdigit % 10);

        return $upc .= $checkdigit;
    }

    /**
     * Get all of the purchased UPC prefixes.
     *
     * @return array<string>
     */
    public static function getPurchasedUpcPrefixes(): array
    {
        /** @var array<array{prefix: string, name: string, purchased?: bool}> $prefixes */
        $prefixes = config('channel-lister.upc_prefixes', []);

        $purchased = array_filter($prefixes, fn (array $prefix): bool => $prefix['purchased'] ?? false);

        return array_map(
            fn (array $prefix): string => $prefix['prefix'],
            $purchased
        );
    }

    /**
     * Check if a UPC prefix is purchased.
     */
    public static function isPurchasedUpcPrefix(string $prefix): bool
    {
        return in_array($prefix, self::getPurchasedUpcPrefixes(), true);
    }

    /**
     * Get the owner information for a purchased UPC prefix.
     *
     * @param  string  $prefix  The UPC prefix to look up
     * @return string|null The owner name, or null if prefix not found or not purchased
     */
    public static function getNameByPrefix(string $prefix): ?string
    {
        /** @var array<array{prefix: string, name: string, purchased?: bool}> $prefixConfig */
        $prefixConfig = config('channel-lister.upc_prefixes', []);

        $upcDefinitions = array_filter($prefixConfig, fn (array $upcDefinition): bool => $upcDefinition['prefix'] === $prefix);

        if ($upcDefinitions === []) {
            return null;
        }

        $upcDefinition = array_pop($upcDefinitions);

        return $upcDefinition['name'];
    }

    /**
     * Check if a UPC code is valid (passes checksum validation).
     */
    public static function isValidUpc(string $upc): bool
    {
        if (strlen($upc) !== 12) {
            return false;
        }

        if (in_array(preg_match('/^\d{12}$/', $upc), [0, false], true)) {
            return false;
        }

        $checkdigit = 3 * ((int) $upc[0] + (int) $upc[2] + (int) $upc[4] + (int) $upc[6] + (int) $upc[8] + (int) $upc[10]);
        $checkdigit += ((int) $upc[1] + (int) $upc[3] + (int) $upc[5] + (int) $upc[7] + (int) $upc[9]);
        $expectedCheckDigit = $checkdigit % 10 == 0 ? 0 : (10 - $checkdigit % 10);

        return (int) $upc[11] === $expectedCheckDigit;
    }

    /**
     * Maps lowercase marketplace names to form used in labels
     *
     * @param  string  $marketplace  lowercase name of marketplace
     * @return string Marketplace formatted as used in label
     */
    public static function marketplaceDisplayName($marketplace): string
    {
        return match ($marketplace) {
            'amazon', 'amazon-us', 'amazon_us' => 'Amazon US',
            'amazon-ca', 'amazon_ca' => 'Amazon CA',
            'amazon-au', 'amazon_au' => 'Amazon AU',
            'amazon-mx', 'amazon_mx' => 'Amazon MX',
            'ebay' => 'eBay',
            'walmart', 'walmart-us', 'walmart_us' => 'Walmart US',
            'walmart-ca', 'walmart_ca' => 'Walmart CA',
            default => ucwords(strtolower($marketplace)),
        };
    }

    /**
     * Get the disabled marketplaces from the configuration.
     *
     * @return string[] Array of disabled marketplace names
     */
    public static function disabledMarketplaces(): array
    {
        /** @var string[]|string $disabledMarketplaces */
        $disabledMarketplaces = config('channel-lister.marketplaces.disabled', []);

        return is_array($disabledMarketplaces) ? $disabledMarketplaces : [$disabledMarketplaces];
    }

    /**
     * Get country code (2-digit or 3-digit) for a given country name.
     */
    public static function getCountryCode(string $countryName, int $digits): ?string
    {
        $countryCodes = [
            'Afghanistan' => ['AF', 'AFG'],
            'Albania' => ['AL', 'ALB'],
            'Algeria' => ['DZ', 'DZA'],
            'American Samoa' => ['AS', 'ASM'],
            'Andorra' => ['AD', 'AND'],
            'Angola' => ['AO', 'AGO'],
            'Anguilla' => ['AI', 'AIA'],
            'Antarctica' => ['AQ', 'ATA'],
            'Antigua and Barbuda' => ['AG', 'ATG'],
            'Argentina' => ['AR', 'ARG'],
            'Armenia' => ['AM', 'ARM'],
            'Aruba' => ['AW', 'ABW'],
            'Australia' => ['AU', 'AUS'],
            'Austria' => ['AT', 'AUT'],
            'Azerbaijan' => ['AZ', 'AZE'],
            'Bahamas' => ['BS', 'BHS'],
            'Bahrain' => ['BH', 'BHR'],
            'Bangladesh' => ['BD', 'BGD'],
            'Barbados' => ['BB', 'BRB'],
            'Belarus' => ['BY', 'BLR'],
            'Belgium' => ['BE', 'BEL'],
            'Belize' => ['BZ', 'BLZ'],
            'Benin' => ['BJ', 'BEN'],
            'Bermuda' => ['BM', 'BMU'],
            'Bhutan' => ['BT', 'BTN'],
            'Bolivia' => ['BO', 'BOL'],
            'Bonaire' => ['BQ', 'BES'],
            'Bosnia and Herzegovina' => ['BA', 'BIH'],
            'Botswana' => ['BW', 'BWA'],
            'Bouvet Island' => ['BV', 'BVT'],
            'Brazil' => ['BR', 'BRA'],
            'British Indian Ocean Territory' => ['IO', 'IOT'],
            'Brunei Darussalam' => ['BN', 'BRN'],
            'Bulgaria' => ['BG', 'BGR'],
            'Burkina Faso' => ['BF', 'BFA'],
            'Burundi' => ['BI', 'BDI'],
            'Cambodia' => ['KH', 'KHM'],
            'Cameroon' => ['CM', 'CMR'],
            'Canada' => ['CA', 'CAN'],
            'Cape Verde' => ['CV', 'CPV'],
            'Cayman Islands' => ['KY', 'CYM'],
            'Central African Republic' => ['CF', 'CAF'],
            'Chad' => ['TD', 'TCD'],
            'Chile' => ['CL', 'CHL'],
            'China' => ['CN', 'CHN'],
            'Christmas Island' => ['CX', 'CXR'],
            'Cocos Islands' => ['CC', 'CCK'],
            'Colombia' => ['CO', 'COL'],
            'Comoros' => ['KM', 'COM'],
            'Congo' => ['CG', 'COG'],
            'Democratic Republic of the Congo' => ['CD', 'COD'],
            'Cook Islands' => ['CK', 'COK'],
            'Costa Rica' => ['CR', 'CRI'],
            'Croatia' => ['HR', 'HRV'],
            'Cuba' => ['CU', 'CUB'],
            'Curacao' => ['CW', 'CUW'],
            'Cyprus' => ['CY', 'CYP'],
            'Czech Republic' => ['CZ', 'CZE'],
            "Cote d'Ivoire" => ['CI', 'CIV'],
            'Denmark' => ['DK', 'DNK'],
            'Djibouti' => ['DJ', 'DJI'],
            'Dominica' => ['DM', 'DMA'],
            'Dominican Republic' => ['DO', 'DOM'],
            'Ecuador' => ['EC', 'ECU'],
            'Egypt' => ['EG', 'EGY'],
            'El Salvador' => ['SV', 'SLV'],
            'Equatorial Guinea' => ['GQ', 'GNQ'],
            'Eritrea' => ['ER', 'ERI'],
            'Estonia' => ['EE', 'EST'],
            'Ethiopia' => ['ET', 'ETH'],
            'Falkland Islands' => ['FK', 'FLK'],
            'Faroe Islands' => ['FO', 'FRO'],
            'Fiji' => ['FJ', 'FJI'],
            'Finland' => ['FI', 'FIN'],
            'France' => ['FR', 'FRA'],
            'French Guiana' => ['GF', 'GUF'],
            'French Polynesia' => ['PF', 'PYF'],
            'French Southern Territories' => ['TF', 'ATF'],
            'Gabon' => ['GA', 'GAB'],
            'Gambia' => ['GM', 'GMB'],
            'Georgia' => ['GE', 'GEO'],
            'Germany' => ['DE', 'DEU'],
            'Ghana' => ['GH', 'GHA'],
            'Gibraltar' => ['GI', 'GIB'],
            'Greece' => ['GR', 'GRC'],
            'Greenland' => ['GL', 'GRL'],
            'Grenada' => ['GD', 'GRD'],
            'Guadeloupe' => ['GP', 'GLP'],
            'Guam' => ['GU', 'GUM'],
            'Guatemala' => ['GT', 'GTM'],
            'Guernsey' => ['GG', 'GGY'],
            'Guinea' => ['GN', 'GIN'],
            'Guinea-Bissau' => ['GW', 'GNB'],
            'Guyana' => ['GY', 'GUY'],
            'Haiti' => ['HT', 'HTI'],
            'Heard Island and McDonald Mcdonald Islands' => ['HM', 'HMD'],
            'Holy See' => ['VA', 'VAT'],
            'Honduras' => ['HN', 'HND'],
            'Hong Kong' => ['HK', 'HKG'],
            'Hungary' => ['HU', 'HUN'],
            'Iceland' => ['IS', 'ISL'],
            'India' => ['IN', 'IND'],
            'Indonesia' => ['ID', 'IDN'],
            'Islamic Republic of Iran' => ['IR', 'IRN'],
            'Iraq' => ['IQ', 'IRQ'],
            'Ireland' => ['IE', 'IRL'],
            'Isle of Man' => ['IM', 'IMN'],
            'Israel' => ['IL', 'ISR'],
            'Italy' => ['IT', 'ITA'],
            'Jamaica' => ['JM', 'JAM'],
            'Japan' => ['JP', 'JPN'],
            'Jersey' => ['JE', 'JEY'],
            'Jordan' => ['JO', 'JOR'],
            'Kazakhstan' => ['KZ', 'KAZ'],
            'Kenya' => ['KE', 'KEN'],
            'Kiribati' => ['KI', 'KIR'],
            "Democratic People's Republic of Korea" => ['KP', 'PRK'],
            'Republic of Korea' => ['KR', 'KOR'],
            'Kuwait' => ['KW', 'KWT'],
            'Kyrgyzstan' => ['KG', 'KGZ'],
            "Lao People's Democratic Republic" => ['LA', 'LAO'],
            'Latvia' => ['LV', 'LVA'],
            'Lebanon' => ['LB', 'LBN'],
            'Lesotho' => ['LS', 'LSO'],
            'Liberia' => ['LR', 'LBR'],
            'Libya' => ['LY', 'LBY'],
            'Liechtenstein' => ['LI', 'LIE'],
            'Lithuania' => ['LT', 'LTU'],
            'Luxembourg' => ['LU', 'LUX'],
            'Macao' => ['MO', 'MAC'],
            'the Former Yugoslav Republic of Macedonia' => ['MK', 'MKD'],
            'Madagascar' => ['MG', 'MDG'],
            'Malawi' => ['MW', 'MWI'],
            'Malaysia' => ['MY', 'MYS'],
            'Maldives' => ['MV', 'MDV'],
            'Mali' => ['ML', 'MLI'],
            'Malta' => ['MT', 'MLT'],
            'Marshall Islands' => ['MH', 'MHL'],
            'Martinique' => ['MQ', 'MTQ'],
            'Mauritania' => ['MR', 'MRT'],
            'Mauritius' => ['MU', 'MUS'],
            'Mayotte' => ['YT', 'MYT'],
            'Mexico' => ['MX', 'MEX'],
            'Federated States of Micronesia' => ['FM', 'FSM'],
            'Republic of Moldova' => ['MD', 'MDA'],
            'Monaco' => ['MC', 'MCO'],
            'Mongolia' => ['MN', 'MNG'],
            'Montenegro' => ['ME', 'MNE'],
            'Montserrat' => ['MS', 'MSR'],
            'Morocco' => ['MA', 'MAR'],
            'Mozambique' => ['MZ', 'MOZ'],
            'Myanmar' => ['MM', 'MMR'],
            'Namibia' => ['NA', 'NAM'],
            'Nauru' => ['NR', 'NRU'],
            'Nepal' => ['NP', 'NPL'],
            'Netherlands' => ['NL', 'NLD'],
            'New Caledonia' => ['NC', 'NCL'],
            'New Zealand' => ['NZ', 'NZL'],
            'Nicaragua' => ['NI', 'NIC'],
            'Niger' => ['NE', 'NER'],
            'Nigeria' => ['NG', 'NGA'],
            'Niue' => ['NU', 'NIU'],
            'Norfolk Island' => ['NF', 'NFK'],
            'Northern Mariana Islands' => ['MP', 'MNP'],
            'Norway' => ['NO', 'NOR'],
            'Oman' => ['OM', 'OMN'],
            'Pakistan' => ['PK', 'PAK'],
            'Palau' => ['PW', 'PLW'],
            'State of Palestine' => ['PS', 'PSE'],
            'Panama' => ['PA', 'PAN'],
            'Papua New Guinea' => ['PG', 'PNG'],
            'Paraguay' => ['PY', 'PRY'],
            'Peru' => ['PE', 'PER'],
            'Philippines' => ['PH', 'PHL'],
            'Pitcairn' => ['PN', 'PCN'],
            'Poland' => ['PL', 'POL'],
            'Portugal' => ['PT', 'PRT'],
            'Puerto Rico' => ['PR', 'PRI'],
            'Qatar' => ['QA', 'QAT'],
            'Romania' => ['RO', 'ROU'],
            'Russian Federation' => ['RU', 'RUS'],
            'Rwanda' => ['RW', 'RWA'],
            'Reunion' => ['RE', 'REU'],
            'Saint Barthelemy' => ['BL', 'BLM'],
            'Saint Helena' => ['SH', 'SHN'],
            'Saint Kitts and Nevis' => ['KN', 'KNA'],
            'Saint Lucia' => ['LC', 'LCA'],
            'Saint Martin' => ['MF', 'MAF'],
            'Saint Pierre and Miquelon' => ['PM', 'SPM'],
            'Saint Vincent and the Grenadines' => ['VC', 'VCT'],
            'Samoa' => ['WS', 'WSM'],
            'San Marino' => ['SM', 'SMR'],
            'Sao Tome and Principe' => ['ST', 'STP'],
            'Saudi Arabia' => ['SA', 'SAU'],
            'Senegal' => ['SN', 'SEN'],
            'Serbia' => ['RS', 'SRB'],
            'Seychelles' => ['SC', 'SYC'],
            'Sierra Leone' => ['SL', 'SLE'],
            'Singapore' => ['SG', 'SGP'],
            'Sint Maarten' => ['SX', 'SXM'],
            'Slovakia' => ['SK', 'SVK'],
            'Slovenia' => ['SI', 'SVN'],
            'Solomon Islands' => ['SB', 'SLB'],
            'Somalia' => ['SO', 'SOM'],
            'South Africa' => ['ZA', 'ZAF'],
            'South Georgia and the South Sandwich Islands' => ['GS', 'SGS'],
            'South Sudan' => ['SS', 'SSD'],
            'Spain' => ['ES', 'ESP'],
            'Sri Lanka' => ['LK', 'LKA'],
            'Sudan' => ['SD', 'SDN'],
            'Suriname' => ['SR', 'SUR'],
            'Svalbard and Jan Mayen' => ['SJ', 'SJM'],
            'Swaziland' => ['SZ', 'SWZ'],
            'Sweden' => ['SE', 'SWE'],
            'Switzerland' => ['CH', 'CHE'],
            'Syrian Arab Republic' => ['SY', 'SYR'],
            'Taiwan' => ['TW', 'TWN'],
            'Tajikistan' => ['TJ', 'TJK'],
            'United Republic of Tanzania' => ['TZ', 'TZA'],
            'Thailand' => ['TH', 'THA'],
            'Timor-Leste' => ['TL', 'TLS'],
            'Togo' => ['TG', 'TGO'],
            'Tokelau' => ['TK', 'TKL'],
            'Tonga' => ['TO', 'TON'],
            'Trinidad and Tobago' => ['TT', 'TTO'],
            'Tunisia' => ['TN', 'TUN'],
            'Turkey' => ['TR', 'TUR'],
            'Turkmenistan' => ['TM', 'TKM'],
            'Turks and Caicos Islands' => ['TC', 'TCA'],
            'Tuvalu' => ['TV', 'TUV'],
            'Uganda' => ['UG', 'UGA'],
            'Ukraine' => ['UA', 'UKR'],
            'United Arab Emirates' => ['AE', 'ARE'],
            'United Kingdom' => ['GB', 'GBR'],
            'United States' => ['US', 'USA'],
            'United States Minor Outlying Islands' => ['UM', 'UMI'],
            'Uruguay' => ['UY', 'URY'],
            'Uzbekistan' => ['UZ', 'UZB'],
            'Vanuatu' => ['VU', 'VUT'],
            'Venezuela' => ['VE', 'VEN'],
            'Viet Nam' => ['VN', 'VNM'],
            'British Virgin Islands' => ['VG', 'VGB'],
            'US Virgin Islands' => ['VI', 'VIR'],
            'Wallis and Futuna' => ['WF', 'WLF'],
            'Western Sahara' => ['EH', 'ESH'],
            'Yemen' => ['YE', 'YEM'],
            'Zambia' => ['ZM', 'ZMB'],
            'Zimbabwe' => ['ZW', 'ZWE'],
        ];

        if (! isset($countryCodes[$countryName])) {
            return null;
        }

        $codes = $countryCodes[$countryName];

        return $digits === 2 ? $codes[0] : $codes[1];
    }

    /**
     * Undocumented function
     *
     * @param  array<string,string>  $data
     */
    public static function csv(array $data): string
    {
        $ca_data = static::extractData($data);
        $custom_data = static::extractData($data, true);

        return static::writeCsv($ca_data, $custom_data);
    }

    /**
     * Writes a temporary file formatted for channeladvisor and returns contents
     *
     * @param  array<string,string>  $ca_params  associative array of ChannelAdvisor reserved attributes
     * @param  array<string,string>  $custom_params  associative array of custom attributes
     * @return string file contents
     */
    protected static function writeCsv(array $ca_params, array $custom_params): string
    {
        $ca_headers = array_keys($ca_params);
        $data = $ca_params;
        $index = 1;
        foreach ($custom_params as $custom_name => $custom_value) {
            $ca_headers[] = 'Attribute'.$index.'Name';
            $ca_headers[] = 'Attribute'.$index.'Value';
            $data[] = $custom_name;
            $data[] = $custom_value;
            $index++;
        }
        $delim = ','; // default delimiter
        $encl = '"'; // default enclosure
        $esc = ''; // disables the escape character that is default "\\"
        $fp = tmpfile();
        $filename = stream_get_meta_data($fp)['uri'];
        fwrite($fp, "\xEF\xBB\xBF"); // Byte Order Mark - UTF-8
        fputcsv($fp, $ca_headers, $delim, $encl, $esc);
        fputcsv($fp, $data, $delim, $encl, $esc);

        return $filename;
    }

    /**
     * Undocumented function
     *
     * @param  array<string,string>  $data
     * @param  bool  $custom
     * @return array<string,string>
     */
    protected static function extractData(array $data, $custom = false): array
    {
        /**
         * 1. Fetch all ChannelAdvisor fields from the db
         * 2. Extract the field name and morph into a proper name
         * 3. Is current form data field a ChannelAdvisor field?
         *      If true set result data with proper field name and data
         * 4. return result data
         */
        /** @var Collection<string,ChannelListerField> $fields */
        $fields = ChannelListerField::query()
            ->select(['field_name', 'input_type'])
            ->where('type', '=', 'channeladvisor')
            ->get()
            ->keyBy('field_name');
        /** @var Collection<int,ChannelListerField> $field_map */
        $field_map = ChannelListerField::query()
            ->select(['field_name'])
            ->get();
        $field_map = $field_map->mapWithKeys(function (ChannelListerField $model): array {
            $key = str_replace([' ', '_'], '', $model->field_name);

            return [$key => $model->field_name];
        });
        $checkbox_fields = [];
        foreach ($fields as $field) {
            if ($field->input_type === InputType::CHECKBOX) {
                $checkbox_fields[$field->field_name] = $field;
            }
        }
        $result = [];
        foreach ($data as $input_name => $input_val) {
            // map to proper names
            $proper_name = $field_map[str_replace([' ', '_'], '', $input_name)] ?? $input_name;
            $input_val = trim($input_val);
            // $key = in_array($proper_name, $fields->field_name) ? 'channeladvisor' : 'custom';
            if ($custom === true && self::isValidCustomField($fields, $input_name, $input_val)) {
                // some browsers seem to be replacing spaces in field names with underscores, this seemed like the easiest fix for now
                // $result[$key][$proper_name] = $input_val;
                $result[$proper_name] = $input_val;
            } elseif ($custom === false && self::isValidChannelAdvisorField($fields, $input_name, $input_val)) {
                $result[$proper_name] = $input_val;
            }

            // stupid html checkbox checked == 'on'
            if ($checkbox_fields !== [] && array_key_exists($input_name, $checkbox_fields)) {
                $result[$proper_name] = strtolower($input_val) === 'on' ? 'true' : 'false';
            }
        }

        // backfill checkbox fields that weren't on the form and set them false
        if ($custom === true && $checkbox_fields !== []) {
            foreach ($checkbox_fields as $field) {
                $result[$field->field_name] ??= 'false';
            }
        } else {
            $result['Picture URLs'] = (new self)->prepareCaImages($data);
            if (array_key_exists('Total Quantity', $result)) { // Quantity update fields
                $result['Quantity Update Type'] = 'UNSHIPPED';
                $warehouse = config('channel-lister.default_warehouse', '');
                if (! is_string($warehouse)) {
                    $warehouse = '';
                }
                $result['DC Quantity'] = "{$warehouse}={$result['Total Quantity']}";
                $result['DC Quantity Update Type'] = 'partial dc list';
                unset($result['Total Quantity']);
            }
        }

        return $result;
    }

    /**
     * Takes in all form inputs and finds image urls, builds CA formatted url string for 'Picture URLs' field in csv
     *
     * @param  array<string,string>  $inputs  form inputs
     * @return string image urls in format ITEMIMAGEURL1=some.url.com,ITEMIMAGEURL2=some.url2.com etc
     */
    protected function prepareCaImages(array $inputs): string
    {
        $image_keys = [];
        foreach (array_keys($inputs) as $key) {
            if (str_contains($key, 'image') && in_array(str_contains($key, '_alt'), [0, false], true)) {
                $image_keys[] = $key;
            }
        }
        $ca_images = array_map(fn ($v) => $inputs[$v], $image_keys);

        return implode(',', array_filter($ca_images));
    }

    /**
     * @param  Collection<string,ChannelListerField>  $fields
     */
    private static function isValidChannelAdvisorField(Collection $fields, string $key, string $value): bool
    {
        return strlen($value) > 0 && $fields->has($key);
    }

    /**
     * @param  Collection<string,ChannelListerField>  $fields
     */
    private static function isValidCustomField(Collection $fields, string $key, string $value): bool
    {
        return strlen($value) > 0 && ! $fields->has($key);
    }
}
