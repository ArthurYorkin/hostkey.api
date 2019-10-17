<?php

namespace common\services\servers\base;

use yii;
use common\services\curl\Curl;
use common\services\curl\CurrencyCurs;

/**
 * Возвращает список серверов из стокмгр
 * с основными параметрами и ценами
 * @author ArthurYorkin
 */
class ServersMain implements ServersInterface
{
    private $response;
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
    public function GetListServers()
    {
        $cacheID = basename(__FILE__) . $this->model->servertype . $this->model->location . $this->model->groups;
        $customData = Yii::$app->cache->get($cacheID);
//        Yii::$app->cache->delete($cacheID);

        $dubl=false;
        if (!isset($customData)) {
            $dubl=true;
        } elseif (!is_array($customData)) {
            $dubl=true;
        }

        if ($dubl) {
//            $url = "https://stockmgr.hostkey.ru/auction/getdata?AuthUserToken=7096e8ae1455a5a2514f58305249efac&currency=EUR";
            $url = Yii::$app->params['externalurls']['urlStockmgr'] . "/auction/getdata?AuthUserToken=" . $this->token . "&currency={$this->model->currency}&location={$this->model->location}&group={$this->model->groups}";
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
        $resdata=[];
        $currencylist = CurrencyCurs::getCurrency($this->model->currencycon);
        foreach ($arrdata as $k => $v) {
            if ($this->receller() && isset($v->auction)) {
                unset($v->auction);
            }
            foreach ($v as $k1 => $v1) {
                $resdata[$k][$k1] = $v1;
            }
            if (isset($v->price)) {
                $resdata[$k]["priceur"] = $v->price;
                $resdata[$k]["price"] = round($v->price / $currencylist['eur']['rate'] * $currencylist[$this->model->currency]['rate'] * $this->model->pricerate, 2);
            }
            if (isset($v->current_price) && !$this->receller()) {
                $resdata[$k]["current_priceur"] = $v->current_price;
                $resdata[$k]["current_price"] = round($v->current_price / $currencylist['eur']['rate'] * $currencylist[$this->model->currency]['rate'] * $this->model->pricerate, 2);
            }
        }
        return $resdata;
    }

    /**
     * TODO идентификация реселлера
     * для блокировки выдачи сторонним реселлерам цены аукциона
     * реселлер идентифицируется по токену
     * токены хранятся в биллинге
     * @return bool
     */
    private function receller() {
        return false;
    }
}
