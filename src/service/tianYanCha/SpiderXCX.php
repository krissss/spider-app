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

    public function fetchDetail($companyName, $removeBracket = false)
    {
        $searchName = $this->removeBracket($companyName, $removeBracket ? '' : '_');
        $hasBracket = false;
        if (strpos($searchName, '_') !== false) {
            // 存在括号时先查询带括号的
            $hasBracket = true;
            $searchName = $companyName;
        }
        $response = $this->client->request('POST', 'https://capi.tianyancha.com/cloud-other-information/search/app/searchCompany', [
            'json' => [
                'sortType' => 0,
                'pageSize' => 20,
                'pageNum' => 1,
                'word' => $searchName,
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
        // 检查名称是否完全匹配
        $isMatch = $this->removeBracket($data['name']) === $this->removeBracket($searchName);
        // 检查曾用名
        if (!$isMatch && $data['matchField']['field'] === '历史名称') {
            $isMatch = $this->removeBracket($data['matchField']['content']) === $this->removeBracket($searchName);
        }
        if (!$isMatch) {
            if ($hasBracket) {
                return $this->fetchDetail($companyName, true);
            }
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

    /**
     * @param $str
     * @param string $replace
     * @return string
     */
    private function removeBracket($str, $replace = '')
    {
        return str_replace(['(', ')', '（', '）', '<em>', '</em>'], $replace, $str);
    }
}
