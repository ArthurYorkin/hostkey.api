<?php

namespace app\modules\hardware\controllers;

use Yii;
use yii\filters\VerbFilter;
use common\services\hardware\models\HardwareMainForm;
use yii\rest\Controller;

/**
 * Default controller for the `hardware` module
 */
class ApiController extends Controller
{
    public function behaviors()
    {
        return [
            
            'compositeAuth' => [
                'class' => \yii\filters\auth\CompositeAuth::className(),
                'authMethods' => [
                    \yii\filters\auth\HttpBasicAuth::className(),
                    \yii\filters\auth\HttpBearerAuth::className(),
                    \yii\filters\auth\QueryParamAuth::className(),
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'callback, resending-client-email' => ['GET'],
                    'deploy-set' => ['POST']
                ],
            ],
        ];
    }
    
    /**
     * Action DisableEnablePort
     * @return string
     */
    public function actionDisableEnablePort()
    {
        $model = new HardwareMainForm();
        
        $model->setAction("DisableEnablePort");
        
        if (Yii::$app->request->method === 'GET' && $model->load(Yii::$app->request->get(), '') && $model->validate()) {
            $model ->initService();
        }
        
        if (Yii::$app->request->method === 'POST' && $model->load(Yii::$app->request->post(), '') && $model->validate()) {
            $model ->initService();
        }
        return $this->asJson($model->result);
    }
    
    /**
     * Action DisableEnablePort
     * @return string
     */
    public function actionGetVlan()
    {
        $model = new HardwareMainForm();
        
        $model->setAction("getVlan");
        
        if (Yii::$app->request->method === 'GET' && $model->load(Yii::$app->request->get(), '') && $model->validate()) {
            $model ->initService();
        }
        
        if (Yii::$app->request->method === 'POST' && $model->load(Yii::$app->request->post(), '') && $model->validate()) {
            $model ->initService();
        }
        return $this->asJson($model->result);
    }
}
