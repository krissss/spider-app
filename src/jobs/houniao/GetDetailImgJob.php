<?php

namespace spider\jobs\houniao;

use spider\service\houniao\Spider;
use yii\base\BaseObject;
use yii\queue\JobInterface;
use yii\queue\Queue;

class GetDetailImgJob extends BaseObject implements JobInterface
{
    public $goodsIdArr;

    /**
     * @param Queue $queue which pushed and is handling the job
     */
    public function execute($queue)
    {
        (new Spider())->getDetailImg($this->goodsIdArr);
    }
}