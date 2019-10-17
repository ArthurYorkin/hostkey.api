<?php

namespace common\services\gpuvirtual\base;

use yii;
use common\services\curl\Curl;
use common\services\curl\CurrencyCurs;

/**
 * Возвращает список виртуальных GPU шаблонов из стокмгр
 * с основными параметрами и ценами
 * @author ArthurYorkin
 */
class GpuvirtualMain implements GpuvirtualInterface
{
    private $model;
    private $token;
    /**
     * Время хранения кеша инвентори (1800 сеунд - полчаса)
     * @var int
     */
    private $cachetime30min = 1800;

    public function __construct($model = [])
    {
        $this->model = $model;
        $this->model->currencycon = strtolower($this->model->currencycon);
        $this->model->currency = strtolower($this->model->currency);
        $this->token = $this->model->config['service']['token'];
    }

    /**
     * извлекает из кэша список серверов
     * если в кеше данные не найдены, обращается в стокмгр
     * @return array|mixed
     */
    public function GetListGpuVirtual()
    {
        if (strtolower($this->model->location)=='all') {
            $this->model->location="";
        }
        $cacheID = basename(__FILE__) . $this->model->servertype . $this->model->location . $this->model->groups;
//        Yii::$app->cache->delete($cacheID);
        $customData = Yii::$app->cache->get($cacheID);

        $dubl=false;
        if (!isset($customData)) {
            $dubl=true;
        } elseif (!is_array($customData)) {
            $dubl=true;
        }

        if ($dubl) {
            $url = Yii::$app->params['externalurls']['urlStockmgr'] . "/auction/getdatagpuvirtual?AuthUserToken=" . $this->token . "&currency={$this->model->currency}&location={$this->model->location}&group={$this->model->groups}";
            $customData = Curl::getData($url, "", "GET", "");
            if ($customData) {
                Yii::$app->cache->set($cacheID, $customData, $this->cachetime30min);
            }
        }

        $arrdata = json_decode($customData);
        $arrdata = $this->recalculatestock($arrdata);
        return $arrdata;
    }




    /**
     * Пересчет из евро в нац.валюту и добавление элементов с ценами в нац.валюте в массив стоковых серверов
     * @param $arrdata
     * @return mixed
     */
    private function recalculatestock($arrdata)
    {
        $resdata = [];
        $currencylist = CurrencyCurs::getCurrency($this->model->currencycon);
        foreach ($arrdata as $k => $v) {
            foreach ($v as $k1 => $v1) {
                $resdata[$k][$k1] = $v1;
            }
            if (isset($v->price)) {
                $resdata[$k]["priceur"] = $v->price;
                $resdata[$k]["price"] = round($v->price / $currencylist['eur']['rate'] * $currencylist[$this->model->currency]['rate'] * $this->model->pricerate, 2);
            }
        }
        return $resdata;
    }
}
