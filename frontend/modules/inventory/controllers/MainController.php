<?php

namespace app\modules\inventory\controllers;

use Yii;
use yii\web\Controller;
use common\services\inventory\models\InventoryMainForm;
use common\services\inventory\models\InventoryApiForm;

/**
 * Default controller for the `inventory` module
 */
class MainController extends Controller
{
    public function behaviors()
    {
        return [
//            'compositeAuth' => [
//                'class' => \yii\filters\auth\CompositeAuth::className(),
//                'authMethods' => [
//                    \yii\filters\auth\HttpBasicAuth::className(),
//                    \yii\filters\auth\HttpBearerAuth::className(),
//                    \yii\filters\auth\QueryParamAuth::className(),
//                ],
//            ],
//            'verbs' => [
//                'class' => VerbFilter::className(),
//                'actions' => [
//                    'callback, resending-client-email' => ['GET'],
//                    'deploy-set' => ['POST']
//                ],
//            ],
        ];
    }

    /**
     * Renders the index view for the module
     * @return string
     */
    public function actionIndex()
    {
        return $this->render('index');
    }

    /**
     * Action actionGetEquip
     * @return string
     */
    public function actionGetEquip()
    {
        $model = new InventoryMainForm();

        $model->setAction("GetEquip");

        if (Yii::$app->request->method === 'GET' && $model->load(Yii::$app->request->get(), '') && $model->validate()) {
            $model ->initService();
        }

        if (Yii::$app->request->method === 'POST' && $model->load(Yii::$app->request->post(), '') && $model->validate()) {
            $model ->initService();
        }
        return $this->asJson($model->result);
    }

    public function actionGetNetwork()
    {
        $model = new InventoryMainForm();

        $model->setAction("GetNetwork");

        if (Yii::$app->request->method === 'GET' && $model->load(Yii::$app->request->get(), '') && $model->validate()) {
            $model ->initService();
        }

        if (Yii::$app->request->method === 'POST' && $model->load(Yii::$app->request->post(), '') && $model->validate()) {
            $model ->initService();
        }
        return $this->asJson($model->result);
    }

    public function actionGetClient()
    {
        $model = new InventoryMainForm();

        $model->setAction("GetClient");

        if (Yii::$app->request->method === 'GET' && $model->load(Yii::$app->request->get(), '') && $model->validate()) {
            $model ->initService();
        }

        if (Yii::$app->request->method === 'POST' && $model->load(Yii::$app->request->post(), '') && $model->validate()) {
            $model ->initService();
        }
        return $this->asJson($model->result);
    }

    public function actionGetAll()
    {
        $model = new InventoryMainForm();

        $model->setAction("GetAll");

        if (Yii::$app->request->method === 'GET' && $model->load(Yii::$app->request->get(), '') && $model->validate()) {
            $model ->initService();
        }

        if (Yii::$app->request->method === 'POST' && $model->load(Yii::$app->request->post(), '') && $model->validate()) {
            $model ->initService();
        }
        return $this->asJson($model->result);
    }

    public function actionGetListServers()
    {
        $model = new InventoryApiForm();

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
