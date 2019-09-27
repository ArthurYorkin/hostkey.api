<?php

namespace common\services\inventory\base;

use yii;

/**
 * Выдача конфигурации компонентов для внешнего калькулятора (сервис) по указанным параметрам
 *
 * Новый сервер
 *
 * @package Calc.Api
 * @author Konstantin Shamiev aka ilosa <konstantin@shamiev.ru>
 * @date 2018-05-11
 */
class InventoryApi implements InventoryInterface
{
    /**
     * Если сервер принадлежит двум этим группам, возникает конфликт выбора сетевых компонентов
     * Придется отнести сервер к группе Micro принудительно
     * @var array
     */
    private $group = ["1cpu", "micro"];
    /**
     * @var array
     */
    private $groupPrefer = ["Micro"];
    private $response;
    private $model;
    private $component1;

    /**
     * trestapiMain constructor.
     */
    public function __construct($model = [])
    {
        $this->model = $model;
    }

    /**
     * Выдача конфигурации компонентов для калькулятора по указанным параметрам
     */
    public function GetListServers()
    {
        /**
         * Если сервер принадлежит группам "1cpu" и "Micro", возникает конфликт выбора сетевых компонентов
         * Придется отнести сервер к группе Micro принудительно
         */
        $buffgr = explode(",", mb_strtolower($this->model->groups));
        if ($this->equArr($buffgr, $this->group)) {
            $groups = $this->groupPrefer;
        } else {
            $groups = explode(",", $this->model->groups);
        }

        $groupsCnt = count($groups);
        $groups = "'" . implode("', '", $groups) . "'";

        $sql = "SELECT
		  typ.`ApiCategory` as Category,
          typ.`ApiName` as typ,
          z.ID as `id`,
          z.ID,
          z.Name,
          z.NameShort,
          z.PriceEUR as Price,
          z.Options,
          z.OptionSelf,
          z.OptionTarget,
          z.IsDefault,
          z.Sort,
          z.ComponentType_ID,
          COUNT(DISTINCT link.`ComponentGroup_ID`) as cntGroup,
          COUNT(DISTINCT link2.`dataCenterLocation_ID`) as cntLocation
        FROM Component AS z
          INNER JOIN ComponentType AS typ ON typ.ID = z.`ComponentType_ID`
          INNER JOIN ComponentLink AS link ON z.ID = link.Component_ID AND link.ComponentGroup_ID IN (SELECT ID FROM ComponentGroup WHERE `Code` IN ({$groups}))
          INNER JOIN ComponentLocation AS link2 ON z.ID = link2.Component_ID AND link2.dataCenterLocation_ID IN (SELECT ID FROM dataCenterLocation WHERE `Code` IN ('{$this->model->location}'))
        WHERE
          z.`IsActive` = 'yes'
          AND typ.serverType_ID = {$this->model->servertype}
        GROUP BY
          1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11
        HAVING
          COUNT(DISTINCT link.`ComponentGroup_ID`) = {$groupsCnt}
          AND COUNT(DISTINCT link2.`dataCenterLocation_ID`) = 1
        ORDER BY
          typ.`Category`, typ.`Sort`, z.Sort ASC
        ";
        $connection = Yii::$app->db;
        $result = $connection->createCommand($sql)->queryAll();

        $response = [];
        foreach ($result as $row) {
            if ($row['NameShort'] == '') {
                $row['NameShort'] = $row['Name'];
            }
            // опции целевые
            $options = [];
            foreach (explode(';', $row['OptionTarget']) as $opt) {
                if (!$opt = trim($opt))
                    continue;
                $arr = explode('=', $opt);
                if (count($arr) == 2)
                    $options[strtolower($arr[0])] = $arr[1];
            }
            $row['OptionTarget'] = $options;
            // опции собственные
            $options = [];
            foreach (explode(';', $row['OptionSelf']) as $opt) {
                if (empty($opt)) {
                    continue;
                }
                $arr = explode('=', $opt);
                if (count($arr) == 2)
                    $options[strtolower($arr[0])] = $arr[1];
            }
            $row['OptionSelf'] = $options;

            unset($row['Options']);
            unset($row['cntGroup']);
            unset($row['cntLocation']);

            $response[$row['Category']][$row['typ']][$row['ID']] = $row;
            unset($response[$row['Category']][$row['typ']][$row['ID']]['Category']);
            unset($response[$row['Category']][$row['typ']][$row['ID']]['typ']);
        }

        return $response;
    }

    /**
     * если в двух массивах одинаковые элементы, считаем их эквивалентными
     * порядок элементов и ключи игнорируются
     * @param array $arr1
     * @param array $arr2
     * @return bool
     */
    private function equArr($arr1 = [], $arr2 = [])
    {
        if (count($arr1) <> count($arr2)) {
            return false;
        }

        foreach ($arr1 as $element) {
            if (!in_array($element, $arr2)) {
                return false;
            }
        }
        return true;
    }
}
