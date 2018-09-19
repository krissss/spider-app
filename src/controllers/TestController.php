<?php

namespace spider\controllers;

use spider\models\HouNiao;
use spider\service\houniao\Spider;
use yii\console\Controller;

class TestController extends Controller
{
    public function actionIndex()
    {
        $model = HouNiao::findOne(['sku' => 'HN1075504083']);
        (new Spider())->getSpecsPrice($model);
    }
}