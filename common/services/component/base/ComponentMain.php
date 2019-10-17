<?php

namespace common\services\component\base;

use yii;
use common\services\curl\Curl;
use common\services\curl\CurrencyCurs;

/**
 * Description of Component
 * @author ArthurYorkin
 */
class ComponentMain implements ComponentInterface
{
    private $model;
    /**
     * @var int
     * Время хранения кеша инвентори (1800 сеунд - полчаса)
     */
    private $cachetime30min = 1800;
    private $ugrequest="/?r=inventory/main/get-list-servers&";
//    private $ugrequest="/api/v2/calc/custom?";

    /**
     * ComponentMain constructor.
     * @param array $model
     */
    public function __construct($model = [])
    {
        $this->model = $model;
        $this->model->currencycon = strtolower($this->model->currencycon);
        $this->model->currency = strtolower($this->model->currency);
    }

    /**
     * извлекает из кэша данные по группе компонентов
     * если в кеше данные не найдены, обращается в инвентори
     * @return array|mixed
     */
    public function GetComponents()
    {
        $cacheID = basename(__FILE__) . $this->model->servertype . $this->model->location . $this->model->groups;
//        Yii::$app->cache->delete($cacheID);
        $component = Yii::$app->cache->get($cacheID);

        $dubl=false;
        if (!isset($component)) {
            $dubl=true;
        } elseif (!is_array($component)) {
            $dubl=true;
        }

        if ($dubl) {
            $url = Yii::$app->params['externalurls']['urlUg'] . $this->ugrequest ."groups={$this->model->groups}&location={$this->model->location}&servertype={$this->model->servertype}";
            $component = Curl::getData($url, "", "GET", "");
            if ($component) {
                Yii::$app->cache->set($cacheID, $component, $this->cachetime30min);
            }
        }

        $customData = json_decode($component, true);
        if (isset($customData['response'])) {
            $customData=$customData['response'];
        }
        $this->delrn($customData);
        $customData = $this->recalculate($customData);

        return $customData;
    }

    /**
     * - пересчитывает цены в нац.валюту перед окончательной выдачей
     * - убирает из выдачи компоненты битности ОС (2019.09.16)
     * - убирает из выдачи компоненты ['OptionSelf']['none'] == 'sale' при выдаче в виджет "аукцион"
     *   такие компоненты нужны виджету "кастомизатор", но мешают виджету "аукцион" (2019.09.02)
     * @param array $customData - необработанный массив для выдачи виджету
     * @return array- обработанный массив для выдачи виджету
     */
    private function recalculate($customData)
    {
        $currency = CurrencyCurs::getCurrency($this->model->currencycon);

        foreach ($customData as $category => $components) {
            foreach ($components as $comp => $rows) {
                if ($comp == 'Bit' || $comp == 'Bandwidth') {
                    unset($customData[$category][$comp]);
                    continue;
                }
                foreach ($rows as $id => $row) {
                    if (isset($this->model->vidj) && $this->model->vidj == 'auction') {
                        if ($customData[$category][$comp][$id]['OptionSelf']['none'] == 'sale') {
                            unset($customData[$category][$comp][$id]);
                            continue;
                        }
                    }

                    $customData[$category][$comp][$id]['PriceEUR'] = $row['Price'];
                    // $customData[$category][$comp][$id]['Price'] = round($row['Price'] * $currency * $_REQUEST['pricerate'], 2);
                    $customData[$category][$comp][$id]['Price'] = round($row['Price'] / $currency['eur']['rate'] * $currency[$this->model->currency]['rate'] * $this->model->pricerate, 2);
                }
            }
        }
        return $customData;
    }

    /**
     * @param $arr
     * рекурсивный метод, удаляет из массива все вхождения переносов строк в ключах и значениях
     */
    private function delrn(&$arr)
    {
        foreach ($arr as $k => &$v) {
            if (!(strpos($k, chr(13) . chr(10)) === false)) {
                $newk = str_replace(chr(13) . chr(10), '', $k);
                $arr[$newk] = $v;
                unset($arr[$k]);
                $k = $newk;
            }

            if (is_array($v)) {
                $this->delrn($v);
            } else {
                if (!(strpos($v, chr(13) . chr(10)) === false)) {
                    $arr[$k] = str_replace(chr(13) . chr(10), '', $v);
                }
            }
        }
    }
}
