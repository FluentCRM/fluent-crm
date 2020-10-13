<?php

namespace FluentCrm\App\Hooks\Handlers;

class CountryNames
{
    private $names = [];

    /**
     * Construct country names from an array.
     */
    public function __construct()
    {
        $this->names = [
            [
                'title' => __('Afghanistan', 'fluent-crm'),
                'code'  => 'AF'
            ],
            [
                'title' => __('Åland Islands', 'fluent-crm'),
                'code'  => 'AX'
            ],
            [
                'title' => __('Albania', 'fluent-crm'),
                'code'  => 'AL'
            ],
            [
                'title' => __('Algeria', 'fluent-crm'),
                'code'  => 'DZ'
            ],
            [
                'title' => __('American Samoa', 'fluent-crm'),
                'code'  => 'AS'
            ],
            [
                'title' => __('Andorra', 'fluent-crm'),
                'code'  => 'AD'
            ],
            [
                'title' => __('Angola', 'fluent-crm'),
                'code'  => 'AO'
            ],
            [
                'title' => __('Anguilla', 'fluent-crm'),
                'code'  => 'AI'
            ],
            [
                'title' => __('Antarctica', 'fluent-crm'),
                'code'  => 'AQ'
            ],
            [
                'title' => __('Antigua and Barbuda', 'fluent-crm'),
                'code'  => 'AG'
            ],
            [
                'title' => __('Argentina', 'fluent-crm'),
                'code'  => 'AR'
            ],
            [
                'title' => __('Armenia', 'fluent-crm'),
                'code'  => 'AM'
            ],
            [
                'title' => __('Aruba', 'fluent-crm'),
                'code'  => 'AW'
            ],
            [
                'title' => __('Australia', 'fluent-crm'),
                'code'  => 'AU'
            ],
            [
                'title' => __('Austria', 'fluent-crm'),
                'code'  => 'AT'
            ],
            [
                'title' => __('Azerbaijan', 'fluent-crm'),
                'code'  => 'AZ'
            ],
            [
                'title' => __('Bahamas', 'fluent-crm'),
                'code'  => 'BS'
            ],
            [
                'title' => __('Bahrain', 'fluent-crm'),
                'code'  => 'BH'
            ],
            [
                'title' => __('Bangladesh', 'fluent-crm'),
                'code'  => 'BD'
            ],
            [
                'title' => __('Barbados', 'fluent-crm'),
                'code'  => 'BB'
            ],
            [
                'title' => __('Belarus', 'fluent-crm'),
                'code'  => 'BY'
            ],
            [
                'title' => __('Belgium', 'fluent-crm'),
                'code'  => 'BE'
            ],
            [
                'title' => __('Belau', 'fluent-crm'),
                'code'  => 'PW'
            ],
            [
                'title' => __('Belize', 'fluent-crm'),
                'code'  => 'BZ'
            ],
            [
                'title' => __('Benin', 'fluent-crm'),
                'code'  => 'BJ'
            ],
            [
                'title' => __('Bermuda', 'fluent-crm'),
                'code'  => 'BM'
            ],
            [
                'title' => __('Bhutan', 'fluent-crm'),
                'code'  => 'BT'
            ],
            [
                'title' => __('Bolivia', 'fluent-crm'),
                'code'  => 'BO'
            ],
            [
                'title' => __('Bonaire, Saint Eustatius and Saba', 'fluent-crm'),
                'code'  => 'BQ'
            ],
            [
                'title' => __('Bosnia and Herzegovina', 'fluent-crm'),
                'code'  => 'BA'
            ],
            [
                'title' => __('Botswana', 'fluent-crm'),
                'code'  => 'BW'
            ],
            [
                'title' => __('Bouvet Island', 'fluent-crm'),
                'code'  => 'BV'
            ],
            [
                'title' => __('Brazil', 'fluent-crm'),
                'code'  => 'BR'
            ],
            [
                'title' => __('British Indian Ocean Territory', 'fluent-crm'),
                'code'  => 'IO'
            ],
            [
                'title' => __('British Virgin Islands', 'fluent-crm'),
                'code'  => 'VG'
            ],
            [
                'title' => __('Brunei', 'fluent-crm'),
                'code'  => 'BN'
            ],
            [
                'title' => __('Bulgaria', 'fluent-crm'),
                'code'  => 'BG'
            ],
            [
                'title' => __('Burkina Faso', 'fluent-crm'),
                'code'  => 'BF'
            ],
            [
                'title' => __('Burundi', 'fluent-crm'),
                'code'  => 'BI'
            ],
            [
                'title' => __('Cambodia', 'fluent-crm'),
                'code'  => 'KH'
            ],
            [
                'title' => __('Cameroon', 'fluent-crm'),
                'code'  => 'CM'
            ],
            [
                'title' => __('Canada', 'fluent-crm'),
                'code'  => 'CA'
            ],
            [
                'title' => __('Cape Verde', 'fluent-crm'),
                'code'  => 'CV'
            ],
            [
                'title' => __('Cayman Islands', 'fluent-crm'),
                'code'  => 'KY'
            ],
            [
                'title' => __('Central African Republic', 'fluent-crm'),
                'code'  => 'CF'
            ],
            [
                'title' => __('Chad', 'fluent-crm'),
                'code'  => 'TD'
            ],
            [
                'title' => __('Chile', 'fluent-crm'),
                'code'  => 'CL'
            ],
            [
                'title' => __('China', 'fluent-crm'),
                'code'  => 'CN'
            ],
            [
                'title' => __('Christmas Island', 'fluent-crm'),
                'code'  => 'CX'
            ],
            [
                'title' => __('Cocos (Keeling) Islands', 'fluent-crm'),
                'code'  => 'CC'
            ],
            [
                'title' => __('Colombia', 'fluent-crm'),
                'code'  => 'CO'
            ],
            [
                'title' => __('Comoros', 'fluent-crm'),
                'code'  => 'KM'
            ],
            [
                'title' => __('Congo (Brazzaville)', 'fluent-crm'),
                'code'  => 'CG'
            ],
            [
                'title' => __('Congo (Kinshasa)', 'fluent-crm'),
                'code'  => 'CD'
            ],
            [
                'title' => __('Cook Islands', 'fluent-crm'),
                'code'  => 'CK'
            ],
            [
                'title' => __('Costa Rica', 'fluent-crm'),
                'code'  => 'CR'
            ],
            [
                'title' => __('Croatia', 'fluent-crm'),
                'code'  => 'HR'
            ],
            [
                'title' => __('Cuba', 'fluent-crm'),
                'code'  => 'CU'
            ],
            [
                'title' => __('Cura&ccedil;ao', 'fluent-crm'),
                'code'  => 'CW'
            ],
            [
                'title' => __('Cyprus', 'fluent-crm'),
                'code'  => 'CY'
            ],
            [
                'title' => __('Czech Republic', 'fluent-crm'),
                'code'  => 'CZ'
            ],
            [
                'title' => __('Denmark', 'fluent-crm'),
                'code'  => 'DK'
            ],
            [
                'title' => __('Djibouti', 'fluent-crm'),
                'code'  => 'DJ'
            ],
            [
                'title' => __('Dominica', 'fluent-crm'),
                'code'  => 'DM'
            ],
            [
                'title' => __('Dominican Republic', 'fluent-crm'),
                'code'  => 'DO'
            ],
            [
                'title' => __('Ecuador', 'fluent-crm'),
                'code'  => 'EC'
            ],
            [
                'title' => __('Egypt', 'fluent-crm'),
                'code'  => 'EG'
            ],
            [
                'title' => __('El Salvador', 'fluent-crm'),
                'code'  => 'SV'
            ],
            [
                'title' => __('Equatorial Guinea', 'fluent-crm'),
                'code'  => 'GQ'
            ],
            [
                'title' => __('Eritrea', 'fluent-crm'),
                'code'  => 'ER'
            ],
            [
                'title' => __('Estonia', 'fluent-crm'),
                'code'  => 'EE'
            ],
            [
                'title' => __('Ethiopia', 'fluent-crm'),
                'code'  => 'ET'
            ],
            [
                'title' => __('Falkland Islands', 'fluent-crm'),
                'code'  => 'FK'
            ],
            [
                'title' => __('Faroe Islands', 'fluent-crm'),
                'code'  => 'FO'
            ],
            [
                'title' => __('Fiji', 'fluent-crm'),
                'code'  => 'FJ'
            ],
            [
                'title' => __('Finland', 'fluent-crm'),
                'code'  => 'FI'
            ],
            [
                'title' => __('France', 'fluent-crm'),
                'code'  => 'FR'
            ],
            [
                'title' => __('French Guiana', 'fluent-crm'),
                'code'  => 'GF'
            ],
            [
                'title' => __('French Polynesia', 'fluent-crm'),
                'code'  => 'PF'
            ],
            [
                'title' => __('French Southern Territories', 'fluent-crm'),
                'code'  => 'TF'
            ],
            [
                'title' => __('Gabon', 'fluent-crm'),
                'code'  => 'GA'
            ],
            [
                'title' => __('Gambia', 'fluent-crm'),
                'code'  => 'GM'
            ],
            [
                'title' => __('Georgia', 'fluent-crm'),
                'code'  => 'GE'
            ],
            [
                'title' => __('Germany', 'fluent-crm'),
                'code'  => 'DE'
            ],
            [
                'title' => __('Ghana', 'fluent-crm'),
                'code'  => 'GH'
            ],
            [
                'title' => __('Gibraltar', 'fluent-crm'),
                'code'  => 'GI'
            ],
            [
                'title' => __('Greece', 'fluent-crm'),
                'code'  => 'GR'
            ],
            [
                'title' => __('Greenland', 'fluent-crm'),
                'code'  => 'GL'
            ],
            [
                'title' => __('Grenada', 'fluent-crm'),
                'code'  => 'GD'
            ],
            [
                'title' => __('Guadeloupe', 'fluent-crm'),
                'code'  => 'GP'
            ],
            [
                'title' => __('Guam', 'fluent-crm'),
                'code'  => 'GU'
            ],
            [
                'title' => __('Guatemala', 'fluent-crm'),
                'code'  => 'GT'
            ],
            [
                'title' => __('Guernsey', 'fluent-crm'),
                'code'  => 'GG'
            ],
            [
                'title' => __('Guinea', 'fluent-crm'),
                'code'  => 'GN'
            ],
            [
                'title' => __('Guinea-Bissau', 'fluent-crm'),
                'code'  => 'GW'
            ],
            [
                'title' => __('Guyana', 'fluent-crm'),
                'code'  => 'GY'
            ],
            [
                'title' => __('Haiti', 'fluent-crm'),
                'code'  => 'HT'
            ],
            [
                'title' => __('Heard Island and McDonald Islands', 'fluent-crm'),
                'code'  => 'HM'
            ],
            [
                'title' => __('Honduras', 'fluent-crm'),
                'code'  => 'HN'
            ],
            [
                'title' => __('Hong Kong', 'fluent-crm'),
                'code'  => 'HK'
            ],
            [
                'title' => __('Hungary', 'fluent-crm'),
                'code'  => 'HU'
            ],
            [
                'title' => __('Iceland', 'fluent-crm'),
                'code'  => 'IS'
            ],
            [
                'title' => __('India', 'fluent-crm'),
                'code'  => 'IN'
            ],
            [
                'title' => __('Indonesia', 'fluent-crm'),
                'code'  => 'ID'
            ],
            [
                'title' => __('Iran', 'fluent-crm'),
                'code'  => 'IR'
            ],
            [
                'title' => __('Iraq', 'fluent-crm'),
                'code'  => 'IQ'
            ],
            [
                'title' => __('Ireland', 'fluent-crm'),
                'code'  => 'IE'
            ],
            [
                'title' => __('Isle of Man', 'fluent-crm'),
                'code'  => 'IM'
            ],
            [
                'title' => __('Israel', 'fluent-crm'),
                'code'  => 'IL'
            ],
            [
                'title' => __('Italy', 'fluent-crm'),
                'code'  => 'IT'
            ],
            [
                'title' => __('Ivory Coast', 'fluent-crm'),
                'code'  => 'CI'
            ],
            [
                'title' => __('Jamaica', 'fluent-crm'),
                'code'  => 'JM'
            ],
            [
                'title' => __('Japan', 'fluent-crm'),
                'code'  => 'JP'
            ],
            [
                'title' => __('Jersey', 'fluent-crm'),
                'code'  => 'JE'
            ],
            [
                'title' => __('Jordan', 'fluent-crm'),
                'code'  => 'JO'
            ],
            [
                'title' => __('Kazakhstan', 'fluent-crm'),
                'code'  => 'KZ'
            ],
            [
                'title' => __('Kenya', 'fluent-crm'),
                'code'  => 'KE'
            ],
            [
                'title' => __('Kiribati', 'fluent-crm'),
                'code'  => 'KI'
            ],
            [
                'title' => __('Kuwait', 'fluent-crm'),
                'code'  => 'KW'
            ],
            [
                'title' => __('Kyrgyzstan', 'fluent-crm'),
                'code'  => 'KG'
            ],
            [
                'title' => __('Laos', 'fluent-crm'),
                'code'  => 'LA'
            ],
            [
                'title' => __('Latvia', 'fluent-crm'),
                'code'  => 'LV'
            ],
            [
                'title' => __('Lebanon', 'fluent-crm'),
                'code'  => 'LB'
            ],
            [
                'title' => __('Lesotho', 'fluent-crm'),
                'code'  => 'LS'
            ],
            [
                'title' => __('Liberia', 'fluent-crm'),
                'code'  => 'LR'
            ],
            [
                'title' => __('Libya', 'fluent-crm'),
                'code'  => 'LY'
            ],
            [
                'title' => __('Liechtenstein', 'fluent-crm'),
                'code'  => 'LI'
            ],
            [
                'title' => __('Lithuania', 'fluent-crm'),
                'code'  => 'LT'
            ],
            [
                'title' => __('Luxembourg', 'fluent-crm'),
                'code'  => 'LU'
            ],
            [
                'title' => __('Macao S.A.R., China', 'fluent-crm'),
                'code'  => 'MO'
            ],
            [
                'title' => __('Macedonia', 'fluent-crm'),
                'code'  => 'MK'
            ],
            [
                'title' => __('Madagascar', 'fluent-crm'),
                'code'  => 'MG'
            ],
            [
                'title' => __('Malawi', 'fluent-crm'),
                'code'  => 'MW'
            ],
            [
                'title' => __('Malaysia', 'fluent-crm'),
                'code'  => 'MY'
            ],
            [
                'title' => __('Maldives', 'fluent-crm'),
                'code'  => 'MV'
            ],
            [
                'title' => __('Mali', 'fluent-crm'),
                'code'  => 'ML'
            ],
            [
                'title' => __('Malta', 'fluent-crm'),
                'code'  => 'MT'
            ],
            [
                'title' => __('Marshall Islands', 'fluent-crm'),
                'code'  => 'MH'
            ],
            [
                'title' => __('Martinique', 'fluent-crm'),
                'code'  => 'MQ'
            ],
            [
                'title' => __('Mauritania', 'fluent-crm'),
                'code'  => 'MR'
            ],
            [
                'title' => __('Mauritius', 'fluent-crm'),
                'code'  => 'MU'
            ],
            [
                'title' => __('Mayotte', 'fluent-crm'),
                'code'  => 'YT'
            ],
            [
                'title' => __('Mexico', 'fluent-crm'),
                'code'  => 'MX'
            ],
            [
                'title' => __('Micronesia', 'fluent-crm'),
                'code'  => 'FM'
            ],
            [
                'title' => __('Moldova', 'fluent-crm'),
                'code'  => 'MD'
            ],
            [
                'title' => __('Monaco', 'fluent-crm'),
                'code'  => 'MC'
            ],
            [
                'title' => __('Mongolia', 'fluent-crm'),
                'code'  => 'MN'
            ],
            [
                'title' => __('Montenegro', 'fluent-crm'),
                'code'  => 'ME'
            ],
            [
                'title' => __('Montserrat', 'fluent-crm'),
                'code'  => 'MS'
            ],
            [
                'title' => __('Morocco', 'fluent-crm'),
                'code'  => 'MA'
            ],
            [
                'title' => __('Mozambique', 'fluent-crm'),
                'code'  => 'MZ'
            ],
            [
                'title' => __('Myanmar', 'fluent-crm'),
                'code'  => 'MM'
            ],
            [
                'title' => __('Namibia', 'fluent-crm'),
                'code'  => 'NA'
            ],
            [
                'title' => __('Nauru', 'fluent-crm'),
                'code'  => 'NR'
            ],
            [
                'title' => __('Nepal', 'fluent-crm'),
                'code'  => 'NP'
            ],
            [
                'title' => __('Netherlands', 'fluent-crm'),
                'code'  => 'NL'
            ],
            [
                'title' => __('New Caledonia', 'fluent-crm'),
                'code'  => 'NC'
            ],
            [
                'title' => __('New Zealand', 'fluent-crm'),
                'code'  => 'NZ'
            ],
            [
                'title' => __('Nicaragua', 'fluent-crm'),
                'code'  => 'NI'
            ],
            [
                'title' => __('Niger', 'fluent-crm'),
                'code'  => 'NE'
            ],
            [
                'title' => __('Nigeria', 'fluent-crm'),
                'code'  => 'NG'
            ],
            [
                'title' => __('Niue', 'fluent-crm'),
                'code'  => 'NU'
            ],
            [
                'title' => __('Norfolk Island', 'fluent-crm'),
                'code'  => 'NF'
            ],
            [
                'title' => __('Northern Mariana Islands', 'fluent-crm'),
                'code'  => 'MP'
            ],
            [
                'title' => __('North Korea', 'fluent-crm'),
                'code'  => 'KP'
            ],
            [
                'title' => __('Norway', 'fluent-crm'),
                'code'  => 'NO'
            ],
            [
                'title' => __('Oman', 'fluent-crm'),
                'code'  => 'OM'
            ],
            [
                'title' => __('Pakistan', 'fluent-crm'),
                'code'  => 'PK'
            ],
            [
                'title' => __('Palestinian Territory', 'fluent-crm'),
                'code'  => 'PS'
            ],
            [
                'title' => __('Panama', 'fluent-crm'),
                'code'  => 'PA'
            ],
            [
                'title' => __('Papua New Guinea', 'fluent-crm'),
                'code'  => 'PG'
            ],
            [
                'title' => __('Paraguay', 'fluent-crm'),
                'code'  => 'PY'
            ],
            [
                'title' => __('Peru', 'fluent-crm'),
                'code'  => 'PE'
            ],
            [
                'title' => __('Philippines', 'fluent-crm'),
                'code'  => 'PH'
            ],
            [
                'title' => __('Pitcairn', 'fluent-crm'),
                'code'  => 'PN'
            ],
            [
                'title' => __('Poland', 'fluent-crm'),
                'code'  => 'PL'
            ],
            [
                'title' => __('Portugal', 'fluent-crm'),
                'code'  => 'PT'
            ],
            [
                'title' => __('Puerto Rico', 'fluent-crm'),
                'code'  => 'PR'
            ],
            [
                'title' => __('Qatar', 'fluent-crm'),
                'code'  => 'QA'
            ],
            [
                'title' => __('Reunion', 'fluent-crm'),
                'code'  => 'RE'
            ],
            [
                'title' => __('Romania', 'fluent-crm'),
                'code'  => 'RO'
            ],
            [
                'title' => __('Russia', 'fluent-crm'),
                'code'  => 'RU'
            ],
            [
                'title' => __('Rwanda', 'fluent-crm'),
                'code'  => 'RW'
            ],
            [
                'title' => __('Saint Barth&eacute;lemy', 'fluent-crm'),
                'code'  => 'BL'
            ],
            [
                'title' => __('Saint Helena', 'fluent-crm'),
                'code'  => 'SH'
            ],
            [
                'title' => __('Saint Kitts and Nevis', 'fluent-crm'),
                'code'  => 'KN'
            ],
            [
                'title' => __('Saint Lucia', 'fluent-crm'),
                'code'  => 'LC'
            ],
            [
                'title' => __('Saint Martin (French part)', 'fluent-crm'),
                'code'  => 'MF'
            ],
            [
                'title' => __('Saint Martin (Dutch part)', 'fluent-crm'),
                'code'  => 'SX'
            ],
            [
                'title' => __('Saint Pierre and Miquelon', 'fluent-crm'),
                'code'  => 'PM'
            ],
            [
                'title' => __('Saint Vincent and the Grenadines', 'fluent-crm'),
                'code'  => 'VC'
            ],
            [
                'title' => __('San Marino', 'fluent-crm'),
                'code'  => 'SM'
            ],
            [
                'title' => __('S&atilde;o Tom&eacute; and Pr&iacute;ncipe', 'fluent-crm'),
                'code'  => 'ST'
            ],
            [
                'title' => __('Saudi Arabia', 'fluent-crm'),
                'code'  => 'SA'
            ],
            [
                'title' => __('Senegal', 'fluent-crm'),
                'code'  => 'SN'
            ],
            [
                'title' => __('Serbia', 'fluent-crm'),
                'code'  => 'RS'
            ],
            [
                'title' => __('Seychelles', 'fluent-crm'),
                'code'  => 'SC'
            ],
            [
                'title' => __('Sierra Leone', 'fluent-crm'),
                'code'  => 'SL'
            ],
            [
                'title' => __('Singapore', 'fluent-crm'),
                'code'  => 'SG'
            ],
            [
                'title' => __('Slovakia', 'fluent-crm'),
                'code'  => 'SK'
            ],
            [
                'title' => __('Slovenia', 'fluent-crm'),
                'code'  => 'SI'
            ],
            [
                'title' => __('Solomon Islands', 'fluent-crm'),
                'code'  => 'SB'
            ],
            [
                'title' => __('Somalia', 'fluent-crm'),
                'code'  => 'SO'
            ],
            [
                'title' => __('South Africa', 'fluent-crm'),
                'code'  => 'ZA'
            ],
            [
                'title' => __('South Georgia/Sandwich Islands', 'fluent-crm'),
                'code'  => 'GS'
            ],
            [
                'title' => __('South Korea', 'fluent-crm'),
                'code'  => 'KR'
            ],
            [
                'title' => __('South Sudan', 'fluent-crm'),
                'code'  => 'SS'
            ],
            [
                'title' => __('Spain', 'fluent-crm'),
                'code'  => 'ES'
            ],
            [
                'title' => __('Sri Lanka', 'fluent-crm'),
                'code'  => 'LK'
            ],
            [
                'title' => __('Sudan', 'fluent-crm'),
                'code'  => 'SD'
            ],
            [
                'title' => __('Suriname', 'fluent-crm'),
                'code'  => 'SR'
            ],
            [
                'title' => __('Svalbard and Jan Mayen', 'fluent-crm'),
                'code'  => 'SJ'
            ],
            [
                'title' => __('Swaziland', 'fluent-crm'),
                'code'  => 'SZ'
            ],
            [
                'title' => __('Sweden', 'fluent-crm'),
                'code'  => 'SE'
            ],
            [
                'title' => __('Switzerland', 'fluent-crm'),
                'code'  => 'CH'
            ],
            [
                'title' => __('Syria', 'fluent-crm'),
                'code'  => 'SY'
            ],
            [
                'title' => __('Taiwan', 'fluent-crm'),
                'code'  => 'TW'
            ],
            [
                'title' => __('Tajikistan', 'fluent-crm'),
                'code'  => 'TJ'
            ],
            [
                'title' => __('Tanzania', 'fluent-crm'),
                'code'  => 'TZ'
            ],
            [
                'title' => __('Thailand', 'fluent-crm'),
                'code'  => 'TH'
            ],
            [
                'title' => __('Timor-Leste', 'fluent-crm'),
                'code'  => 'TL'
            ],
            [
                'title' => __('Togo', 'fluent-crm'),
                'code'  => 'TG'
            ],
            [
                'title' => __('Tokelau', 'fluent-crm'),
                'code'  => 'TK'
            ],
            [
                'title' => __('Tonga', 'fluent-crm'),
                'code'  => 'TO'
            ],
            [
                'title' => __('Trinidad and Tobago', 'fluent-crm'),
                'code'  => 'TT'
            ],
            [
                'title' => __('Tunisia', 'fluent-crm'),
                'code'  => 'TN'
            ],
            [
                'title' => __('Turkey', 'fluent-crm'),
                'code'  => 'TR'
            ],
            [
                'title' => __('Turkmenistan', 'fluent-crm'),
                'code'  => 'TM'
            ],
            [
                'title' => __('Turks and Caicos Islands', 'fluent-crm'),
                'code'  => 'TC'
            ],
            [
                'title' => __('Tuvalu', 'fluent-crm'),
                'code'  => 'TV'
            ],
            [
                'title' => __('Uganda', 'fluent-crm'),
                'code'  => 'UG'
            ],
            [
                'title' => __('Ukraine', 'fluent-crm'),
                'code'  => 'UA'
            ],
            [
                'title' => __('United Arab Emirates', 'fluent-crm'),
                'code'  => 'AE'
            ],
            [
                'title' => __('United Kingdom (UK)', 'fluent-crm'),
                'code'  => 'GB'
            ],
            [
                'title' => __('United States (US)', 'fluent-crm'),
                'code'  => 'US'
            ],
            [
                'title' => __('United States (US) Minor Outlying Islands', 'fluent-crm'),
                'code'  => 'UM'
            ],
            [
                'title' => __('United States (US) Virgin Islands', 'fluent-crm'),
                'code'  => 'VI'
            ],
            [
                'title' => __('Uruguay', 'fluent-crm'),
                'code'  => 'UY'
            ],
            [
                'title' => __('Uzbekistan', 'fluent-crm'),
                'code'  => 'UZ'
            ],
            [
                'title' => __('Vanuatu', 'fluent-crm'),
                'code'  => 'VU'
            ],
            [
                'title' => __('Vatican', 'fluent-crm'),
                'code'  => 'VA'
            ],
            [
                'title' => __('Venezuela', 'fluent-crm'),
                'code'  => 'VE'
            ],
            [
                'title' => __('Vietnam', 'fluent-crm'),
                'code'  => 'VN'
            ],
            [
                'title' => __('Wallis and Futuna', 'fluent-crm'),
                'code'  => 'WF'
            ],
            [
                'title' => __('Western Sahara', 'fluent-crm'),
                'code'  => 'EH'
            ],
            [
                'title' => __('Samoa', 'fluent-crm'),
                'code'  => 'WS'
            ],
            [
                'title' => __('Yemen', 'fluent-crm'),
                'code'  => 'YE'
            ],
            [
                'title' => __('Zambia', 'fluent-crm'),
                'code'  => 'ZM'
            ],
            [
                'title' => __('Zimbabwe', 'fluent-crm'),
                'code'  => 'ZW'
            ]
        ];
    }

    /**
     * Get an array of all the country names.
     *
     * @return array
     */
    public function get()
    {
        return $this->names;
    }
}
