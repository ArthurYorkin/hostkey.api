<?php

namespace common\services\inventory\models;

use common\services\inventory\Service;
use common\services\inventory\base\Inventory;
use common\services\inventory\base\InventoryApi;

/**
 *
 * @author ArthurYorkin
 */
class InventoryApiForm extends Service
{
    private $action;
    private $inventory;

    public $location;
    public $groups;
    public $servertype;
//    public $response;


    /**
     * @inheritdoc
     */
    public function rules()
    {
        $rules[] = [['location', 'groups', 'servertype'], 'required'];
        $rules[] = ['location', 'in', 'range' => ['us', 'ru', 'nl', 'US', 'RU', 'NL']];
        $rules[] = ['groups', 'string', 'min' => 3];
        $rules[] = ['servertype', 'integer'];
        return $rules;
    }

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        $this->inventory = new Inventory(new InventoryApi($this));
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
