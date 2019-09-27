<?php

namespace common\services\servers\models;

use common\services\servers\Service;
use common\services\servers\base\Servers;
use common\services\servers\base\ServersMain;

/**
 * Description of ServersMainForm
 *
 * @author ArthurYorkin
 */
class ServersMainForm extends Service
{
    public $location;
    public $pid;
    public $groups;
    public $currency;
    public $pricerate;
    public $currencycon;
    public $servertype;
    public $component;
    public $serviceStatus;
    public $componentServiceStatus;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        $rules[] = [['pid', 'currency', 'pricerate', 'currencycon', 'servertype'], 'required'];
        $rules[] = ['location', 'in', 'range' => ['us', 'ru', 'nl', 'US', 'RU', 'NL']];
        $rules[] = ['pid', 'integer', 'min' => 1, 'max' => 999999];
        $rules[] = ['groups', 'string', 'min' => 3];
        $rules[] = ['currency', 'in', 'range' => ['usd', 'rur', 'eur', 'USD', 'RUR', 'EUR']];
        $rules[] = ['pricerate', 'number'];
        $rules[] = ['currencycon', 'in', 'range' => ['CB', 'cb', 'BR', 'br']];
        $rules[] = ['servertype', 'integer'];

        return $rules;
    }

    public function setAction($action)
    {
        return $this->action = $action;
    }

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        $this->component = new Servers(new ServersMain($this));
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
        if (method_exists($this->component, $this->action)) {
            $func = $this->action;
            $this->result['response'] = $this->component->$func();
        } else {
            $this->serviceStatus = false;
            $this->addError('S400001', "Вызванный метод API не существует");
            return false;
        }

        if (isset($this->result['response']['errors']) && $this->result['response']['errors']) {
            $this->addErrors($this->result['response']['errors']);
        }
    }

    /**
     * Final service
     * @return boolean
     */
    public function finalService()
    {

        if (isset($this->response['component']['code']) && $this->response['component']['code'] > 0) {
            $this->addError(20201, $this->response['component']['message']);
            unset($this->response['component']);
        }
        parent::finalService();
        return $this->componentServiceStatus;
    }
}
