<?php
/**
 * Created by PhpStorm.
 * User: ArthurYorkin
 * Date: 24.09.2019
 * Time: 11:42
 */
namespace common\services\curl;

class Curl
{
    public static function getData($url, $data, $RequestType, $auth) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $RequestType);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Authorization: ' . $auth,
                'Content-Length: ' . strlen($data))
        );
        $output = curl_exec($ch);
        curl_close($ch);
        return $output;
/*
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, $url);
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
*/
    }
}