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
                'title' => __('Afghanistan', 'fluentcrm'),
                'code'  => 'AF'
            ],
            [
                'title' => __('Åland Islands', 'fluentcrm'),
                'code'  => 'AX'
            ],
            [
                'title' => __('Albania', 'fluentcrm'),
                'code'  => 'AL'
            ],
            [
                'title' => __('Algeria', 'fluentcrm'),
                'code'  => 'DZ'
            ],
            [
                'title' => __('American Samoa', 'fluentcrm'),
                'code'  => 'AS'
            ],
            [
                'title' => __('Andorra', 'fluentcrm'),
                'code'  => 'AD'
            ],
            [
                'title' => __('Angola', 'fluentcrm'),
                'code'  => 'AO'
            ],
            [
                'title' => __('Anguilla', 'fluentcrm'),
                'code'  => 'AI'
            ],
            [
                'title' => __('Antarctica', 'fluentcrm'),
                'code'  => 'AQ'
            ],
            [
                'title' => __('Antigua and Barbuda', 'fluentcrm'),
                'code'  => 'AG'
            ],
            [
                'title' => __('Argentina', 'fluentcrm'),
                'code'  => 'AR'
            ],
            [
                'title' => __('Armenia', 'fluentcrm'),
                'code'  => 'AM'
            ],
            [
                'title' => __('Aruba', 'fluentcrm'),
                'code'  => 'AW'
            ],
            [
                'title' => __('Australia', 'fluentcrm'),
                'code'  => 'AU'
            ],
            [
                'title' => __('Austria', 'fluentcrm'),
                'code'  => 'AT'
            ],
            [
                'title' => __('Azerbaijan', 'fluentcrm'),
                'code'  => 'AZ'
            ],
            [
                'title' => __('Bahamas', 'fluentcrm'),
                'code'  => 'BS'
            ],
            [
                'title' => __('Bahrain', 'fluentcrm'),
                'code'  => 'BH'
            ],
            [
                'title' => __('Bangladesh', 'fluentcrm'),
                'code'  => 'BD'
            ],
            [
                'title' => __('Barbados', 'fluentcrm'),
                'code'  => 'BB'
            ],
            [
                'title' => __('Belarus', 'fluentcrm'),
                'code'  => 'BY'
            ],
            [
                'title' => __('Belgium', 'fluentcrm'),
                'code'  => 'BE'
            ],
            [
                'title' => __('Belau', 'fluentcrm'),
                'code'  => 'PW'
            ],
            [
                'title' => __('Belize', 'fluentcrm'),
                'code'  => 'BZ'
            ],
            [
                'title' => __('Benin', 'fluentcrm'),
                'code'  => 'BJ'
            ],
            [
                'title' => __('Bermuda', 'fluentcrm'),
                'code'  => 'BM'
            ],
            [
                'title' => __('Bhutan', 'fluentcrm'),
                'code'  => 'BT'
            ],
            [
                'title' => __('Bolivia', 'fluentcrm'),
                'code'  => 'BO'
            ],
            [
                'title' => __('Bonaire, Saint Eustatius and Saba', 'fluentcrm'),
                'code'  => 'BQ'
            ],
            [
                'title' => __('Bosnia and Herzegovina', 'fluentcrm'),
                'code'  => 'BA'
            ],
            [
                'title' => __('Botswana', 'fluentcrm'),
                'code'  => 'BW'
            ],
            [
                'title' => __('Bouvet Island', 'fluentcrm'),
                'code'  => 'BV'
            ],
            [
                'title' => __('Brazil', 'fluentcrm'),
                'code'  => 'BR'
            ],
            [
                'title' => __('British Indian Ocean Territory', 'fluentcrm'),
                'code'  => 'IO'
            ],
            [
                'title' => __('British Virgin Islands', 'fluentcrm'),
                'code'  => 'VG'
            ],
            [
                'title' => __('Brunei', 'fluentcrm'),
                'code'  => 'BN'
            ],
            [
                'title' => __('Bulgaria', 'fluentcrm'),
                'code'  => 'BG'
            ],
            [
                'title' => __('Burkina Faso', 'fluentcrm'),
                'code'  => 'BF'
            ],
            [
                'title' => __('Burundi', 'fluentcrm'),
                'code'  => 'BI'
            ],
            [
                'title' => __('Cambodia', 'fluentcrm'),
                'code'  => 'KH'
            ],
            [
                'title' => __('Cameroon', 'fluentcrm'),
                'code'  => 'CM'
            ],
            [
                'title' => __('Canada', 'fluentcrm'),
                'code'  => 'CA'
            ],
            [
                'title' => __('Cape Verde', 'fluentcrm'),
                'code'  => 'CV'
            ],
            [
                'title' => __('Cayman Islands', 'fluentcrm'),
                'code'  => 'KY'
            ],
            [
                'title' => __('Central African Republic', 'fluentcrm'),
                'code'  => 'CF'
            ],
            [
                'title' => __('Chad', 'fluentcrm'),
                'code'  => 'TD'
            ],
            [
                'title' => __('Chile', 'fluentcrm'),
                'code'  => 'CL'
            ],
            [
                'title' => __('China', 'fluentcrm'),
                'code'  => 'CN'
            ],
            [
                'title' => __('Christmas Island', 'fluentcrm'),
                'code'  => 'CX'
            ],
            [
                'title' => __('Cocos (Keeling) Islands', 'fluentcrm'),
                'code'  => 'CC'
            ],
            [
                'title' => __('Colombia', 'fluentcrm'),
                'code'  => 'CO'
            ],
            [
                'title' => __('Comoros', 'fluentcrm'),
                'code'  => 'KM'
            ],
            [
                'title' => __('Congo (Brazzaville)', 'fluentcrm'),
                'code'  => 'CG'
            ],
            [
                'title' => __('Congo (Kinshasa)', 'fluentcrm'),
                'code'  => 'CD'
            ],
            [
                'title' => __('Cook Islands', 'fluentcrm'),
                'code'  => 'CK'
            ],
            [
                'title' => __('Costa Rica', 'fluentcrm'),
                'code'  => 'CR'
            ],
            [
                'title' => __('Croatia', 'fluentcrm'),
                'code'  => 'HR'
            ],
            [
                'title' => __('Cuba', 'fluentcrm'),
                'code'  => 'CU'
            ],
            [
                'title' => __('Cura&ccedil;ao', 'fluentcrm'),
                'code'  => 'CW'
            ],
            [
                'title' => __('Cyprus', 'fluentcrm'),
                'code'  => 'CY'
            ],
            [
                'title' => __('Czech Republic', 'fluentcrm'),
                'code'  => 'CZ'
            ],
            [
                'title' => __('Denmark', 'fluentcrm'),
                'code'  => 'DK'
            ],
            [
                'title' => __('Djibouti', 'fluentcrm'),
                'code'  => 'DJ'
            ],
            [
                'title' => __('Dominica', 'fluentcrm'),
                'code'  => 'DM'
            ],
            [
                'title' => __('Dominican Republic', 'fluentcrm'),
                'code'  => 'DO'
            ],
            [
                'title' => __('Ecuador', 'fluentcrm'),
                'code'  => 'EC'
            ],
            [
                'title' => __('Egypt', 'fluentcrm'),
                'code'  => 'EG'
            ],
            [
                'title' => __('El Salvador', 'fluentcrm'),
                'code'  => 'SV'
            ],
            [
                'title' => __('Equatorial Guinea', 'fluentcrm'),
                'code'  => 'GQ'
            ],
            [
                'title' => __('Eritrea', 'fluentcrm'),
                'code'  => 'ER'
            ],
            [
                'title' => __('Estonia', 'fluentcrm'),
                'code'  => 'EE'
            ],
            [
                'title' => __('Ethiopia', 'fluentcrm'),
                'code'  => 'ET'
            ],
            [
                'title' => __('Falkland Islands', 'fluentcrm'),
                'code'  => 'FK'
            ],
            [
                'title' => __('Faroe Islands', 'fluentcrm'),
                'code'  => 'FO'
            ],
            [
                'title' => __('Fiji', 'fluentcrm'),
                'code'  => 'FJ'
            ],
            [
                'title' => __('Finland', 'fluentcrm'),
                'code'  => 'FI'
            ],
            [
                'title' => __('France', 'fluentcrm'),
                'code'  => 'FR'
            ],
            [
                'title' => __('French Guiana', 'fluentcrm'),
                'code'  => 'GF'
            ],
            [
                'title' => __('French Polynesia', 'fluentcrm'),
                'code'  => 'PF'
            ],
            [
                'title' => __('French Southern Territories', 'fluentcrm'),
                'code'  => 'TF'
            ],
            [
                'title' => __('Gabon', 'fluentcrm'),
                'code'  => 'GA'
            ],
            [
                'title' => __('Gambia', 'fluentcrm'),
                'code'  => 'GM'
            ],
            [
                'title' => __('Georgia', 'fluentcrm'),
                'code'  => 'GE'
            ],
            [
                'title' => __('Germany', 'fluentcrm'),
                'code'  => 'DE'
            ],
            [
                'title' => __('Ghana', 'fluentcrm'),
                'code'  => 'GH'
            ],
            [
                'title' => __('Gibraltar', 'fluentcrm'),
                'code'  => 'GI'
            ],
            [
                'title' => __('Greece', 'fluentcrm'),
                'code'  => 'GR'
            ],
            [
                'title' => __('Greenland', 'fluentcrm'),
                'code'  => 'GL'
            ],
            [
                'title' => __('Grenada', 'fluentcrm'),
                'code'  => 'GD'
            ],
            [
                'title' => __('Guadeloupe', 'fluentcrm'),
                'code'  => 'GP'
            ],
            [
                'title' => __('Guam', 'fluentcrm'),
                'code'  => 'GU'
            ],
            [
                'title' => __('Guatemala', 'fluentcrm'),
                'code'  => 'GT'
            ],
            [
                'title' => __('Guernsey', 'fluentcrm'),
                'code'  => 'GG'
            ],
            [
                'title' => __('Guinea', 'fluentcrm'),
                'code'  => 'GN'
            ],
            [
                'title' => __('Guinea-Bissau', 'fluentcrm'),
                'code'  => 'GW'
            ],
            [
                'title' => __('Guyana', 'fluentcrm'),
                'code'  => 'GY'
            ],
            [
                'title' => __('Haiti', 'fluentcrm'),
                'code'  => 'HT'
            ],
            [
                'title' => __('Heard Island and McDonald Islands', 'fluentcrm'),
                'code'  => 'HM'
            ],
            [
                'title' => __('Honduras', 'fluentcrm'),
                'code'  => 'HN'
            ],
            [
                'title' => __('Hong Kong', 'fluentcrm'),
                'code'  => 'HK'
            ],
            [
                'title' => __('Hungary', 'fluentcrm'),
                'code'  => 'HU'
            ],
            [
                'title' => __('Iceland', 'fluentcrm'),
                'code'  => 'IS'
            ],
            [
                'title' => __('India', 'fluentcrm'),
                'code'  => 'IN'
            ],
            [
                'title' => __('Indonesia', 'fluentcrm'),
                'code'  => 'ID'
            ],
            [
                'title' => __('Iran', 'fluentcrm'),
                'code'  => 'IR'
            ],
            [
                'title' => __('Iraq', 'fluentcrm'),
                'code'  => 'IQ'
            ],
            [
                'title' => __('Ireland', 'fluentcrm'),
                'code'  => 'IE'
            ],
            [
                'title' => __('Isle of Man', 'fluentcrm'),
                'code'  => 'IM'
            ],
            [
                'title' => __('Israel', 'fluentcrm'),
                'code'  => 'IL'
            ],
            [
                'title' => __('Italy', 'fluentcrm'),
                'code'  => 'IT'
            ],
            [
                'title' => __('Ivory Coast', 'fluentcrm'),
                'code'  => 'CI'
            ],
            [
                'title' => __('Jamaica', 'fluentcrm'),
                'code'  => 'JM'
            ],
            [
                'title' => __('Japan', 'fluentcrm'),
                'code'  => 'JP'
            ],
            [
                'title' => __('Jersey', 'fluentcrm'),
                'code'  => 'JE'
            ],
            [
                'title' => __('Jordan', 'fluentcrm'),
                'code'  => 'JO'
            ],
            [
                'title' => __('Kazakhstan', 'fluentcrm'),
                'code'  => 'KZ'
            ],
            [
                'title' => __('Kenya', 'fluentcrm'),
                'code'  => 'KE'
            ],
            [
                'title' => __('Kiribati', 'fluentcrm'),
                'code'  => 'KI'
            ],
            [
                'title' => __('Kuwait', 'fluentcrm'),
                'code'  => 'KW'
            ],
            [
                'title' => __('Kyrgyzstan', 'fluentcrm'),
                'code'  => 'KG'
            ],
            [
                'title' => __('Laos', 'fluentcrm'),
                'code'  => 'LA'
            ],
            [
                'title' => __('Latvia', 'fluentcrm'),
                'code'  => 'LV'
            ],
            [
                'title' => __('Lebanon', 'fluentcrm'),
                'code'  => 'LB'
            ],
            [
                'title' => __('Lesotho', 'fluentcrm'),
                'code'  => 'LS'
            ],
            [
                'title' => __('Liberia', 'fluentcrm'),
                'code'  => 'LR'
            ],
            [
                'title' => __('Libya', 'fluentcrm'),
                'code'  => 'LY'
            ],
            [
                'title' => __('Liechtenstein', 'fluentcrm'),
                'code'  => 'LI'
            ],
            [
                'title' => __('Lithuania', 'fluentcrm'),
                'code'  => 'LT'
            ],
            [
                'title' => __('Luxembourg', 'fluentcrm'),
                'code'  => 'LU'
            ],
            [
                'title' => __('Macao S.A.R., China', 'fluentcrm'),
                'code'  => 'MO'
            ],
            [
                'title' => __('Macedonia', 'fluentcrm'),
                'code'  => 'MK'
            ],
            [
                'title' => __('Madagascar', 'fluentcrm'),
                'code'  => 'MG'
            ],
            [
                'title' => __('Malawi', 'fluentcrm'),
                'code'  => 'MW'
            ],
            [
                'title' => __('Malaysia', 'fluentcrm'),
                'code'  => 'MY'
            ],
            [
                'title' => __('Maldives', 'fluentcrm'),
                'code'  => 'MV'
            ],
            [
                'title' => __('Mali', 'fluentcrm'),
                'code'  => 'ML'
            ],
            [
                'title' => __('Malta', 'fluentcrm'),
                'code'  => 'MT'
            ],
            [
                'title' => __('Marshall Islands', 'fluentcrm'),
                'code'  => 'MH'
            ],
            [
                'title' => __('Martinique', 'fluentcrm'),
                'code'  => 'MQ'
            ],
            [
                'title' => __('Mauritania', 'fluentcrm'),
                'code'  => 'MR'
            ],
            [
                'title' => __('Mauritius', 'fluentcrm'),
                'code'  => 'MU'
            ],
            [
                'title' => __('Mayotte', 'fluentcrm'),
                'code'  => 'YT'
            ],
            [
                'title' => __('Mexico', 'fluentcrm'),
                'code'  => 'MX'
            ],
            [
                'title' => __('Micronesia', 'fluentcrm'),
                'code'  => 'FM'
            ],
            [
                'title' => __('Moldova', 'fluentcrm'),
                'code'  => 'MD'
            ],
            [
                'title' => __('Monaco', 'fluentcrm'),
                'code'  => 'MC'
            ],
            [
                'title' => __('Mongolia', 'fluentcrm'),
                'code'  => 'MN'
            ],
            [
                'title' => __('Montenegro', 'fluentcrm'),
                'code'  => 'ME'
            ],
            [
                'title' => __('Montserrat', 'fluentcrm'),
                'code'  => 'MS'
            ],
            [
                'title' => __('Morocco', 'fluentcrm'),
                'code'  => 'MA'
            ],
            [
                'title' => __('Mozambique', 'fluentcrm'),
                'code'  => 'MZ'
            ],
            [
                'title' => __('Myanmar', 'fluentcrm'),
                'code'  => 'MM'
            ],
            [
                'title' => __('Namibia', 'fluentcrm'),
                'code'  => 'NA'
            ],
            [
                'title' => __('Nauru', 'fluentcrm'),
                'code'  => 'NR'
            ],
            [
                'title' => __('Nepal', 'fluentcrm'),
                'code'  => 'NP'
            ],
            [
                'title' => __('Netherlands', 'fluentcrm'),
                'code'  => 'NL'
            ],
            [
                'title' => __('New Caledonia', 'fluentcrm'),
                'code'  => 'NC'
            ],
            [
                'title' => __('New Zealand', 'fluentcrm'),
                'code'  => 'NZ'
            ],
            [
                'title' => __('Nicaragua', 'fluentcrm'),
                'code'  => 'NI'
            ],
            [
                'title' => __('Niger', 'fluentcrm'),
                'code'  => 'NE'
            ],
            [
                'title' => __('Nigeria', 'fluentcrm'),
                'code'  => 'NG'
            ],
            [
                'title' => __('Niue', 'fluentcrm'),
                'code'  => 'NU'
            ],
            [
                'title' => __('Norfolk Island', 'fluentcrm'),
                'code'  => 'NF'
            ],
            [
                'title' => __('Northern Mariana Islands', 'fluentcrm'),
                'code'  => 'MP'
            ],
            [
                'title' => __('North Korea', 'fluentcrm'),
                'code'  => 'KP'
            ],
            [
                'title' => __('Norway', 'fluentcrm'),
                'code'  => 'NO'
            ],
            [
                'title' => __('Oman', 'fluentcrm'),
                'code'  => 'OM'
            ],
            [
                'title' => __('Pakistan', 'fluentcrm'),
                'code'  => 'PK'
            ],
            [
                'title' => __('Palestinian Territory', 'fluentcrm'),
                'code'  => 'PS'
            ],
            [
                'title' => __('Panama', 'fluentcrm'),
                'code'  => 'PA'
            ],
            [
                'title' => __('Papua New Guinea', 'fluentcrm'),
                'code'  => 'PG'
            ],
            [
                'title' => __('Paraguay', 'fluentcrm'),
                'code'  => 'PY'
            ],
            [
                'title' => __('Peru', 'fluentcrm'),
                'code'  => 'PE'
            ],
            [
                'title' => __('Philippines', 'fluentcrm'),
                'code'  => 'PH'
            ],
            [
                'title' => __('Pitcairn', 'fluentcrm'),
                'code'  => 'PN'
            ],
            [
                'title' => __('Poland', 'fluentcrm'),
                'code'  => 'PL'
            ],
            [
                'title' => __('Portugal', 'fluentcrm'),
                'code'  => 'PT'
            ],
            [
                'title' => __('Puerto Rico', 'fluentcrm'),
                'code'  => 'PR'
            ],
            [
                'title' => __('Qatar', 'fluentcrm'),
                'code'  => 'QA'
            ],
            [
                'title' => __('Reunion', 'fluentcrm'),
                'code'  => 'RE'
            ],
            [
                'title' => __('Romania', 'fluentcrm'),
                'code'  => 'RO'
            ],
            [
                'title' => __('Russia', 'fluentcrm'),
                'code'  => 'RU'
            ],
            [
                'title' => __('Rwanda', 'fluentcrm'),
                'code'  => 'RW'
            ],
            [
                'title' => __('Saint Barth&eacute;lemy', 'fluentcrm'),
                'code'  => 'BL'
            ],
            [
                'title' => __('Saint Helena', 'fluentcrm'),
                'code'  => 'SH'
            ],
            [
                'title' => __('Saint Kitts and Nevis', 'fluentcrm'),
                'code'  => 'KN'
            ],
            [
                'title' => __('Saint Lucia', 'fluentcrm'),
                'code'  => 'LC'
            ],
            [
                'title' => __('Saint Martin (French part)', 'fluentcrm'),
                'code'  => 'MF'
            ],
            [
                'title' => __('Saint Martin (Dutch part)', 'fluentcrm'),
                'code'  => 'SX'
            ],
            [
                'title' => __('Saint Pierre and Miquelon', 'fluentcrm'),
                'code'  => 'PM'
            ],
            [
                'title' => __('Saint Vincent and the Grenadines', 'fluentcrm'),
                'code'  => 'VC'
            ],
            [
                'title' => __('San Marino', 'fluentcrm'),
                'code'  => 'SM'
            ],
            [
                'title' => __('S&atilde;o Tom&eacute; and Pr&iacute;ncipe', 'fluentcrm'),
                'code'  => 'ST'
            ],
            [
                'title' => __('Saudi Arabia', 'fluentcrm'),
                'code'  => 'SA'
            ],
            [
                'title' => __('Senegal', 'fluentcrm'),
                'code'  => 'SN'
            ],
            [
                'title' => __('Serbia', 'fluentcrm'),
                'code'  => 'RS'
            ],
            [
                'title' => __('Seychelles', 'fluentcrm'),
                'code'  => 'SC'
            ],
            [
                'title' => __('Sierra Leone', 'fluentcrm'),
                'code'  => 'SL'
            ],
            [
                'title' => __('Singapore', 'fluentcrm'),
                'code'  => 'SG'
            ],
            [
                'title' => __('Slovakia', 'fluentcrm'),
                'code'  => 'SK'
            ],
            [
                'title' => __('Slovenia', 'fluentcrm'),
                'code'  => 'SI'
            ],
            [
                'title' => __('Solomon Islands', 'fluentcrm'),
                'code'  => 'SB'
            ],
            [
                'title' => __('Somalia', 'fluentcrm'),
                'code'  => 'SO'
            ],
            [
                'title' => __('South Africa', 'fluentcrm'),
                'code'  => 'ZA'
            ],
            [
                'title' => __('South Georgia/Sandwich Islands', 'fluentcrm'),
                'code'  => 'GS'
            ],
            [
                'title' => __('South Korea', 'fluentcrm'),
                'code'  => 'KR'
            ],
            [
                'title' => __('South Sudan', 'fluentcrm'),
                'code'  => 'SS'
            ],
            [
                'title' => __('Spain', 'fluentcrm'),
                'code'  => 'ES'
            ],
            [
                'title' => __('Sri Lanka', 'fluentcrm'),
                'code'  => 'LK'
            ],
            [
                'title' => __('Sudan', 'fluentcrm'),
                'code'  => 'SD'
            ],
            [
                'title' => __('Suriname', 'fluentcrm'),
                'code'  => 'SR'
            ],
            [
                'title' => __('Svalbard and Jan Mayen', 'fluentcrm'),
                'code'  => 'SJ'
            ],
            [
                'title' => __('Swaziland', 'fluentcrm'),
                'code'  => 'SZ'
            ],
            [
                'title' => __('Sweden', 'fluentcrm'),
                'code'  => 'SE'
            ],
            [
                'title' => __('Switzerland', 'fluentcrm'),
                'code'  => 'CH'
            ],
            [
                'title' => __('Syria', 'fluentcrm'),
                'code'  => 'SY'
            ],
            [
                'title' => __('Taiwan', 'fluentcrm'),
                'code'  => 'TW'
            ],
            [
                'title' => __('Tajikistan', 'fluentcrm'),
                'code'  => 'TJ'
            ],
            [
                'title' => __('Tanzania', 'fluentcrm'),
                'code'  => 'TZ'
            ],
            [
                'title' => __('Thailand', 'fluentcrm'),
                'code'  => 'TH'
            ],
            [
                'title' => __('Timor-Leste', 'fluentcrm'),
                'code'  => 'TL'
            ],
            [
                'title' => __('Togo', 'fluentcrm'),
                'code'  => 'TG'
            ],
            [
                'title' => __('Tokelau', 'fluentcrm'),
                'code'  => 'TK'
            ],
            [
                'title' => __('Tonga', 'fluentcrm'),
                'code'  => 'TO'
            ],
            [
                'title' => __('Trinidad and Tobago', 'fluentcrm'),
                'code'  => 'TT'
            ],
            [
                'title' => __('Tunisia', 'fluentcrm'),
                'code'  => 'TN'
            ],
            [
                'title' => __('Turkey', 'fluentcrm'),
                'code'  => 'TR'
            ],
            [
                'title' => __('Turkmenistan', 'fluentcrm'),
                'code'  => 'TM'
            ],
            [
                'title' => __('Turks and Caicos Islands', 'fluentcrm'),
                'code'  => 'TC'
            ],
            [
                'title' => __('Tuvalu', 'fluentcrm'),
                'code'  => 'TV'
            ],
            [
                'title' => __('Uganda', 'fluentcrm'),
                'code'  => 'UG'
            ],
            [
                'title' => __('Ukraine', 'fluentcrm'),
                'code'  => 'UA'
            ],
            [
                'title' => __('United Arab Emirates', 'fluentcrm'),
                'code'  => 'AE'
            ],
            [
                'title' => __('United Kingdom (UK)', 'fluentcrm'),
                'code'  => 'GB'
            ],
            [
                'title' => __('United States (US)', 'fluentcrm'),
                'code'  => 'US'
            ],
            [
                'title' => __('United States (US) Minor Outlying Islands', 'fluentcrm'),
                'code'  => 'UM'
            ],
            [
                'title' => __('United States (US) Virgin Islands', 'fluentcrm'),
                'code'  => 'VI'
            ],
            [
                'title' => __('Uruguay', 'fluentcrm'),
                'code'  => 'UY'
            ],
            [
                'title' => __('Uzbekistan', 'fluentcrm'),
                'code'  => 'UZ'
            ],
            [
                'title' => __('Vanuatu', 'fluentcrm'),
                'code'  => 'VU'
            ],
            [
                'title' => __('Vatican', 'fluentcrm'),
                'code'  => 'VA'
            ],
            [
                'title' => __('Venezuela', 'fluentcrm'),
                'code'  => 'VE'
            ],
            [
                'title' => __('Vietnam', 'fluentcrm'),
                'code'  => 'VN'
            ],
            [
                'title' => __('Wallis and Futuna', 'fluentcrm'),
                'code'  => 'WF'
            ],
            [
                'title' => __('Western Sahara', 'fluentcrm'),
                'code'  => 'EH'
            ],
            [
                'title' => __('Samoa', 'fluentcrm'),
                'code'  => 'WS'
            ],
            [
                'title' => __('Yemen', 'fluentcrm'),
                'code'  => 'YE'
            ],
            [
                'title' => __('Zambia', 'fluentcrm'),
                'code'  => 'ZM'
            ],
            [
                'title' => __('Zimbabwe', 'fluentcrm'),
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
