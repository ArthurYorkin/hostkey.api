<?php

namespace common\services\component\models;

use common\services\component\Service;
use common\services\component\base\Component;
use common\services\component\base\ComponentMain;

/**
 * Description of ComponentMainForm
 *
 * @author ArthurYorkin
 */
class ComponentMainForm extends Service
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
    public $vidj;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        /*
                location=us
                language=ru-ru
                pid=2020
                groups=1cpu
                servertype=1
                currency=RUB
                pricerate=1
                template=hostkey-dedicated
                currencycon=CB
                sid=lhj6gf4jj5hgfhj
        */
        $rules[] = [['location', 'pid', 'groups', 'currency', 'pricerate', 'currencycon', 'servertype'], 'required'];
        $rules[] = ['location', 'in', 'range' => ['us', 'ru', 'nl', 'US', 'RU', 'NL']];
        $rules[] = ['pid', 'integer', 'min' => 1, 'max' => 999999];
        $rules[] = ['groups', 'string', 'min' => 3];
        $rules[] = ['currency', 'in', 'range' => ['usd', 'rur', 'eur', 'USD', 'RUR', 'EUR']];
        $rules[] = ['pricerate', 'number'];
        $rules[] = ['currencycon', 'in', 'range' => ['CB', 'cb', 'BR', 'br']];
        $rules[] = ['servertype', 'integer'];
        $rules[] = ['vidj', 'in', 'range' => ['auction']];

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
        $this->component = new Component(new ComponentMain($this));
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
