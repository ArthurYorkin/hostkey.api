<?php

namespace common\services\inventory\base;

use yii;

/**
 * Возвращает данные из инвентори
 * Запрос:
 * @example ug3.hostkey.ru/?r=inventory/main/get-all&ip=10.77.23.140&id=30867
 * варианты get:
 *  - get-all;
 *  - get-equip;
 *  - get-network;
 *  - get-client;
 *
 * параметры:
 *  id - id оборудования из таблицы Component1
 *  ip - ip адрес оборудования
 *  если переданы оба параметра, приоритет за id
 *  если не передан ни один - ошибка
 */

class InventoryMain implements InventoryInterface
{
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
     * Классу нужно передать хотя бы один из параметров: IP или ID
     * если передан ID, компонент выбирается из таблицы component1 напрямую по ID
     * если передан IP, то вычисляем ID, исходя из IP, а потом выбираем из таблицы component1 по ID
     * @return mixed
     */
    public function validParam()
    {
        if (isset($this->model->id)) {
            $this->component1 = \common\models\Component1::findOne(['id' => $this->model->id]);
        } elseif (isset($this->model->ip)) {
            $this->ip2id($this->model->ip);
            $this->component1 = \common\models\Component1::findOne(['id' => $this->model->id]);
        } else {
            $code = "H2000010";
            $this->setErrors($code, "Error parameters. No ID, no IP. Error code: $code.");
            return $this->response;
            $result['errors'] = true;
            $result['errordescription'] = 'отсутствуют оба входжных параметра: ID, IP';
            return $result;
        }
    }

//возвращает данные по оборудованию
//возвращает IP главного интерфейса (не IPMI)
//    - если пришел запрос IP и выяснилось, что этот IP не главный, то ошибка
//    - IP в запросе предполагает доп.параметр - локацию; IP без локации - это ошибка
    public function GetEquip()
    {
        $equip['equip']['errors'] = false;
        $equip['equip']['id'] = $this->component1['id'];
        $res = $this->id2ip($equip['equip']['id']);
        if ($res['errors']) {
            return $res;
        } else {
            $equip['equip']['ip'] = $res['ip'];
        }

        $equip['equip']['equipment type'] = $this->component1['ref_tableName'];
        $equip['equip']['status'] = $this->component1['Condition_Component'];
        $equip['equip']['inv_id'] = $this->component1['name'];
        $osdata = \common\models\OS::findOne(['id' => $this->component1['os_id']]);
        if (isset($osdata['name'])) {
            $equip['equip']['os_installed'] = $osdata['name'];
        } else {
            $equip['equip']['os_installed'] = "";
        }
        $equip['equip']['deploy_password'] = $this->component1['password'];

        return $equip;
    }

    /*
        name - поле Интерфейс
        vlan { id, location }
        mac
        ip { IP1{ address, ddos(boolean) }, IP2 ... }
    */
    public function GetNetwork()
    {
        $network['network']['errors'] = false;

        $i = 0;
        $netinterface = \common\models\NetInterface1::findAll(['Component_fk_id' => $this->model->id]);
        foreach ($netinterface as $ninterface) {
            $network['network'][$i]['name'] = $ninterface['port_in_Component'];

            $ips = (new yii\db\Query())
                ->from('IpAdress2')
                ->select(['INET_NTOA(`ip`) as `ip`', '`ip` as `longip`'])
                ->where(['NetInterface_fk_id' => $ninterface['id']])
                ->all();

            $j=0;
            $iplist = [];
            foreach ($ips as $ip) {
                $iplist[$j]['address'] = $ip['ip'];

                $nip = $ip['longip'];
                $subnet = (new yii\db\Query())
                    ->from('subnet')
                    ->select(['vlan_fk_id'])
                    ->where(['and', "network<$nip", "broadcast>$nip"])
                    ->all();

                foreach ($subnet as $vlanid) {
                    $vlanlist[] = $vlanid['vlan_fk_id'];
                }

                $vlans = [];
                $vlan = \common\models\Vlans::findAll($vlanlist);
                foreach ($vlan as $vl) {
                    if (!in_array($vl['number'], $vlans)) {
                        $vlans[] = $vl['number'];
                    }
                }
                $iplist[$j]['vlan'] = $vlans;

                $ddos = (new yii\db\Query())
                    ->from('NetWhiteIp')
                    ->where(["Ip" => $ip['ip']])
                    ->count();
                $iplist[$j++]['ddos'] = $ddos > 0 ? true : false;

                $network['network'][$i]['ip'] = $iplist;
            }

            if ($j = 0) {
                $network['network'][$i]['ip'] = [];
            }
            $network['network'][$i]['mac'] = $ninterface['mac'] ?: "";

            $i++;
        }
        return $network;
    }

    public function GetClient()
    {
        $network['client']['errors'] = false;
        $client['client']['email'] = $this->component1['owner'];
        $client['client']['name'] = $this->component1['name_client'];
        return $client;
    }

    public function GetAll()
    {
        $equip = $this->GetEquip();
        $network = $this->GetNetwork();
        $client = $this->GetClient();
        return [$equip, $network, $client];
    }

    /**
     * Найти IP главного интефейса сервера
     * принимает id сервера из Component1, возвращает IP главного интерфейса
     * @param $id
     * @return mixed
     */
    private function id2ip($id)
    {
        $result['errors'] = false;
        $netinterface = \common\models\NetInterface1::findAll(['Component_fk_id' => $id]);

        if (count($netinterface) > 1) {
            foreach ($netinterface as $netintr) {
                $maininterface = false;
                if ($netintr['IsMain'] == 1) {
                    $maininterface = $netintr;
                    break;
                }
            }
        } elseif (count($netinterface) == 1) {
            $maininterface = $netinterface[0];
        } else {
            $code = "H2000013";
            $this->setErrors($code, "Количество главных сетевых интерфейсов равно " . count($netinterface) . " Error code: $code.");
            return $this->response;
        }

        if ($maininterface) {
            if (strtolower($maininterface['port_in_Component']) == 'ipmi') {
                $result['errors'] = true;
                $result['errordescription'] = 'тип главного сетевого интерфейса IPMI';
                return $result;
            }
            $ip = (new yii\db\Query())
                ->from('IpAdress2')
                ->select(['INET_NTOA(`ip`) as `ip`', '`main_IP`'])
                ->where(['NetInterface_fk_id' => $maininterface['id']])
                ->all();

            if (count($ip) == 1) {
                $result['ip'] = $ip[0]['ip'];
            } else {
                $result['ip'] = "";
                foreach ($ip as $curip) {
                    if ($curip['main_IP'] > 0) {
                        $result['ip'] = $curip['ip'];
                        break;
                    }
                }
            }
        } else {
            $code = "H2000011";
            $this->setErrors($code, "Обнаружено количество главных сетевых интерфейсов не равное единице. Количество " . count($netinterface) . " Error code: $code.");
            return $this->response;
//            $result['errors'] = true;
//            $result['errordescription'] = 'количество главных сетевых интерфейсов оборудования: ' . count($netinterface);
        }
        return $result;
    }

    /**
     * Найти ID оборудования
     * принимает IP оборудования, возвращает ID из таблицы Component1
     * записывает найденный IP в @var $this->model->id
     * @param $ip
     * @return mixed
     */
    private function ip2id($ip)
    {
        $ips = (new yii\db\Query())
            ->from(['ipadr' => 'IpAdress2'])
            ->select(['`net`.`Component_fk_id` as `comp_id`'])
            ->innerJoin(['net' => 'NetInterface1'], '`ipadr`.`NetInterface_fk_id` = `net`.`id`')
            ->where(['INET_NTOA(`ipadr`.`ip`)' => $ip])
            ->all();

        if (count($ips) == 1) {
            $this->model->id = $ips[0]['comp_id'];
//            $netinterface = \common\models\NetInterface1::findAll(['Component_fk_id' => $ips[0]['comp_id']]);
        } else {
            $code = "H2000012";
            $this->setErrors($code, "Обнаружено количество главных сетевых интерфейсов не равное единице. Количество " . count($ips) . " Error code: $code.");
            return $this->response;
        }
    }

    /**
     * Set errors
     * @param mixed $errorNumber
     * @param string $errorMsg
     * @return array
     */
    private function setErrors($errorNumber, $errorMsg)
    {
        $this->response['errors'][$errorNumber] = $errorMsg;
        return $this->response['errors'];
    }
}
