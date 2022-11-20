<?php

namespace FluentCrm\App\Hooks\Handlers;

/**
 *  CountryNames Class
 *
 * @package FluentCrm\App\Hooks
 *
 * @version 1.0.0
 */

class CountryNames
{
    private $names;

    /**
     * Construct country names from an array.
     */
    public function __construct()
    {
        $this->names = [
            [
                'code'  => 'AF',
                'title' => __('Afghanistan', 'fluent-crm')
            ],
            [
                'code'  => 'AX',
                'title' => __('Åland Islands', 'fluent-crm')
            ],
            [
                'code'  => 'AL',
                'title' => __('Albania', 'fluent-crm')
            ],
            [
                'code'  => 'DZ',
                'title' => __('Algeria', 'fluent-crm')
            ],
            [
                'code'  => 'AS',
                'title' => __('American Samoa', 'fluent-crm')
            ],
            [
                'code'  => 'AD',
                'title' => __('Andorra', 'fluent-crm')
            ],
            [
                'code'  => 'AO',
                'title' => __('Angola', 'fluent-crm')
            ],
            [
                'code'  => 'AI',
                'title' => __('Anguilla', 'fluent-crm')
            ],
            [
                'code'  => 'AQ',
                'title' => __('Antarctica', 'fluent-crm')
            ],
            [
                'code'  => 'AG',
                'title' => __('Antigua and Barbuda', 'fluent-crm')
            ],
            [
                'code'  => 'AR',
                'title' => __('Argentina', 'fluent-crm')
            ],
            [
                'code'  => 'AM',
                'title' => __('Armenia', 'fluent-crm')
            ],
            [
                'code'  => 'AW',
                'title' => __('Aruba', 'fluent-crm')
            ],
            [
                'code'  => 'AU',
                'title' => __('Australia', 'fluent-crm')
            ],
            [
                'code'  => 'AT',
                'title' => __('Austria', 'fluent-crm')
            ],
            [
                'code'  => 'AZ',
                'title' => __('Azerbaijan', 'fluent-crm')
            ],
            [
                'code'  => 'BS',
                'title' => __('Bahamas', 'fluent-crm')
            ],
            [
                'code'  => 'BH',
                'title' => __('Bahrain', 'fluent-crm')
            ],
            [
                'code'  => 'BD',
                'title' => __('Bangladesh', 'fluent-crm')
            ],
            [
                'code'  => 'BB',
                'title' => __('Barbados', 'fluent-crm')
            ],
            [
                'code'  => 'BY',
                'title' => __('Belarus', 'fluent-crm')
            ],
            [
                'code'  => 'BE',
                'title' => __('Belgium', 'fluent-crm')
            ],
            [
                'code'  => 'PW',
                'title' => __('Belau', 'fluent-crm')
            ],
            [
                'code'  => 'BZ',
                'title' => __('Belize', 'fluent-crm')
            ],
            [
                'code'  => 'BJ',
                'title' => __('Benin', 'fluent-crm')
            ],
            [
                'code'  => 'BM',
                'title' => __('Bermuda', 'fluent-crm')
            ],
            [
                'code'  => 'BT',
                'title' => __('Bhutan', 'fluent-crm')
            ],
            [
                'code'  => 'BO',
                'title' => __('Bolivia', 'fluent-crm')
            ],
            [
                'code'  => 'BQ',
                'title' => __('Bonaire, Saint Eustatius and Saba', 'fluent-crm')
            ],
            [
                'code'  => 'BA',
                'title' => __('Bosnia and Herzegovina', 'fluent-crm')
            ],
            [
                'code'  => 'BW',
                'title' => __('Botswana', 'fluent-crm')
            ],
            [
                'code'  => 'BV',
                'title' => __('Bouvet Island', 'fluent-crm')
            ],
            [
                'code'  => 'BR',
                'title' => __('Brazil', 'fluent-crm')
            ],
            [
                'code'  => 'IO',
                'title' => __('British Indian Ocean Territory', 'fluent-crm')
            ],
            [
                'code'  => 'BN',
                'title' => __('Brunei', 'fluent-crm')
            ],
            [
                'code'  => 'BG',
                'title' => __('Bulgaria', 'fluent-crm')
            ],
            [
                'code'  => 'BF',
                'title' => __('Burkina Faso', 'fluent-crm')
            ],
            [
                'code'  => 'BI',
                'title' => __('Burundi', 'fluent-crm')
            ],
            [
                'code'  => 'KH',
                'title' => __('Cambodia', 'fluent-crm')
            ],
            [
                'code'  => 'CM',
                'title' => __('Cameroon', 'fluent-crm')
            ],
            [
                'code'  => 'CA',
                'title' => __('Canada', 'fluent-crm')
            ],
            [
                'code'  => 'CV',
                'title' => __('Cape Verde', 'fluent-crm')
            ],
            [
                'code'  => 'KY',
                'title' => __('Cayman Islands', 'fluent-crm')
            ],
            [
                'code'  => 'CF',
                'title' => __('Central African Republic', 'fluent-crm')
            ],
            [
                'code'  => 'TD',
                'title' => __('Chad', 'fluent-crm')
            ],
            [
                'code'  => 'CL',
                'title' => __('Chile', 'fluent-crm')
            ],
            [
                'code'  => 'CN',
                'title' => __('China', 'fluent-crm')
            ],
            [
                'code'  => 'CX',
                'title' => __('Christmas Island', 'fluent-crm')
            ],
            [
                'code'  => 'CC',
                'title' => __('Cocos (Keeling) Islands', 'fluent-crm')
            ],
            [
                'code'  => 'CO',
                'title' => __('Colombia', 'fluent-crm')
            ],
            [
                'code'  => 'KM',
                'title' => __('Comoros', 'fluent-crm')
            ],
            [
                'code'  => 'CG',
                'title' => __('Congo (Brazzaville)', 'fluent-crm')
            ],
            [
                'code'  => 'CD',
                'title' => __('Congo (Kinshasa)', 'fluent-crm')
            ],
            [
                'code'  => 'CK',
                'title' => __('Cook Islands', 'fluent-crm')
            ],
            [
                'code'  => 'CR',
                'title' => __('Costa Rica', 'fluent-crm')
            ],
            [
                'code'  => 'HR',
                'title' => __('Croatia', 'fluent-crm')
            ],
            [
                'code'  => 'CU',
                'title' => __('Cuba', 'fluent-crm')
            ],
            [
                'code'  => 'CW',
                'title' => __('Cura&ccedil;ao', 'fluent-crm')
            ],
            [
                'code'  => 'CY',
                'title' => __('Cyprus', 'fluent-crm')
            ],
            [
                'code'  => 'CZ',
                'title' => __('Czechia (Czech Republic)', 'fluent-crm')
            ],
            [
                'code'  => 'DK',
                'title' => __('Denmark', 'fluent-crm')
            ],
            [
                'code'  => 'DJ',
                'title' => __('Djibouti', 'fluent-crm')
            ],
            [
                'code'  => 'DM',
                'title' => __('Dominica', 'fluent-crm')
            ],
            [
                'code'  => 'DO',
                'title' => __('Dominican Republic', 'fluent-crm')
            ],
            [
                'code'  => 'EC',
                'title' => __('Ecuador', 'fluent-crm')
            ],
            [
                'code'  => 'EG',
                'title' => __('Egypt', 'fluent-crm')
            ],
            [
                'code'  => 'SV',
                'title' => __('El Salvador', 'fluent-crm')
            ],
            [
                'code'  => 'GQ',
                'title' => __('Equatorial Guinea', 'fluent-crm')
            ],
            [
                'code'  => 'ER',
                'title' => __('Eritrea', 'fluent-crm')
            ],
            [
                'code'  => 'EE',
                'title' => __('Estonia', 'fluent-crm')
            ],
            [
                'code'  => 'ET',
                'title' => __('Ethiopia', 'fluent-crm')
            ],
            [
                'code'  => 'FK',
                'title' => __('Falkland Islands', 'fluent-crm')
            ],
            [
                'code'  => 'FO',
                'title' => __('Faroe Islands', 'fluent-crm')
            ],
            [
                'code'  => 'FJ',
                'title' => __('Fiji', 'fluent-crm')
            ],
            [
                'code'  => 'FI',
                'title' => __('Finland', 'fluent-crm')
            ],
            [
                'code'  => 'FR',
                'title' => __('France', 'fluent-crm')
            ],
            [
                'code'  => 'GF',
                'title' => __('French Guiana', 'fluent-crm')
            ],
            [
                'code'  => 'PF',
                'title' => __('French Polynesia', 'fluent-crm')
            ],
            [
                'code'  => 'TF',
                'title' => __('French Southern Territories', 'fluent-crm')
            ],
            [
                'code'  => 'GA',
                'title' => __('Gabon', 'fluent-crm')
            ],
            [
                'code'  => 'GM',
                'title' => __('Gambia', 'fluent-crm')
            ],
            [
                'code'  => 'GE',
                'title' => __('Georgia', 'fluent-crm')
            ],
            [
                'code'  => 'DE',
                'title' => __('Germany', 'fluent-crm')
            ],
            [
                'code'  => 'GH',
                'title' => __('Ghana', 'fluent-crm')
            ],
            [
                'code'  => 'GI',
                'title' => __('Gibraltar', 'fluent-crm')
            ],
            [
                'code'  => 'GR',
                'title' => __('Greece', 'fluent-crm')
            ],
            [
                'code'  => 'GL',
                'title' => __('Greenland', 'fluent-crm')
            ],
            [
                'code'  => 'GD',
                'title' => __('Grenada', 'fluent-crm')
            ],
            [
                'code'  => 'GP',
                'title' => __('Guadeloupe', 'fluent-crm')
            ],
            [
                'code'  => 'GU',
                'title' => __('Guam', 'fluent-crm')
            ],
            [
                'code'  => 'GT',
                'title' => __('Guatemala', 'fluent-crm')
            ],
            [
                'code'  => 'GG',
                'title' => __('Guernsey', 'fluent-crm')
            ],
            [
                'code'  => 'GN',
                'title' => __('Guinea', 'fluent-crm')
            ],
            [
                'code'  => 'GW',
                'title' => __('Guinea-Bissau', 'fluent-crm')
            ],
            [
                'code'  => 'GY',
                'title' => __('Guyana', 'fluent-crm')
            ],
            [
                'code'  => 'HT',
                'title' => __('Haiti', 'fluent-crm')
            ],
            [
                'code'  => 'HM',
                'title' => __('Heard Island and McDonald Islands', 'fluent-crm')
            ],
            [
                'code'  => 'HN',
                'title' => __('Honduras', 'fluent-crm')
            ],
            [
                'code'  => 'HK',
                'title' => __('Hong Kong', 'fluent-crm')
            ],
            [
                'code'  => 'HU',
                'title' => __('Hungary', 'fluent-crm')
            ],
            [
                'code'  => 'IS',
                'title' => __('Iceland', 'fluent-crm')
            ],
            [
                'code'  => 'IN',
                'title' => __('India', 'fluent-crm')
            ],
            [
                'code'  => 'ID',
                'title' => __('Indonesia', 'fluent-crm')
            ],
            [
                'code'  => 'IR',
                'title' => __('Iran', 'fluent-crm')
            ],
            [
                'code'  => 'IQ',
                'title' => __('Iraq', 'fluent-crm')
            ],
            [
                'code'  => 'IE',
                'title' => __('Ireland', 'fluent-crm')
            ],
            [
                'code'  => 'IM',
                'title' => __('Isle of Man', 'fluent-crm')
            ],
            [
                'code'  => 'IL',
                'title' => __('Israel', 'fluent-crm')
            ],
            [
                'code'  => 'IT',
                'title' => __('Italy', 'fluent-crm')
            ],
            [
                'code'  => 'CI',
                'title' => __('Ivory Coast', 'fluent-crm')
            ],
            [
                'code'  => 'JM',
                'title' => __('Jamaica', 'fluent-crm')
            ],
            [
                'code'  => 'JP',
                'title' => __('Japan', 'fluent-crm')
            ],
            [
                'code'  => 'JE',
                'title' => __('Jersey', 'fluent-crm')
            ],
            [
                'code'  => 'JO',
                'title' => __('Jordan', 'fluent-crm')
            ],
            [
                'code'  => 'KZ',
                'title' => __('Kazakhstan', 'fluent-crm')
            ],
            [
                'code'  => 'KE',
                'title' => __('Kenya', 'fluent-crm')
            ],
            [
                'code'  => 'KI',
                'title' => __('Kiribati', 'fluent-crm')
            ],
            [
                'code'  => 'KW',
                'title' => __('Kuwait', 'fluent-crm')
            ],
            [
                'code'  => 'XK',
                'title' => __('Kosovo', 'fluent-crm')
            ],
            [
                'code'  => 'KG',
                'title' => __('Kyrgyzstan', 'fluent-crm')
            ],
            [
                'code'  => 'LA',
                'title' => __('Laos', 'fluent-crm')
            ],
            [
                'code'  => 'LV',
                'title' => __('Latvia', 'fluent-crm')
            ],
            [
                'code'  => 'LB',
                'title' => __('Lebanon', 'fluent-crm')
            ],
            [
                'code'  => 'LS',
                'title' => __('Lesotho', 'fluent-crm')
            ],
            [
                'code'  => 'LR',
                'title' => __('Liberia', 'fluent-crm')
            ],
            [
                'code'  => 'LY',
                'title' => __('Libya', 'fluent-crm')
            ],
            [
                'code'  => 'LI',
                'title' => __('Liechtenstein', 'fluent-crm')
            ],
            [
                'code'  => 'LT',
                'title' => __('Lithuania', 'fluent-crm')
            ],
            [
                'code'  => 'LU',
                'title' => __('Luxembourg', 'fluent-crm')
            ],
            [
                'code'  => 'MO',
                'title' => __('Macao', 'fluent-crm')
            ],
            [
                'code'  => 'MK',
                'title' => __('North Macedonia', 'fluent-crm')
            ],
            [
                'code'  => 'MG',
                'title' => __('Madagascar', 'fluent-crm')
            ],
            [
                'code'  => 'MW',
                'title' => __('Malawi', 'fluent-crm')
            ],
            [
                'code'  => 'MY',
                'title' => __('Malaysia', 'fluent-crm')
            ],
            [
                'code'  => 'MV',
                'title' => __('Maldives', 'fluent-crm')
            ],
            [
                'code'  => 'ML',
                'title' => __('Mali', 'fluent-crm')
            ],
            [
                'code'  => 'MT',
                'title' => __('Malta', 'fluent-crm')
            ],
            [
                'code'  => 'MH',
                'title' => __('Marshall Islands', 'fluent-crm')
            ],
            [
                'code'  => 'MQ',
                'title' => __('Martinique', 'fluent-crm')
            ],
            [
                'code'  => 'MR',
                'title' => __('Mauritania', 'fluent-crm')
            ],
            [
                'code'  => 'MU',
                'title' => __('Mauritius', 'fluent-crm')
            ],
            [
                'code'  => 'YT',
                'title' => __('Mayotte', 'fluent-crm')
            ],
            [
                'code'  => 'MX',
                'title' => __('Mexico', 'fluent-crm')
            ],
            [
                'code'  => 'FM',
                'title' => __('Micronesia', 'fluent-crm')
            ],
            [
                'code'  => 'MD',
                'title' => __('Moldova', 'fluent-crm')
            ],
            [
                'code'  => 'MC',
                'title' => __('Monaco', 'fluent-crm')
            ],
            [
                'code'  => 'MN',
                'title' => __('Mongolia', 'fluent-crm')
            ],
            [
                'code'  => 'ME',
                'title' => __('Montenegro', 'fluent-crm')
            ],
            [
                'code'  => 'MS',
                'title' => __('Montserrat', 'fluent-crm')
            ],
            [
                'code'  => 'MA',
                'title' => __('Morocco', 'fluent-crm')
            ],
            [
                'code'  => 'MZ',
                'title' => __('Mozambique', 'fluent-crm')
            ],
            [
                'code'  => 'MM',
                'title' => __('Myanmar', 'fluent-crm')
            ],
            [
                'code'  => 'NA',
                'title' => __('Namibia', 'fluent-crm')
            ],
            [
                'code'  => 'NR',
                'title' => __('Nauru', 'fluent-crm')
            ],
            [
                'code'  => 'NP',
                'title' => __('Nepal', 'fluent-crm')
            ],
            [
                'code'  => 'NL',
                'title' => __('Netherlands', 'fluent-crm')
            ],
            [
                'code'  => 'NC',
                'title' => __('New Caledonia', 'fluent-crm')
            ],
            [
                'code'  => 'NZ',
                'title' => __('New Zealand', 'fluent-crm')
            ],
            [
                'code'  => 'NI',
                'title' => __('Nicaragua', 'fluent-crm')
            ],
            [
                'code'  => 'NE',
                'title' => __('Niger', 'fluent-crm')
            ],
            [
                'code'  => 'NG',
                'title' => __('Nigeria', 'fluent-crm')
            ],
            [
                'code'  => 'NU',
                'title' => __('Niue', 'fluent-crm')
            ],
            [
                'code'  => 'NF',
                'title' => __('Norfolk Island', 'fluent-crm')
            ],
            [
                'code'  => 'MP',
                'title' => __('Northern Mariana Islands', 'fluent-crm')
            ],
            [
                'code'  => 'KP',
                'title' => __('North Korea', 'fluent-crm')
            ],
            [
                'code'  => 'NO',
                'title' => __('Norway', 'fluent-crm')
            ],
            [
                'code'  => 'OM',
                'title' => __('Oman', 'fluent-crm')
            ],
            [
                'code'  => 'PK',
                'title' => __('Pakistan', 'fluent-crm')
            ],
            [
                'code'  => 'PS',
                'title' => __('Palestinian Territory', 'fluent-crm')
            ],
            [
                'code'  => 'PA',
                'title' => __('Panama', 'fluent-crm')
            ],
            [
                'code'  => 'PG',
                'title' => __('Papua New Guinea', 'fluent-crm')
            ],
            [
                'code'  => 'PY',
                'title' => __('Paraguay', 'fluent-crm')
            ],
            [
                'code'  => 'PE',
                'title' => __('Peru', 'fluent-crm')
            ],
            [
                'code'  => 'PH',
                'title' => __('Philippines', 'fluent-crm')
            ],
            [
                'code'  => 'PN',
                'title' => __('Pitcairn', 'fluent-crm')
            ],
            [
                'code'  => 'PL',
                'title' => __('Poland', 'fluent-crm')
            ],
            [
                'code'  => 'PT',
                'title' => __('Portugal', 'fluent-crm')
            ],
            [
                'code'  => 'PR',
                'title' => __('Puerto Rico', 'fluent-crm')
            ],
            [
                'code'  => 'QA',
                'title' => __('Qatar', 'fluent-crm')
            ],
            [
                'code'  => 'RE',
                'title' => __('Reunion', 'fluent-crm')
            ],
            [
                'code'  => 'RO',
                'title' => __('Romania', 'fluent-crm')
            ],
            [
                'code'  => 'RU',
                'title' => __('Russia', 'fluent-crm')
            ],
            [
                'code'  => 'RW',
                'title' => __('Rwanda', 'fluent-crm')
            ],
            [
                'code'  => 'BL',
                'title' => __('Saint Barth&eacute;lemy', 'fluent-crm')
            ],
            [
                'code'  => 'SH',
                'title' => __('Saint Helena', 'fluent-crm')
            ],
            [
                'code'  => 'KN',
                'title' => __('Saint Kitts and Nevis', 'fluent-crm')
            ],
            [
                'code'  => 'LC',
                'title' => __('Saint Lucia', 'fluent-crm')
            ],
            [
                'code'  => 'MF',
                'title' => __('Saint Martin (French part)', 'fluent-crm')
            ],
            [
                'code'  => 'SX',
                'title' => __('Saint Martin (Dutch part)', 'fluent-crm')
            ],
            [
                'code'  => 'PM',
                'title' => __('Saint Pierre and Miquelon', 'fluent-crm')
            ],
            [
                'code'  => 'VC',
                'title' => __('Saint Vincent and the Grenadines', 'fluent-crm')
            ],
            [
                'code'  => 'SM',
                'title' => __('San Marino', 'fluent-crm')
            ],
            [
                'code'  => 'ST',
                'title' => __('S&atilde;o Tom&eacute; and Pr&iacute;ncipe', 'fluent-crm')
            ],
            [
                'code'  => 'SA',
                'title' => __('Saudi Arabia', 'fluent-crm')
            ],
            [
                'code'  => 'SN',
                'title' => __('Senegal', 'fluent-crm')
            ],
            [
                'code'  => 'RS',
                'title' => __('Serbia', 'fluent-crm')
            ],
            [
                'code'  => 'SC',
                'title' => __('Seychelles', 'fluent-crm')
            ],
            [
                'code'  => 'SL',
                'title' => __('Sierra Leone', 'fluent-crm')
            ],
            [
                'code'  => 'SG',
                'title' => __('Singapore', 'fluent-crm')
            ],
            [
                'code'  => 'SK',
                'title' => __('Slovakia', 'fluent-crm')
            ],
            [
                'code'  => 'SI',
                'title' => __('Slovenia', 'fluent-crm')
            ],
            [
                'code'  => 'SB',
                'title' => __('Solomon Islands', 'fluent-crm')
            ],
            [
                'code'  => 'SO',
                'title' => __('Somalia', 'fluent-crm')
            ],
            [
                'code'  => 'ZA',
                'title' => __('South Africa', 'fluent-crm')
            ],
            [
                'code'  => 'GS',
                'title' => __('South Georgia/Sandwich Islands', 'fluent-crm')
            ],
            [
                'code'  => 'KR',
                'title' => __('South Korea', 'fluent-crm')
            ],
            [
                'code'  => 'SS',
                'title' => __('South Sudan', 'fluent-crm')
            ],
            [
                'code'  => 'ES',
                'title' => __('Spain', 'fluent-crm')
            ],
            [
                'code'  => 'LK',
                'title' => __('Sri Lanka', 'fluent-crm')
            ],
            [
                'code'  => 'SD',
                'title' => __('Sudan', 'fluent-crm')
            ],
            [
                'code'  => 'SR',
                'title' => __('Suriname', 'fluent-crm')
            ],
            [
                'code'  => 'SJ',
                'title' => __('Svalbard and Jan Mayen', 'fluent-crm')
            ],
            [
                'code'  => 'SZ',
                'title' => __('Swaziland', 'fluent-crm')
            ],
            [
                'code'  => 'SE',
                'title' => __('Sweden', 'fluent-crm')
            ],
            [
                'code'  => 'CH',
                'title' => __('Switzerland', 'fluent-crm')
            ],
            [
                'code'  => 'SY',
                'title' => __('Syria', 'fluent-crm')
            ],
            [
                'code'  => 'TW',
                'title' => __('Taiwan', 'fluent-crm')
            ],
            [
                'code'  => 'TJ',
                'title' => __('Tajikistan', 'fluent-crm')
            ],
            [
                'code'  => 'TZ',
                'title' => __('Tanzania', 'fluent-crm')
            ],
            [
                'code'  => 'TH',
                'title' => __('Thailand', 'fluent-crm')
            ],
            [
                'code'  => 'TL',
                'title' => __('Timor-Leste', 'fluent-crm')
            ],
            [
                'code'  => 'TG',
                'title' => __('Togo', 'fluent-crm')
            ],
            [
                'code'  => 'TK',
                'title' => __('Tokelau', 'fluent-crm')
            ],
            [
                'code'  => 'TO',
                'title' => __('Tonga', 'fluent-crm')
            ],
            [
                'code'  => 'TT',
                'title' => __('Trinidad and Tobago', 'fluent-crm')
            ],
            [
                'code'  => 'TN',
                'title' => __('Tunisia', 'fluent-crm')
            ],
            [
                'code'  => 'TR',
                'title' => __('Turkey', 'fluent-crm')
            ],
            [
                'code'  => 'TM',
                'title' => __('Turkmenistan', 'fluent-crm')
            ],
            [
                'code'  => 'TC',
                'title' => __('Turks and Caicos Islands', 'fluent-crm')
            ],
            [
                'code'  => 'TV',
                'title' => __('Tuvalu', 'fluent-crm')
            ],
            [
                'code'  => 'UG',
                'title' => __('Uganda', 'fluent-crm')
            ],
            [
                'code'  => 'UA',
                'title' => __('Ukraine', 'fluent-crm')
            ],
            [
                'code'  => 'AE',
                'title' => __('United Arab Emirates', 'fluent-crm')
            ],
            [
                'code'  => 'GB',
                'title' => __('United Kingdom (UK)', 'fluent-crm')
            ],
            [
                'code'  => 'US',
                'title' => __('United States (US)', 'fluent-crm')
            ],
            [
                'code'  => 'UM',
                'title' => __('United States (US) Minor Outlying Islands', 'fluent-crm')
            ],
            [
                'code'  => 'UY',
                'title' => __('Uruguay', 'fluent-crm')
            ],
            [
                'code'  => 'UZ',
                'title' => __('Uzbekistan', 'fluent-crm')
            ],
            [
                'code'  => 'VU',
                'title' => __('Vanuatu', 'fluent-crm')
            ],
            [
                'code'  => 'VA',
                'title' => __('Vatican', 'fluent-crm')
            ],
            [
                'code'      => 'VE',
                'title' => __('Venezuela', 'fluent-crm')
            ],
            [
                'code'  => 'VN',
                'title' => __('Vietnam', 'fluent-crm')
            ],
            [
                'code'  => 'VG',
                'title' => __('Virgin Islands (British)', 'fluent-crm')
            ],
            [
                'code'  => 'VI',
                'title' => __('Virgin Islands (US)', 'fluent-crm')
            ],
            [
                'code'  => 'WF',
                'title' => __('Wallis and Futuna', 'fluent-crm')
            ],
            [
                'code'  => 'EH',
                'title' => __('Western Sahara', 'fluent-crm')
            ],
            [
                'code'  => 'WS',
                'title' => __('Samoa', 'fluent-crm')
            ],
            [
                'code'  => 'YE',
                'title' => __('Yemen', 'fluent-crm')
            ],
            [
                'code'  => 'ZM',
                'title' => __('Zambia', 'fluent-crm')
            ],
            [
                'code'  => 'ZW',
                'title' => __('Zimbabwe', 'fluent-crm')
            ],
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
