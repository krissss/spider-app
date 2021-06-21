<?php

namespace spider\service\ss0;

use GuzzleHttp\Client;
use yii\base\Exception;
use yii\helpers\Json;

class SpiderXCX
{
    private $client;
    private $authorization;

    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => 'https://wx-api.youzu.com/game/374/',
            'timeout' => 5,
        ]);
        $this->authorization = get_env('SS0_AUTHORIZATION');
        if (!$this->authorization) {
            throw new Exception('未知的 SS0_AUTHORIZATION');
        }
    }

    public function userRole()
    {
        return $this->api('user/get-defaultRole', [], [
            'resolveData' => false,
        ]);
    }

    public function taskList()
    {
        return $this->api('welfare/task-list');
    }

    public function sign()
    {
        return $this->api('welfare/sign-gift', [], [
            'resolveData' => false,
        ]);
    }

    public function pickGift(int $taskId)
    {
        return $this->api('welfare/task-gift', [
            'task_id' => $taskId,
        ], [
            'resolveData' => false,
        ]);
    }

    public function battlePlayList()
    {
        return $this->api('battle/play', [
            'num' => 8,
        ]);
    }

    public function battleHeroList()
    {
        return $this->api('battle/hero-list', [
            'hero_type' => 0,
            'profess_type' => 0,
        ]);
    }

    public function battleSubmit(string $title, string $content, int $playId, int $generalId, array $strategistList)
    {
        $detail = [
            ['location' => 0, 'hero_id' => $generalId],
        ];
        for ($i = 1; $i < 17; $i++) {
            $detail[] = ['location' => $i, 'hero_id' => $strategistList[$i - 1] ?? 0];
        }
        return $this->api('battle/submit', [
            'num' => 8,
            'title' => $title,
            'reason' => $content,
            'play_ids' => $playId,
            'details' => json_encode($detail),
        ], [
            'resolveData' => false,
        ]);
    }

    public function battleList(int $page = 1, int $pageSize = 5)
    {
        $data = $this->api('battle/battle-user-list', [
            'play_id' => 0,
            'page' => $page,
            'page_size' => $pageSize,
        ]);
        return $data['list'];
    }

    public function battleComment(int $battleId, string $content)
    {
        return $this->api('battle/battle-user-comment', [
            'battle_id' => $battleId,
            'content' => $content,
        ], [
            'resolveData' => false,
        ]);
    }

    public function articleList(int $page = 1, int $pageSize = 5)
    {
        $data = $this->api('article/listAndCount', [
            'site_id' => 304,
            'cid' => 'sdk_advance_strategy',
            'page' => $page,
            'limit' => $pageSize,
            'lang_id' => 3,
        ]);
        return $data['list'];
    }

    public function articleView(int $articleId)
    {
        return $this->api('browse/article', [
            'id' => $articleId,
            'cid' => 'guide',
        ]);
    }

    public function articlePrize(int $articleId)
    {
        return $this->api('like/article-do', [
            'id' => $articleId,
            'cid' => 'guide',
        ], [
            'resolveData' => false,
        ]);
    }

    public function articlePrizeCancel(int $articleId)
    {
        return $this->api('like/article-cancel', [
            'id' => $articleId,
            'cid' => 'guide',
        ], [
            'resolveData' => false,
        ]);
    }

    public function share()
    {
        return $this->api('share/share', [], [
            'resolveData' => false,
        ]);
    }

    private function api(string $uri, array $params = [], array $config = [])
    {
        $config = array_merge([
            'resolveData' => true,
            'hasVersion' => true,
        ], $config);

        $headers = [
            'Authorization' => 'Bearer ' . $this->authorization,
            'User-Agent' => 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/53.0.2785.143 Safari/537.36 MicroMessenger/7.0.9.501 NetType/WIFI MiniProgramEnv/Windows WindowsWechat',
        ];
        if ($config['hasVersion']) {
            $headers['version'] = 1;
        }
        $response = $this->client->request('GET', $uri, [
            'query' => $params,
            'headers' => $headers,
        ]);
        $data = Json::decode($response->getBody()->getContents());
        if ($config['resolveData']) {
            if ($data['status'] !== 200) {
                throw new Exception($data['msg']);
            }
            return $data['data'];
        }
        return $data;
    }


}