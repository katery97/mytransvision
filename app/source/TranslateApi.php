<?php

class TranslateApi{

    const APP_ID = '20201210000643600';

    const APP_SEC_KEY = 'FhlWZyFhpXVDL3w7I_DN';

    const API_URI = 'http://fanyi-api.baidu.com/api/trans/vip/translate';

    const LANGUAGE_LIST = [
        'zh-CN' => 'zh',
        'en-US' => 'en',
        'fr' => 'fra',
        'de' => 'de',
        'jp' => 'jp'
    ];

    /**
     * @param $str
     * @param $lan
     * @return string
     */
    public static function run($str, $lan)
    {
        $random = rand(1000, 9999);

        $param = [
            'q'     => $str,
            'from'  => 'auto',
            'to'    => self::LANGUAGE_LIST[$lan],
            'appid' => self::APP_ID,
            'salt'  => $random,
            'sign'  => md5(self::APP_ID . $str . $random . self::APP_SEC_KEY)
        ];

        $res = self::call(self::API_URI, $param);

        return $res ? json_decode($res, true)['trans_result'][0]['dst'] : '';
    }


    /**
     * @param $url
     * @param null $args
     * @param string $method
     * @param bool $withCookie
     * @param string $timeout
     * @param array $headers
     * @return bool|string
     */
    public static function call($url, $args=null, $method="post", $withCookie = false, $timeout = '120', $headers=array())
    {
        $ch = curl_init();
        if($method == "post") {
            $data = self::convert($args);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_POST, 1);
        } else {
            $data = self::convert($args);
            if($data)
            {
                if(stripos($url, "?") > 0)
                {
                    $url .= "&$data";
                }
                else
                {
                    $url .= "?$data";
                }
            }
        }
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        if(!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        if($withCookie) {
            curl_setopt($ch, CURLOPT_COOKIEJAR, $_COOKIE);
        }
        $r = curl_exec($ch);
        curl_close($ch);
        return $r;
    }


    /**
     * @param $args
     * @return string
     */
    public static function convert(&$args){
        $data = '';
        if (is_array($args))
        {
            foreach ($args as $key=>$val)
            {
                if (is_array($val))
                {
                    foreach ($val as $k=>$v)
                    {
                        $data .= $key.'['.$k.']='.rawurlencode($v).'&';
                    }
                }
                else
                {
                    $data .="$key=".rawurlencode($val)."&";
                }
            }
            return trim($data, "&");
        }
        return $args;
    }

}

