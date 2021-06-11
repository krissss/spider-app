<?php

namespace spider\models;

use spider\common\ConfigString;
use spider\common\SmartAddress;
use yii\db\ActiveRecord;
use Throwable;

/**
 * @property int $id
 * @property int $batch_num
 * @property string $company_name
 * @property string $page_url
 * @property string $leader_person
 * @property string $num_na_shui_ren
 * @property string $full_address
 * @property string $province
 * @property string $city
 * @property string $region
 * @property string $street
 */
class TianYanCha extends ActiveRecord
{
    public static function tableName()
    {
        return 'tian_yan_cha';
    }

    public static function getDb()
    {
        return ConfigString::getDb();
    }

    public function parseAddress()
    {
        if (!$this->full_address) {
            return;
        }
        try {
            $address = strtr($this->full_address, [
                '苏州' => '苏州市',
            ]);
            $result = SmartAddress::smart($address);
            $this->province = rtrim($result['province'], '省');
            $this->city = rtrim($result['city'], '市');
            $this->region = $result['region'];
            $this->street = $result['street'];
        } catch (Throwable $e) {
            $this->street = $this->full_address;
        }
    }
}