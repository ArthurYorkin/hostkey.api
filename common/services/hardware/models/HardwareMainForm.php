<?php
/**
 * Работа со свичами 2950, 2960, 3550, 3560, 3750, 4948
 * включение/выключение, смена скорости, смена влана по SSH
 */
//require_once "/var/www/html/inv3/common/models/Hardware.php";
//ug3.hostkey.ru/?r=dpc/main/hardwaretest&showDataSSH=1&ipSwitch=10.20.0.31&numPort=Gi0/27&serverlinkid=35350

namespace common\services\hardware\models;

use common\services\hardware\Service;
use common\services\hardware\base\Hardware;
use common\services\hardware\base\HardwareMain;

/**
 * Description of HardwareMainForm
 *
 * @author ArthurYorkin
 */
class HardwareMainForm extends Service
{
    private $action;
//    public $response;
    public $hardware;
    public $login;
    public $name;

//    public $config = [];
//    private $testServer = 35138;      //Забиваем id тестового сервера на тот случай, если классу не передан id рабочего сервера, а в лог данные записать нужно

    public $serverId;              //обязательный параметр (если не задан, подменяем на ID тестового сервера)
    public $ipSwitch;              //обязательный параметр
    public $numPort;               //обязательный параметр

//    public $changeSpeedPort = false;    //новое значение скорости порта. если =1, то скорость на порту выставляется в auto, иначе присваивается переданное значение
    public $onoff = "";
//    public $changeVlan = false;
//    public $vlan1 = false;
//    public $vlan2 = false;
//    public $showVlan = false;
//    public $showPortSecurity = false;
//    public $setPortSecurity = false;
//    public $showDataSSH = false;
//    public $prtscr = 0;
//    public $serverlinkid = false;
//
//    public $idNetI;

//Принимает массив входных параметров с ключами
    // $ipSwitch;
    // $numPort;
    // $serverId;
    // $changeSpeedPort;
    // $onoff;
    // $changeVlan;
    // $showVlan;
    // $showPortSecurity;
    // $setPortSecurity;
    // $showDataSSH;
    // $idNetI;


    /**
     * @inheritdoc
     */
    public function rules()
    {
        $rules[] = [['serverId', 'ipSwitch', 'numPort'], 'required'];
        $rules[] = ['serverId', 'integer', 'min' => 1, 'max' => 999999];
        $rules[] = ['ipSwitch', 'ip', 'ipv6' => false];
        $rules[] = ['numPort', 'string', 'min' => 2, 'max' => 10];

        switch ($this->action) {
            case 'DisableEnablePort':
                $rules[] = [['onoff'], 'required'];
                $rules[] = ['onoff', 'in', 'range' => ['porton', 'portoff']];

                break;
            case 'ChangeSpeed':
                $rules[] = [['changeSpeedPort'], 'required'];
                $rules[] = ['changeSpeedPort', 'integer', 'min' => 1, 'max' => 100000000000];

                break;
            case 'ChangeVlan':
                $rules[] = [['vlan1', 'vlan2'], 'required'];
                $rules[] = ['vlan1', 'integer', 'min' => 1, 'max' => 999999];
                $rules[] = ['vlan2', 'integer', 'min' => 1, 'max' => 999999];

                break;
            case 'setPortSecurity':
                $rules[] = [['prtscr'], 'required'];
                $rules[] = ['prtscr', 'integer', 'min' => 1, 'max' => 1000];

                break;
            default :
                break;
        }

        return $rules;
    }

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        $this->hardware = new Hardware(new HardwareMain($this));
    }

    public function setAction($action) {
        return $this->action = $action;
    }

    /**
     * Init service
     * @return boolean
     */
    public function initService()
    {
        $this->callAction();
        return $this->finalService();
    }

    /**
     * Call action
     * @return boolean
     */
    private function callAction()
    {
        if(method_exists($this->hardware, $this->action))
        {
            $func = $this->action;
            $this->result['response'] = $this->hardware->$func();
        } else {
            $this->serviceStatus = false;
            $this->addError( 'S300001', "Метод IPMI не существует" );
            return false;
        }
//        print_r($this->result['response']['errors']); exit;
        if(isset($this->result['response']['errors']) && $this->result['response']['errors']) {
            $this->addErrors($this->result['response']['errors']);
        }
    }

    public function setUser($login, $name) {

        if(!$this->login) {
            $this->login = $login;
        }
        if(!$this->name) {
            $this->name = $name;
        }

        return true;
    }

    /**
     * Final service
     * @return boolean
     */
    public function finalService()
    {
//        echo $this->hasErrors();
        parent::finalService();
        unset($this->hardware);
        return;
    }
}
