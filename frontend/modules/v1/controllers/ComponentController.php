<?php

namespace app\modules\v1\controllers;

use yii;
use yii\rest\Controller;
use common\services\component\models\ComponentMainForm;

class ComponentController extends Controller
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
        ];
    }
    
    /**
     * Index
     * @return string
     */
    public function actionIndex()
    {
        echo "INDEX";
    }
    
    public function actionGetAll()
    {
        $model = new ComponentMainForm();
        $model->setAction("GetComponents");
        if (Yii::$app->request->method === 'GET' && $model->load(Yii::$app->request->get(), '') && $model->validate()) {
            $model ->initService();
        }

        if (Yii::$app->request->method === 'POST' && $model->load(Yii::$app->request->post(), '') && $model->validate()) {
            $model ->initService();
        }
        return $this->asJson($model->result);
    }
}
