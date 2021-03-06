<?php
namespace common\controllers;

use Yii;
use yii\web\Controller;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use GatewayClient\Gateway;
use api\models\BindForm;

/**
 * Site controller
 */
class BaseController extends Controller
{
    public function init()
    {
        Gateway::$registerAddress = Yii::$app->params['workerConfig']['registerAddress'];
    }

    public function success($data=[],$message='success')
    {
        return ['code'=>1,'data'=>$data,'message'=>$message];
    }


    public function fail($message='fail',$data=[])
    {
        return ['code'=>0,'data'=>$data,'message'=>$message];
    }


    public function actionBind()
    {
        $model = new BindForm();

        if ($model->load(Yii::$app->request->post(),'') && $model->validate()) {

            // 假设用户已经登录，用户uid和群组id在session中
            $uid      = $model->uid;
            $client_id = $model->sid;
            $group_id = 1;

            // client_id与uid绑定
            Gateway::bindUid($client_id, $uid);
            // 加入某个群组（可调用多次加入多个群组）
            // Gateway::joinGroup($client_id, $group_id);

            return $this->success($model,'绑定成功');
        }
        return $this->fail($model->errors);
    }




}
