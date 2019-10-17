<?php

namespace common\services\gpuvirtual;

/**
 * v1 module definition class
 */
class Service extends \common\services\base\Main
{
    public function setConfig()
    {
        parent::setConfig();
        $configDir = __DIR__ . '/config/' . YII_ENV . '_main.php';
        $this->config['service'] = require $configDir;
        $this->config['service']['config_dir'] = $configDir;
    }
    
}