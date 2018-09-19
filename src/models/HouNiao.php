<?php

namespace spider\models;

use spider\common\ConfigString;
use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property string $sku
 * @property string $title
 * @property string $title_label
 * @property string $goods_tips
 * @property string $img
 * @property string $price
 * @property string $goods_attributes
 * @property int $goods_id
 * @property string $detail
 * @property string $specs
 * @property string $specs_prices
 * @property int $is_download
 */
class HouNiao extends ActiveRecord
{
    public static function tableName()
    {
        return 'houniao';
    }

    public static function getDb()
    {
        return ConfigString::getDb();
    }
}