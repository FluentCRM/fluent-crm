<?php

namespace FluentCrm\App\Models;

class UrlStores extends Model
{
    protected $table = 'fc_url_stores';


    public static function getUrlSlug($longUrl)
    {
        $longUrl = esc_url_raw($longUrl);
        $isExist = self::where('url', $longUrl)
                    ->first();
        if($isExist) {
            return $isExist->short;
        }

        $short = self::getNextShortUrl();
        // otherwise we have to create
        $data = [
            'url' => $longUrl,
            'short' => $short,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        self::insert($data);

        return $short;
    }

    public static function getNextShortUrl($num = null)
    {
        if($num == null) {
            $lastItem = self::orderBy('id', 'desc')->first();
            if($lastItem) {
                $num = $lastItem->id + 1;
            } else {
                $num = 1;
            }
        }

        $num = $num + 4000; // to make it atleast 3 char

        $chars = apply_filters(FLUENTCRM.'_url_charset','0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ');
        $string = '';
        $len = strlen( $chars );
        while( $num >= $len ) {
            $mod = bcmod( $num, $len );
            $num = bcdiv( $num, $len );
            $string = $chars[ $mod ] . $string;
        }
        return $chars[ intval( $num ) ] . $string;
    }
}
