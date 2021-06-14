<?php

namespace spider\service\tianYanCha;

use GuzzleHttp\Client;

class SpiderXCX
{
    private $client;

    public function __construct()
    {
        $this->client = new Client();
    }

    public function fetchDetail($companyName)
    {
        $response = $this->client->request('POST', 'https://capi.tianyancha.com/cloud-other-information/search/app/searchCompany', [
            'json' => [
                'sortType' => 0,
                'pageSize' => 20,
                'pageNum' => 1,
                'word' => $companyName,
                'allowModifyQuery' => 1,
            ]
        ]);
        if ($response->getStatusCode() !== 200) {
            return null;
        }
        $data = json_decode($response->getBody(), true);
        if ($data['state'] !== 'ok') {
            return null;
        }
        $data = $data['data']['companyList'][0];
        if ($data['name'] !== "<em>{$companyName}</em>") {
            return null;
        }
        return [
            'page_url' => $data['id'],
            '法定代表人' => $data['legalPersonName'],
            '工商注册号' => $data['regNumber'],
            '统一社会信用代码' => $data['creditCode'],
            '纳税人识别号' => $data['creditCode'],
            '组织机构代码' => $data['orgNumber'],
            '注册地址' => $data['regLocation'],
        ];
    }
}