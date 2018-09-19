<?php

namespace spider\service\houniao;

use QL\Ext\AbsoluteUrl;
use QL\Ext\CurlMulti;
use QL\QueryList;
use spider\common\ConfigString;
use spider\common\Logger;
use spider\models\HouNiao;
use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\FileHelper;
use yii\httpclient\Client;
use yii\httpclient\CurlTransport;

class Spider
{
    public function getSku($pageArr)
    {
        $pageRecordPath = Yii::getAlias('@runtime/houniao-page');
        FileHelper::createDirectory($pageRecordPath);
        $newPageArr = [];
        foreach ($pageArr as $page) {
            if (file_exists($pageRecordPath . '/' . $page)) {
                continue;
            }
            $newPageArr[] = $page;
        }
        $pageArr = $newPageArr;
        if (!$pageArr) {
            return;
        }
        $rules = [
            'sku' => ['.goods-info>input[name="sku"]', 'value'],
        ];
        $range = '.result li.goods';
        $ql = QueryList::getInstance()->use([
            CurlMulti::class,
        ]);
        $urls = array_map(function ($page) {
            return $this->getPageUrl($page);
        }, $pageArr);
        $ql->rules($rules)->range($range)
            ->curlMulti($urls)
            ->success(function (QueryList $ql, CurlMulti $curl, $r) use ($pageRecordPath) {
                $url = $r['info']['url'];
                Logger::houniao($url);
                // 写入数据
                $rows = $ql->query()->getData()->all();
                // 需要跳过已经保存的sku
                $existsSkuArr = HouNiao::find()->select(['sku'])->where(['sku' => ArrayHelper::getColumn($rows, 'sku')])->column();
                $newRows = [];
                foreach ($rows as $row) {
                    if (in_array($row['sku'], $existsSkuArr)) {
                        continue;
                    }
                    $newRows[] = $row;
                }
                $count = ConfigString::getDb()->createCommand()->batchInsert(HouNiao::tableName(), ['sku'], $newRows)->execute();
                Logger::houniao('success-count: ' . $count);
                file_put_contents($pageRecordPath . '/' . Helper::getQsParam($url, 'index'), 'ok');
            })
            ->start(['maxThread' => 3, 'maxTry' => 3, 'opt' => $this->curlOpt()]);
    }

    public function getDetail($skuArr)
    {
        $absoluteUrl = 'http://houniao.hk';
        $rules = [
            'sku' => ['#goodsDetail > div.goods-detail-center.fl > div.summary-bg > div.summary.summary-wrap > div.li.li-first > div.dd', 'text'],
            'img' => ['.img-con>img', 'src'],
            'title_label' => ['.sku-name>.label', 'text', '-i'],
            'title' => ['.sku-name', 'text', '-div'],
            'goods_tips' => ['.goods-tips', 'title'],
            'price' => ['#actualPrice', 'text'],
            'specs' => ['#specs > div.dd', 'html'],
            'goods_attributes' => ['.goods-attributes>ul', 'text'],
            'goods_id' => ['input[name="itemId"]', 'value'],
            //'detail' => ['.goods-details-con', 'text'], // 该数据为异步 jsonp 请求
        ];
        $range = '.main-content';
        $ql = QueryList::getInstance()->use([
            AbsoluteUrl::class,
            CurlMulti::class,
        ]);
        $urls = array_map(function ($sku) {
            return $this->getDetailUrl($sku);
        }, $skuArr);
        $ql->rules($rules)->range($range)
            ->curlMulti($urls)
            ->success(function (QueryList $ql, CurlMulti $curl, $r) use ($absoluteUrl) {
                $url = $r['info']['url'];
                Logger::houniao($url);
                // 格式化数据
                $data = $ql->absoluteUrl($absoluteUrl)->query()->getData(function ($item) use ($ql, $absoluteUrl) {
                    $specsData = QueryList::getInstance()->html($item['specs'])->find('.item>a')->map(function ($item) {
                        return ['num' => $item->attr('data-specnum'), 'text' => $item->text()];
                    });
                    $item['specs'] = json_encode($specsData->all(), JSON_UNESCAPED_UNICODE);
                    $item['goods_attributes'] = strtr($item['goods_attributes'], [
                        ' ' => '',
                        "\n" => '|'
                    ]);
                    return $item;
                });
                $rows = $data->all();
                // 更新数据
                if (isset($rows[0])) {
                    $sku = $rows[0]['sku'];
                    unset($rows[0]['sku']);
                    $count = HouNiao::updateAll($rows[0], ['sku' => $sku]);
                    Logger::houniao('update-count:' . $count);
                }
            })
            ->start(['maxThread' => 10, 'maxTry' => 3, 'opt' => $this->curlOpt()]);
    }

    public function getDetailImg($goodsIdArr)
    {
        $absoluteUrl = 'http://houniao.hk';
        $client = new Client([
            'transport' => CurlTransport::class
        ]);
        $request = $client->createRequest()
            ->setMethod('POST')
            ->setUrl('http://houniao.hk/home/product/get?callback=getGoodsDescCallback')
            ->setHeaders(Helper::getRequestHeader());
        $request = Helper::httpClientWithCookie($request);
        $requests = [];
        foreach ($goodsIdArr as $goodsId) {
            $requests[$goodsId] = (clone $request)->setData(['goodsId' => $goodsId]);
        }
        $responses = $client->batchSend($requests);
        foreach ($responses as $goodsId => $response) {
            $data = $this->getJsonpCallback($response->content, 'getGoodsDescCallback');
            if ($data) {
                $images = QueryList::getInstance()->html($data);
                $imgArr = $images->find('img')->map(function ($img) use ($absoluteUrl) {
                    return $absoluteUrl . $img->attr('data-original');
                })->all();
                $count = HouNiao::updateAll(['detail' => implode('|', $imgArr)], ['goods_id' => $goodsId]);
                Logger::houniao('update-count:' . $count);
            }
        }
    }

    public function getSpecsPrice(HouNiao $model)
    {
        $client = new Client([
            'transport' => CurlTransport::class
        ]);
        $request = $client->createRequest()
            ->setMethod('POST')
            ->setUrl('http://houniao.hk/home/product/get?callback=screenSelectCallback')
            ->setHeaders(Helper::getRequestHeader());
        $request = Helper::httpClientWithCookie($request);
        $requests = [];
        $specs = json_decode($model->specs, true);
        if (!$specs) {
            Logger::houniao('json 解析失败' . $model->specs);
            return;
        }
        $specs = ArrayHelper::index($specs, 'num');
        foreach ($specs as $spec) {
            $requests[$spec['num']] = (clone $request)->setData([
                'itemId' => $model->goods_id, 'itemSku' => $model->sku,
                'buyNum' => 1, 'specNum' => $spec['num'],
            ]);
        }

        $responses = $client->batchSend($requests);

        foreach ($responses as $num => $response) {
            $data = $this->getJsonpCallback($response->content, 'screenSelectCallback');
            if ($data) {
                $specs[$num]['price'] = $data['actualPrice'];
                $specs[$num]['averagePrice'] = $data['averagePrice'];
            }
        }
        $model->specs_prices = json_encode(array_values($specs), JSON_UNESCAPED_UNICODE);
        $model->save(false);
        Logger::houniao(['success model:' => $model->id]);
    }

    public function download(HouNiao $model)
    {
        $client = new Client([
            'transport' => CurlTransport::class
        ]);
        $request = $client->createRequest()->setMethod('GET');
        $requests = [];
        $imgPath = Helper::getStoragePath('houniao/' . $model->sku);
        if (is_dir($imgPath) && file_exists($imgPath . '/pic.jpg')) {
            return;
        }
        FileHelper::createDirectory($imgPath);

        $requests[$imgPath . '/pic.jpg'] = (clone $request)->setUrl($model->img);
        $detailImgArr = array_filter(explode('|', $model->detail));
        foreach ($detailImgArr as $index => $detailImg) {
            $requests["{$imgPath}/detail_{$index}.jpg"] = (clone $request)->setUrl($detailImg);
        }

        $responses = $client->batchSend($requests);

        $successCount = 0;
        foreach ($responses as $imgFilename => $response) {
            $isOk = file_put_contents($imgFilename, $response->content);
            $isOk && $successCount++;
        }
        Logger::houniao('download batch over:' . $model->id);
        Logger::houniao(['success count' => $successCount]);
    }

    protected function getPageUrl($page = 1)
    {
        return "http://houniao.hk/home/product/search?brand=&origin=&categorytops=&categorytwos=&categorys=&category=&strict=&index={$page}&type=&isDesc=&needStock=&tradeType=&kw=";
    }

    protected function getDetailUrl($sku)
    {
        return 'http://houniao.hk/home/product/detail?itemSku=' . $sku;
    }

    protected function curlOpt()
    {
        return [
            CURLOPT_COOKIE => Helper::getRequestHeader()['Cookie'],
            CURLINFO_HEADER_OUT => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_AUTOREFERER => true,
            CURLOPT_USERAGENT => Helper::getRequestHeader()['User-Agent'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5
        ];
    }

    protected function getJsonpCallback($content, $jsonpCallbackName)
    {
        $json = ltrim($content, $jsonpCallbackName . '(');
        $json = rtrim($json, ')');
        //Logger::houniao($json);
        $json = json_decode($json, true);
        if ($json['retCode'] == 200) {
            return $json['retEntity'];
        }
        return false;
    }
}