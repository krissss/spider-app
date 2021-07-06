<?php

namespace spider\controllers;

use spider\models\TianYanCha;
use spider\service\tianYanCha\Spider;
use spider\service\tianYanCha\SpiderXCX;
use yii\console\Controller;

class TianYanChaController extends Controller
{
    // 测试用
    public function actionIndex()
    {
        $spider = new SpiderXCX();

        $data = $spider->fetchDetail('北京百度网讯科技有限公司');
        var_dump($data);
        echo PHP_EOL;
        return 0;
    }

    // 小程序接口
    // php yii tian-yan-cha/db-xcx 20210529
    public function actionDbXcx($batchNum)
    {
        $spider = new SpiderXCX();

        $models = TianYanCha::find()
            ->where(['batch_num' => $batchNum])
            ->andWhere('page_url is null')
            ->all();
        $notFoundCount = 0;
        foreach ($models as $model) {
            $detail = $spider->fetchDetail($model->company_name);
            if ($detail === null) {
                $model->page_url = 'no';
                $model->save(false);
                $this->stderr("Not Found: {$model->id}.{$model->company_name}" . PHP_EOL);
                $notFoundCount++;
                if ($notFoundCount >= 3) {
                    $this->stderr('连续获取失败' . PHP_EOL);
                    break;
                }
                continue;
            }
            $notFoundCount = 0;
            $model->page_url = $detail['page_url'];
            $model->leader_person = $detail['法定代表人'];
            $model->num_na_shui_ren = $detail['纳税人识别号'];
            $model->reg_money = $detail['注册资金'];
            $model->full_address = $detail['注册地址'];
            $model->parseAddress();
            $model->save(false);
            $this->stdout("Success: {$model->id}.{$model->company_name}" . PHP_EOL);
            //sleep(random_int(10, 20));
        }

        return 0;
    }

    // 处理库中的数据
    // php yii tian-yan-cha/db 20210529
    public function actionDb($batchNum)
    {
        $spider = new Spider();

        $models = TianYanCha::find()
            ->where(['batch_num' => $batchNum])
            ->andWhere('page_url is null')
            ->all();
        $notFoundCount = 0;
        foreach ($models as $model) {
            $page = $spider->findPage($model->company_name);
            if (!$page) {
                $model->page_url = 'no';
                $model->save(false);
                $this->stderr("Not Found: {$model->id}.{$model->company_name}" . PHP_EOL);
                $notFoundCount++;
                if ($notFoundCount >= 3) {
                    $this->stderr('连续获取失败' . PHP_EOL);
                    break;
                }
                continue;
            }
            $notFoundCount = 0;
            $detail = $spider->fetchDetail($page);
            $model->page_url = $page;
            $model->leader_person = $detail['法定代表人'];
            $model->num_na_shui_ren = $detail['纳税人识别号'];
            $model->full_address = $detail['注册地址'];
            $model->parseAddress();
            $model->save(false);
            $this->stdout("Success: {$model->id}.{$model->company_name}" . PHP_EOL);
            sleep(random_int(10, 20));
        }

        return 0;
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