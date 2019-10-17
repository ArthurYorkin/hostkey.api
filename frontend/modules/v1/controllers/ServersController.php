<?php

namespace app\modules\v1\controllers;

use yii;
use yii\rest\Controller;
use yii\filters\Cors;
use yii\helpers\ArrayHelper;
use common\services\servers\models\ServersMainForm;

class ServersController extends Controller
{

    public function behaviors()
    {
        return ArrayHelper::merge([
            [
                'class' => Cors::className(),
                'cors' => [
                    'Access-Control-Allow-Credentials' => true,
                    'Origin' => Yii::$app->params['alloweddomain'],
                    'Access-Control-Request-Method' => ['GET', 'POST', 'HEAD', 'OPTIONS'],
                ],
                'actions' => [
                    'login' => [
                        'Access-Control-Allow-Credentials' => true,
                    ]
                ]
            ],
            'compositeAuth' => [
                'class' => \yii\filters\auth\CompositeAuth::className(),
                'authMethods' => [
                    \yii\filters\auth\HttpBasicAuth::className(),
                    \yii\filters\auth\HttpBearerAuth::className(),
                    \yii\filters\auth\QueryParamAuth::className(),
                ],
            ],
        ],
            parent::behaviors());
    }
    
    /**
     * Index
     * @return string
     */
    public function actionIndex()
    {
        echo "INDEX";
    }
    
    public function actionGetListServers()
    {
        $model = new ServersMainForm();
        $model->setAction("GetListServers");
        if (Yii::$app->request->method === 'GET' && $model->load(Yii::$app->request->get(), '') && $model->validate()) {
            $model ->initService();
        }

        if (Yii::$app->request->method === 'POST' && $model->load(Yii::$app->request->post(), '') && $model->validate()) {
            $model ->initService();
        }
        return $this->asJson($model->result);
    }
    
}
