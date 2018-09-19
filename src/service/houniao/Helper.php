<?php

namespace spider\service\houniao;

use yii\httpclient\Request;

class Helper extends \spider\common\Helper
{
    public static function getRequestHeader()
    {
        return [
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8',
            //'Accept-Encoding' => 'gzip, deflate', // jsonp 的请求会失败
            'Accept-Language' => 'zh-CN,zh;q=0.9',
            'Cookie' => 'PHPSESSID=o8u2u2hb9o5gr2vop66guqnqv1; HN_account=15061528313; HN_thor=w0fvi5P6lrsIGkBJN6biNTNV7cH2ok9OBZEoybpyNs5KNFcouwmBHUHv8u4D72M1ddE4voSoZmuVt4nLqLdGv42Gv-tq-qXygt5MhuRqCOfiAEDKWTbidVpnS5a4eyDjtI4ct0VEc6ZWuwhSpbSfT3NccMay9I4mDCyw3b65Mwo5rAl3cS1lHvNi9hejr3l7jZX4TsGVQvrymspDc_xL8YlP3mnu2laNhagyKJ4bkwnrT7yO57RDWgjBtLqjvPXh',
            'Host' => 'houniao.hk',
            'Origin' => 'http://houniao.hk',
            'Referer' => 'http://houniao.hk/home/product/detail?itemSku=HN1075504071',
            'X-Requested-With' => 'XMLHttpRequest',
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/63.0.3239.132 Safari/537.36',
        ];
    }

    /**
     * @param Request $clientRequest
     * @return Request
     */
    public static function httpClientWithCookie(Request $clientRequest)
    {
        $cookies = explode(';', static::getRequestHeader()['Cookie']);
        $cookieArr = [];
        foreach ($cookies as $cookie) {
            $arr = explode('=', $cookie);
            $cookieArr[] = [
                'name' => trim($arr[0]),
                'value' => $arr[1],
            ];
        }
        return $clientRequest->setCookies($cookieArr);
    }
}