<?php

namespace spider\jobs\houniao;

use spider\common\ConfigString;
use spider\models\HouNiao;
use spider\service\houniao\Spider;
use yii\base\BaseObject;
use yii\queue\JobInterface;
use yii\queue\Queue;

class DownloadJob extends BaseObject implements JobInterface
{
    /**
     * @param Queue $queue which pushed and is handling the job
     */
    public function execute($queue)
    {
        $model = HouNiao::find()
            ->where(['is not', 'img', null])
            ->andWhere(['is not', 'detail', null])
            ->andWhere(['is_download' => 0])
            ->orderBy('rand()')
            ->limit(1)
            ->one();
        if ($model) {
            (new Spider())->download($model);

            // 循环执行下载
            ConfigString::getQueue()->push(new static());
        } else {
            echo 'stop' . PHP_EOL;
        }
    }
}