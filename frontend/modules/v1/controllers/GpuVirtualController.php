<?php

namespace app\modules\v1\controllers;

use yii;
use yii\rest\Controller;
use yii\filters\Cors;
use yii\helpers\ArrayHelper;
use common\services\gpuvirtual\models\GpuvirtualMainForm;

class GpuVirtualController extends Controller
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

        /*
            return [
                            'corsFilter' => [
                                'class' => \yii\filters\Cors::className(),
                                'cors' => [
                                    'Origin' => '*',
                //                    'Origin' => static::allowedDomains(),
                                    'Access-Control-Allow-Origin' => '*',
                                    'Access-Control-Request-Method' => ['POST', 'GET', 'PUT', 'DELETE', 'OPTIONS'],
                                    'Access-Control-Allow-Credentials' => true,
                                    'Access-Control-Max-Age' => 3600,
                                ],
                            ],


                    'class' => Cors::className(),
                    'cors' => [
                        'Access-Control-Allow-Credentials' => true,
                        'Origin' => ['http://dev-site.hostkey.com'],
                        'Access-Control-Request-Method' => ['GET', 'POST', 'HEAD', 'OPTIONS'],
                    ],
                    'compositeAuth' => [
                        'class' => \yii\filters\auth\CompositeAuth::className(),
                        'authMethods' => [
                            \yii\filters\auth\HttpBasicAuth::className(),
                            \yii\filters\auth\HttpBearerAuth::className(),
                            \yii\filters\auth\QueryParamAuth::className(),
                        ],
                    ],
                ];
        */
    }

    /**
     * Index
     * @return string
     */
    public function actionIndex()
    {
        echo "INDEX";
    }

    public function actionGetListGpuVirtual()
    {
        $model = new GpuVirtualMainForm();
        $model->setAction("GetListGpuVirtual");
        if (Yii::$app->request->method === 'GET' && $model->load(Yii::$app->request->get(), '') && $model->validate()) {
            $model->initService();
        }

        if (Yii::$app->request->method === 'POST' && $model->load(Yii::$app->request->post(), '') && $model->validate()) {
            $model->initService();
        }
        return $this->asJson($model->result);
    }
}
