<?php

namespace backend\controllers;

use Yii;

class CommonController extends \yii\web\Controller
{
    // public $layout = false;
	 /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'islogin' => [
                'class' => \backend\common\behaviors\IsLogin::className(),
                'actions' => '*',
            ],
        ];
    }

    public function actions()
    {
        return [
            'upload' => [
                'class' => 'common\components\actions\upload',
            ],
        ];
    }

    public function actionIndex()
    {
        return $this->render('index');
    }

    public function render($view, $params = [])
    {
        if (Yii::$app->request->getIsAjax()) {
            return $this->renderAjax($view, $params, $this);
        }else{
            return parent::render($view, $params);
        }
    }

}
