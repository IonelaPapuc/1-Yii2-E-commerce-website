<?php

use common\models\Order;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\ActionColumn;
use yii\grid\GridView;

/** @var yii\web\View $this */
/** @var backend\models\search\OrderSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */

$this->title = 'Orders';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="order-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Create Order', ['create'], ['class' => 'btn btn-success']) ?>
    </p>
    <i class="fas fa-chevron-down"></i>

    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

    <?= GridView::widget([
            'id'=>'ordersTable',
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'pager'=>[
            'class' => \yii\bootstrap5\LinkPager::class
],
        'columns' => [
[

            'attribute'=>'id',
            'contentOptions'=>['style'=>'width:80px']],


            [

                    'attribute'=>'fullname',
                    'content'=> function($model){
        return $model->firstname .' ' . $model->lastname;
                    }
],

            'total_price:currency',

            [
                    'attribute' => 'status',
                'content' => function($model){

        if($model->status == \common\models\Order::STATUS_COMPLETED) {

            return \yii\bootstrap5\Html::tag('span', 'Completed', ['class'=>'badge badge-success']);
                     } else if ($model->status == \common\models\Order::STATUS_COMPLETED){
            return \yii\bootstrap5\Html::tag('span', 'Unpaid', ['class'=>'badge badge-secondary']);
        }else{
            return \yii\bootstrap5\Html::tag('span', 'Failured', ['class'=>'badge badge-danger']);
        }
                }],
            'created_at:datetime',
            //'email:email',
            //'transaction_id',
            //'paypal_order_id',
            //'created_at',
            //'created_by',
            [
                'class' => ActionColumn::className(),
               // 'urlCreator' => function ($action, Order $model, $key, $index, $column) {
                  //  return Url::toRoute([$action, 'id' => $model->id]);
              //   }

            ],
        ],
    ]); ?>


</div>
