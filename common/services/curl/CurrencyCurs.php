<?php
/**
 * Created by PhpStorm.
 * User: ArthurYorkin
 * Date: 24.09.2019
 * Time: 17:12
 */

namespace common\services\curl;

use yii;

class CurrencyCurs
{
    public static function getCurrency($currencycon)
    {
        if ($currencycon == 'cb') {
            $currency=self::currencyWork('currencyCB', Yii::$app->params['externalurls']['urlBillingRu'] . '/api/v1/general/currency');
        } elseif ($currencycon == 'br') {
            $currency=self::currencyWork('currencyBR', Yii::$app->params['externalurls']['urlBillingCom'] . '/api/v1/general/currency');
        } else {
            $currency=[];
        }
        return $currency;
    }

    private static function currencyWork($namecache, $billpath)
    {
        $cachetime24hours = 86400;                  //Время хранения кеша курса валют (24 часа)
        $currency = Yii::$app->cache->get($namecache);
        if ($currency === false) {
            $currency = [];
            $data = Curl::getData($billpath, "", 'GET', "");
            $data = json_decode($data, true);
            foreach ($data as $arr) {
                $currency[strtolower($arr['code'])] = $arr;
            }
            Yii::$app->cache->set($namecache, $currency, $cachetime24hours);
        }
        return $currency;
    }
}