<?php

namespace spider\service\ss0;

use Exception;
use GuzzleHttp\Client;
use yii\helpers\Json;

class GameXCX
{
    private $client;
    private $authorization;

    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => 'https://uapi.youzu.com/',
            'timeout' => 5,
        ]);
        $this->authorization = get_env('SS0_GAME_AUTHORIZATION');
        if (!$this->authorization) {
            throw new Exception('未知的 SS0_GAME_AUTHORIZATION');
        }
    }

    public function heCheng()
    {
        return $this->api('act/ss0/xigua/sub', [
            'grade' => 1238,
        ]);
    }

    private function api(string $uri, array $params = [], array $config = [])
    {
        $config = array_merge([
            'resolveData' => false,
        ], $config);

        $headers = [
            'User-Agent' => 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/53.0.2785.143 Safari/537.36 MicroMessenger/7.0.9.501 NetType/WIFI MiniProgramEnv/Windows WindowsWechat',
        ];
        $params['token'] = $this->authorization;
        $response = $this->client->request('POST', $uri, [
            'form_params' => $params,
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