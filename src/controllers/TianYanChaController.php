<?php

namespace spider\controllers;

use spider\models\TianYanCha;
use spider\service\tianYanCha\Spider;
use yii\console\Controller;

class TianYanChaController extends Controller
{
    // 测试用
    public function actionIndex()
    {
        $spider = new Spider();

        $page = $spider->findPage('北京百度网讯科技有限公司');
        //$page = "https://www.tianyancha.com/company/22822";
        $detail = $spider->fetchDetail($page);

        dd($detail);
    }

    // 处理库中的数据
    // php yii tian-yan-cha/db 20210529
    public function actionDb($batchNum)
    {
        $spider = new Spider();

        $models = TianYanCha::find()->where(['batch_num' => $batchNum])->all();
        foreach ($models as $model) {
            $page = $spider->findPage($model->company_name);
            if (!$page) {
                $this->stderr('NotFound: ' . $model->company_name . PHP_EOL);
                continue;
            }
            $detail = $spider->fetchDetail($page);
            $model->page_url = $page;
            $model->leader_person = $detail['法定代表人'];
            $model->num_na_shui_ren = $detail['纳税人识别号'];
            $model->full_address = $detail['注册地址'];
            $model->parseAddress();
            $model->save(false);
            $this->stdout('Success: ' . $model->company_name . PHP_EOL);
        }
    }

    // 解析无省份的地址
    // php yii tian-yan-cha/parse-address 20210529
    public function actionParseAddress($batchNum)
    {
        $models = TianYanCha::find()->where(['batch_num' => $batchNum])->andWhere('province is null')->all();
        foreach ($models as $model) {
            $model->parseAddress();
            $model->save(false);
            $this->stdout('Success: ' . $model->company_name . PHP_EOL);
        }
    }
}