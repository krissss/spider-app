<?php

namespace spider\jobs\houniao;

use spider\common\ConfigString;
use spider\service\houniao\Spider;
use yii\base\BaseObject;
use yii\queue\JobInterface;
use yii\queue\Queue;

class GetSkuJob extends BaseObject implements JobInterface
{
    public $pages;

    /**
     * @param Queue $queue which pushed and is handling the job
     */
    public function execute($queue)
    {
        try {
            (new Spider())->getSku($this->pages);
        } catch (\Exception $e) {
            sleep(10);
            ConfigString::getQueue()->push(new static([
                'pages' => $this->pages,
            ]));
        }
    }
}