<?php

namespace common\services\inventory\models;

use common\services\inventory\Service;
use common\services\inventory\base\Inventory;
use common\services\inventory\base\InventoryMain;

/**
 *
 * @author ArthurYorkin
 */
class InventoryMainForm extends Service
{
    private $action;
    private $inventory;

    public $id;
    public $ip;
//    public $response;


    /**
     * @inheritdoc
     */
    public function rules()
    {
//        $rules[] = [['id'], 'required'];
//        $rules[] = [['componentId', 'oborudIp'], 'required'];
        $rules[] = ['id', 'integer', 'min' => 1, 'max' => 999999];
        $rules[] = ['ip', 'ip', 'ipv6' => false];

        switch ($this->action) {
            case 'GetEquip':
//                $rules[] = ['onoff', 'in', 'range' => ['porton', 'portoff']];

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
        $this->inventory = new Inventory(new InventoryMain($this));
    }

    public function setAction($action)
    {
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
        if (method_exists($this->inventory, $this->action)) {
            $this->inventory->validParam();
            $func = $this->action;
            $this->result['response'] = $this->inventory->$func();
        } else {
            $this->serviceStatus = false;
            $this->addError('S400001', "Вызванный метод API не существует");
            return false;
        }
//        print_r($this->result['response']['errors']); exit;
        if (isset($this->result['response']['errors']) && $this->result['response']['errors']) {
            $this->addErrors($this->result['response']['errors']);
        }
    }

    public function setUser($login, $name)
    {

        if (!$this->login) {
            $this->login = $login;
        }
        if (!$this->name) {
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
        unset($this->inventory);
        return;
    }
}
