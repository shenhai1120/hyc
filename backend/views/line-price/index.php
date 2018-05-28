<?php

use yii\helpers\Html;
use kartik\grid\GridView;
/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Line Prices';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="line-price-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Create Line Price', ['create'], ['class' => 'btn btn-success']) ?>
    </p> <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],

            'id',
            'starting',
            'destination',
            'startingshow',
            'destinationshow',
            // 'price',
            // 'create_at',
            // 'updated_at',

            ['class' => 'yii\grid\ActionColumn'],
        ],
        'pjax' => true,
        'pjaxSettings'=>['options'=>['enablePushState'=>false,'enableReplaceState'=>false]],
    ]); ?>
</div>
