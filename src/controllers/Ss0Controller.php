<?php

namespace spider\controllers;

use spider\service\ss0\SpiderXCX;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\helpers\ArrayHelper;

class Ss0Controller extends Controller
{
    /**
     * @var SpiderXCX
     */
    private $spider;

    public function init()
    {
        $this->spider = new SpiderXCX();
    }

    // 测试
    public function actionIndex()
    {
        $data = $this->spider->taskList();
        dd($data);
    }

    // 签到
    // php yii ss0/sign
    public function actionSign()
    {
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
                $this->viewAndPrizeArticle($hasCount, $hasCount, true);
                break;
            case 1:
                // 浏览攻略
                $this->viewAndPrizeArticle($hasCount, 0, false);
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

    private $_articleList = [];
    private $_articleIndex = 0;

    private function viewAndPrizeArticle($viewHasCount, $prizeHasCount, $isPrize = false)
    {
        $viewMax = 50;
        $prizeMax = 5;
        $needPrize = $prizeHasCount !== 0 && $prizeHasCount <= $prizeMax;
        $needView = $needPrize || $viewHasCount < $viewMax;
        if (!$needView && !$needPrize) {
            return;
        }

        if (!$this->_articleList) {
            $this->_articleList = $this->spider->articleList(1, max($viewMax, $prizeMax));
        }
        if ($isPrize && $prizeHasCount > $prizeMax) {
            return;
        }

        $articleId = $this->_articleList[$this->_articleIndex]['id'];
        $this->_articleIndex++;
        $data = $this->spider->articleView($articleId);
        $this->writeLn("第{$viewHasCount}次浏览: {$data['id']}");
        if ($needPrize) {
            $data = $this->spider->articlePrize($articleId);
            $this->writeLn("第{$prizeHasCount}次：{$data['msg']}");
            $data = $this->spider->articlePrizeCancel($articleId);
            $this->writeLn("第{$prizeHasCount}次：{$data['msg']}");
            $prizeHasCount++;
        }
        sleep(1);

        $this->viewAndPrizeArticle($viewHasCount + 1, $prizeHasCount);
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