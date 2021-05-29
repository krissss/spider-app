<?php

namespace spider\service\tianYanCha;

use QL\Ext\CurlMulti;
use QL\QueryList;
use yii\helpers\StringHelper;

class Spider
{
    private $ql;

    public function __construct()
    {
        $this->ql = QueryList::getInstance()->use([
            CurlMulti::class,
        ]);
    }

    public function findPage($companyName)
    {
        $first = $this->ql->get("https://www.tianyancha.com/search?key={$companyName}")
            ->find('.result-list .search-result-single .name')->get(0);
        if ($first->textContent !== $companyName) {
            return null;
        }
        return $first->getAttribute('href');
    }

    public function fetchDetail($url)
    {
        $ql = $this->ql->get($url);
        $tds = $ql->find('#_container_baseInfo table')->find('td')->texts();
        $dataKey = [];
        $dataValue = [];
        foreach ($tds as $index => $td) {
            if ($index % 2 === 0) {
                $dataKey[] = $td;
            } else {
                $dataValue[] = $td;
            }
        }
        $data = array_combine($dataKey, $dataValue);
        //dd($data);
        // 清理数据格式
        $result = [
            '法定代表人' => function ($value) {
                if (!$value) {
                    return '';
                }
                $value = mb_substr($value, 1);
                $arr = explode('任职', $value);
                if (count($arr) >= 2) {
                    $value = $arr[0];
                }
                return $value;
            },
            '统一社会信用代码' => '',
            '纳税人识别号' => '',
            '组织机构代码' => '',
            '注册地址' => '',
        ];
        foreach ($data as $key => $value) {
            foreach ($result as $start => $call) {
                if (StringHelper::startsWith($key, $start)) {
                    $result[$start] = is_callable($call) ? call_user_func($call, $value) : $value;
                }
            }
        }
        //dd($result);
        return $result;
    }
}
