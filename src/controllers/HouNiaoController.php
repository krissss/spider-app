<?php

namespace spider\controllers;

use spider\common\ConfigString;
use spider\jobs\houniao\DownloadJob;
use spider\jobs\houniao\GetDetailImgJob;
use spider\jobs\houniao\GetDetailJob;
use spider\jobs\houniao\GetSkuJob;
use spider\jobs\houniao\GetSpecsPriceJob;
use spider\models\HouNiao;
use yii\console\Controller;
use yii\helpers\ArrayHelper;

class HouNiaoController extends Controller
{
    public function actionIndex()
    {
        $totalSkuCount = HouNiao::find()->select(['count' => 'count(*)'])->scalar();
        $noDetailCount = HouNiao::find()->select(['count' => 'count(*)'])->where(['title' => null])->orWhere(['specs' => null])->scalar();
        $noDetailImgCount = HouNiao::find()->select(['count' => 'count(*)'])->where(['detail' => null])->scalar();
        $noSpecsPriceCount = HouNiao::find()->select(['count' => 'count(*)'])->where(['specs_prices' => null])->scalar();
        $noDownloadCount = HouNiao::find()->select(['count' => 'count(*)'])->where(['is_download' => 0])->scalar();

        $data = [
            '总SKU' => $totalSkuCount,
            '没有详情的' => $noDetailCount,
            '没有详情图片的' => $noDetailImgCount,
            '没有价格的' => $noSpecsPriceCount,
            '没有下载的' => $noDownloadCount,
        ];
        foreach ($data as $name => $value) {
            echo $name . ':' . $value . PHP_EOL;
        }
    }

    // 最多跑一个worker
    public function actionSpiderSku()
    {
        $max = 1178;
        $arr = [];
        for ($i = 1; $i < $max; $i++) {
            $arr[] = $i;
            if ($i % 100 == 0) {
                ConfigString::getQueue()->push(new GetSkuJob(['pages' => $arr]));
                $arr = [];
            }
        }
        if ($arr) {
            ConfigString::getQueue()->push(new GetSkuJob(['pages' => $arr]));
        }
    }

    // 最多跑一个 2-3 个worker
    public function actionSpiderDetail()
    {
        foreach (HouNiao::find()->select(['sku'])->where(['goods_id' => null])->orWhere(['specs' => null])->batch(100) as $models) {
            ConfigString::getQueue()->push(new GetDetailJob([
                'skuArr' => ArrayHelper::getColumn($models, 'sku'),
            ]));
        }
    }

    // 可以跑 3-4 个worker
    public function actionSpiderDetailImg()
    {
        foreach (HouNiao::find()->select(['goods_id'])->where(['detail' => null])->batch(50) as $models) {
            ConfigString::getQueue()->push(new GetDetailImgJob([
                'goodsIdArr' => ArrayHelper::getColumn($models, 'goods_id'),
            ]));
        }
    }

    // 可以跑 3-4 个 worker，注意 cpu 占用会比较高
    public function actionSpiderSpecsPrice()
    {
        for ($i = 0; $i < 5; $i++) {
            ConfigString::getQueue()->push(new GetSpecsPriceJob());
        }
    }

    public function actionSpiderDownload()
    {
        for ($i = 0; $i < 10; $i++) {
            ConfigString::getQueue()->push(new DownloadJob());
        }
    }
}