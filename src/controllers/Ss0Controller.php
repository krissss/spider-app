<?php

namespace spider\controllers;

use spider\service\ss0\GameXCX;
use spider\service\ss0\SpiderXCX;
use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\helpers\ArrayHelper;

class Ss0Controller extends Controller
{
    /**
     * @var SpiderXCX
     */
    private $spider;
    /**
     * @var GameXCX
     */
    private $game;

    // 测试
    public function actionIndex()
    {
        $this->spider = new SpiderXCX();
        $data = $this->spider->taskList();
        dd($data);
    }

    // php yii ss0/full
    public function actionFull()
    {
        $this->actionSign();

        $cache = Yii::$app->cache;
        $thisWeekFirstDate = date('Y-m-d', mktime(0, 0, 0, date('m'), date('d') - date('w') + 1, date('Y')));
        $cacheKey = [__CLASS__, __FUNCTION__, $thisWeekFirstDate, 'v1'];
        if (!$cache->exists($cacheKey)) {
            $this->actionTask();
            $this->actionGameHeCheng();

            $cache->set($cacheKey, 1, 7*24*3600);
        }

        return ExitCode::OK;
    }

    // 签到
    // php yii ss0/sign
    public function actionSign()
    {
        $this->spider = new SpiderXCX();
        $is = $this->checkAuth();
        if (!$is) {
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $data = $this->spider->taskList();
        $index = date('w');
        $index = $index == 0 ? 6 : $index - 1;
        if ($data['sign'][$index]['status'] == 2) {
            $this->writeLn('已签到');
            return ExitCode::OK;
        }
        $data = $this->spider->sign();
        $this->writeLn('签到：' . $data['msg']);
        return ExitCode::OK;
    }

    // 任务
    // php yii ss0/task
    public function actionTask()
    {
        $this->spider = new SpiderXCX();
        $is = $this->checkAuth();
        if (!$is) {
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $max = 30;
        $count = 0;
        while (true) {
            if (++$count > $max) {
                $this->writeLn('超次数结束: ' . $count);
                break;
            }

            $data = $this->spider->taskList();
            $tasks = $data['task'];
            $tasksCount = count($tasks);
            $this->writeLn("第{$count}次获取任务列表，数量：" . $tasksCount);

            ArrayHelper::multisort($tasks, [
                'status', // 优先获取可以领取奖励的
                'type' // 主要为了优先处理点赞，因为点赞包括浏览
            ], [SORT_ASC, SORT_DESC]);
            $canPickGiftTaskId = 0;
            $firstNotOverTask = null;
            foreach ($tasks as $item) {
                if (in_array($item['type'], [3, 4])) {
                    // 忽略战力和等级任务
                    continue;
                }
                if ($item['id'] <= 7) {
                    // 无效的任务
                    continue;
                }
                if ($item['status'] === 1) {
                    // 可领取奖励
                    $canPickGiftTaskId = $item['id'];
                    break;
                }
                if ($firstNotOverTask) {
                    continue;
                }
                if ($item['status'] === 2) {
                    // 已完成
                    continue;
                }
                $firstNotOverTask = $item;
            }
            sleep(1);
            if ($canPickGiftTaskId) {
                $this->pickGift($canPickGiftTaskId);
                continue;
            }
            if ($firstNotOverTask) {
                $this->doTaskItem($firstNotOverTask);
                continue;
            }
            $this->writeLn('所有已完成');
            break;
        }

        return ExitCode::OK;
    }

    // 合成月月兔
    // php yii ss0/game-he-cheng
    public function actionGameHeCheng()
    {
        $this->game = new GameXCX();
        $data = $this->game->heCheng();
        $this->writeLn('合成月月兔：' . $data['msg']);
        if ($data['status'] == 200) {
            Yii::$app->redis->set('SS0_game_he_cheng:' . date('Y-m-d'), implode(',', $data['data']['gift']));

            foreach ($data['data']['gift'] as $item) {
                $this->writeLn($item);
            }
        }
        return ExitCode::OK;
    }

    private function checkAuth(): bool
    {
        $data = $this->spider->userRole();
        if ($data['status'] == 200) {
            $this->writeLn("角色：{$data['data']['role_name']}({$data['data']['server_name']})");
            return true;
        }
        $this->writeLn($data['msg']);
        return false;
    }

    private function doTaskItem($item)
    {
        $this->writeLn("开始任务：{$item['name']}:{$item['desc']}");
        $hasCount = $item['count'];
        switch ($item['type']) {
            case 7:
                // 评论阵容
                $this->commentBattle($hasCount);
                break;
            case 6:
                // 发布阵容
                $this->submitBattle($hasCount);
                break;
            case 5:
                // 分享
                $this->share($hasCount);
                break;
            case 2:
                // 点赞攻略
                $this->prizeArticle($hasCount, $item['num']);
                break;
            case 1:
                // 浏览攻略
                $this->viewArticle($hasCount);
                break;
            default:
                $this->writeLn('忽略');
        }
    }

    private function pickGift(int $taskId)
    {
        $data = $this->spider->pickGift($taskId);
        $this->writeLn("领取奖励[{$taskId}]：{$data['msg']}");
    }

    private $_battleSubmitData = [];
    private $_battleSubmitDataIndex = 0;

    private function submitBattle($hasCount)
    {
        $max = 5;
        if ($hasCount >= $max) {
            return;
        }
        if (!$this->_battleSubmitData) {
            $playList = $this->spider->battlePlayList();
            $heroList = $this->spider->battleHeroList();
            $generalList = array_values($heroList['general']);
            $strategistList = array_values($heroList['strategist']);
            for ($i = 0; $i < $max - $hasCount; $i++) {
                $this->_battleSubmitData[] = [
                    $playList[array_rand($playList)]['id'],
                    $generalList[array_rand($generalList)]['id'],
                    [
                        $strategistList[array_rand($strategistList)]['id'],
                    ],
                ];
            }
        }

        $title = $this->randomContent();
        $battleSubmitData = $this->_battleSubmitData[$this->_battleSubmitDataIndex];
        $this->_battleSubmitDataIndex++;
        $data = $this->spider->battleSubmit($title, $title, ...$battleSubmitData);
        $this->writeLn("第{$hasCount}次：{$data['msg']}");
        sleep(1);

        $this->submitBattle($hasCount + 1);
    }

    private $_battleList = [];
    private $_commentIndex = 0;

    private function commentBattle($hasCount)
    {
        $max = 20;
        if ($hasCount >= $max) {
            return;
        }

        if (!$this->_battleList) {
            $this->_battleList = $this->spider->battleList(1, $max);
        }

        $battleId = $this->_battleList[$this->_commentIndex]['id'];
        $this->_commentIndex++;
        $data = $this->spider->battleComment($battleId, $this->randomContent());
        $this->writeLn("第{$hasCount}次：{$data['msg']}");
        sleep(1);

        $this->commentBattle($hasCount + 1);
    }

    private $_articleViewList = [];
    private $_articleViewIndex = 0;

    private function viewArticle($hasCount)
    {
        $max = 50;
        if ($hasCount >= $max) {
            return;
        }

        if (!$this->_articleViewList) {
            $this->_articleViewList = $this->spider->articleList(1, $max);
        }
        $articleId = $this->_articleViewList[$this->_articleViewIndex]['id'];
        $this->_articleViewIndex++;

        $data = $this->spider->articleView($articleId);
        $this->writeLn("第{$hasCount}次浏览: {$data['id']}");

        sleep(1);

        $this->viewArticle($hasCount + 1);
    }

    private $_articlePrizeList = [];
    private $_articlePrizeIndex = 0;
    private $_pageIndex = 2;

    private function prizeArticle($hasCount, $max)
    {
        if ($hasCount >= $max) {
            return;
        }

        if (!$this->_articlePrizeList) {
            $this->writeLn("获取第{$this->_pageIndex}页数据");
            $this->_articlePrizeList = $this->spider->articleList($this->_pageIndex, 5);
            $this->_pageIndex++;
        }
        if (!isset($this->_articlePrizeList[$this->_articlePrizeIndex])) {
            $this->_articlePrizeList = [];
            $this->_articlePrizeIndex = 0;
            $this->prizeArticle($hasCount, $max);
            return;
        }

        $articleId = $this->_articlePrizeList[$this->_articlePrizeIndex]['id'];
        $this->_articlePrizeIndex++;

        $data = $this->spider->articlePrize($articleId);
        $this->writeLn("第{$hasCount}次：{$data['msg']}");
        sleep(1);
        $data = $this->spider->articlePrizeCancel($articleId);
        $this->writeLn("第{$hasCount}次：{$data['msg']}");
        sleep(1);

        $this->prizeArticle($hasCount + 1, $max);
    }

    private function share($hasCount)
    {
        $max = 30;
        if ($hasCount >= $max) {
            return;
        }

        $data = $this->spider->share();
        $this->writeLn("第{$hasCount}次：{$data['msg']}");
        sleep(1);

        $this->share($hasCount + 1);
    }

    private function randomContent(): string
    {
        $arr = [
            '66666',
            '666666',
            '6666666',
            '66666666',
            '666666666',
            '88888',
            '888888',
            '8888888',
            '88888888',
            '家里借记卡阿萨德看看',
            'qwjjjddd',
            'qweqwe',
        ];
        return $arr[array_rand($arr)];
    }

    private function writeLn($msg)
    {
        $this->stdout($msg . "\n");
    }
}