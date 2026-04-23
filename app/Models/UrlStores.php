<?php

namespace FluentCrm\App\Models;

/**
 *  UrlStores Model - DB Model for Short Urls
 *
 *  Database Model
 *
 * @package FluentCrm\App\Models
 *
 * @version 1.0.0
 */
class UrlStores extends Model
{
    protected $table = 'fc_url_stores';

    protected $guarded = ['id'];

    public static function getUrlSlug($longUrl)
    {
        // remove zero width space
        $longUrl = str_replace("\xE2\x80\x8B", '', $longUrl);

        static $urls = [];

        if (isset($urls[md5($longUrl)])) {
            return $urls[md5($longUrl)];
        }

        $isExist = self::where('url', $longUrl)
            ->first();

        if ($isExist) {
            $urls[md5($longUrl)] = $isExist->short;
            return $isExist->short;
        }

        $short = self::getNextShortUrl();
        // otherwise we have to create
        $data = [
            'url'        => htmlspecialchars_decode($longUrl),
            'short'      => $short,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        self::insert($data);

        $urls[md5($longUrl)] = $short;

        return $short;
    }

    public static function getNextShortUrl($num = null)
    {
        if ($num == null) {
            $lastItem = self::select(['id'])->orderBy('id', 'desc')->first();
            if ($lastItem) {
                $num = $lastItem->id + 1;
            } else {
                $num = 1;
            }
        }

        $num = $num + 100000; // to make it atleast 4 char
        /**
         * Filter the character set used for URL generation in FluentCRM.
         *
         * This filter allows you to modify the set of characters that will be used
         * when generating URLs in FluentCRM.
         *
         * @since 1.0.0
         *
         * @param string The character set used for URL generation. Default is 'abcdefghijklm1234567890nopqrstuvwxyz'.
         */
        $chars = apply_filters('fluentcrm_url_charset', '0123456789abcdefghijklmnopqrstuvwxyz');

        $string = '';
        $len = strlen($chars);
        while ($num >= $len) {
            if (function_exists('bcmod')) {
                $mod = bcmod($num, $len);
            } else {
                $mod = self::bcmodFallBack($num, $len);
            }
            if (function_exists('bcdiv')) {
                $num = bcdiv($num, $len);
            } else {
                $num = self::bcDivFallBack($num, $len);
            }

            if (isset($chars[$mod])) {
                $string = $chars[$mod] . $string;
            }
        }

        return $chars[intval($num)] . $string;
    }

    public static function getStringByNumber($num)
    {
        /**
         * Filter the character set used for URL generation in FluentCRM.
         *
         * This filter allows you to modify the set of characters that will be used
         * when generating URLs in FluentCRM.
         *
         * @since 1.0.0
         *
         * @param string The character set used for URL generation. Default is 'abcdefghijklm1234567890nopqrstuvwxyz'.
         */
        $chars = apply_filters('fluentcrm_url_charset', 'abcdefghijklm1234567890nopqrstuvwxyz');
        $string = '';
        $len = strlen($chars);
        while ($num >= $len) {
            if (function_exists('bcmod')) {
                $mod = bcmod($num, $len);
            } else {
                $mod = self::bcmodFallBack($num, $len);
            }
            if (function_exists('bcdiv')) {
                $num = bcdiv($num, $len);
            } else {
                $num = self::bcDivFallBack($num, $len);
            }

            $string = $chars[$mod] . $string;
        }
        return $chars[intval($num)] . $string;
    }

    private static function bcmodFallBack($x, $y)
    {
        // how many numbers to take at once? carefull not to exceed (int)
        $take = 5;
        $mod = '';

        do {
            $a = (int)$mod . substr($x, 0, $take);
            $x = substr($x, $take);
            $mod = $a % $y;
        } while (strlen($x));

        return (int)$mod;
    }

    private static function bcDivFallBack($x, $y)
    {
        return floor($x / $y);
    }

    public static function getRowByShort($short)
    {
        $short = esc_sql($short);
        global $wpdb;
        return $wpdb->get_row("SELECT * FROM " . $wpdb->prefix . "fc_url_stores WHERE BINARY `short` = '" . $short . "' ORDER BY `id` DESC LIMIT 1");
    }
}
