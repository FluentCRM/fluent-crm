<?php

namespace FluentCrm\App\Models;

class UrlStores extends Model
{
    protected $table = 'fc_url_stores';

    public static function getUrlSlug($longUrl)
    {
        $isExist = self::where('url', $longUrl)
            ->first();
        if ($isExist) {
            return $isExist->short;
        }

        $short = self::getNextShortUrl();
        // otherwise we have to create
        $data = [
            'url'        => $longUrl,
            'short'      => $short,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        self::insert($data);

        return $short;
    }

    public static function getNextShortUrl($num = null)
    {
        if ($num == null) {
            $lastItem = self::orderBy('id', 'desc')->first();
            if ($lastItem) {
                $num = $lastItem->id + 1;
            } else {
                $num = 1;
            }
        }

        $num = $num + 4000; // to make it atleast 3 char

        $chars = apply_filters(FLUENTCRM . '_url_charset', '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ');
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

    public static function getStringByNumber($num)
    {
        $chars = apply_filters(FLUENTCRM . '_url_charset', 'abcdefghijklm1234567890nopqrstuvwxyz');
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
        return $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."fc_url_stores WHERE BINARY `short` = '".$short."' LIMIT 1");
    }
}
