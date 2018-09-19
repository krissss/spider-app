<?php

use spider\migrations\Migration;

/**
 * Handles the creation of table `houniao`.
 */
class m180918_012944_create_houniao_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('houniao', [
            'id' => $this->primaryKey(),
            'sku' => $this->string()->unique()->comment('SKU'),
            'title' => $this->string()->comment('标题'),
            'title_label' => $this->string()->comment('标题'),
            'goods_tips' => $this->string()->comment('标题'),
            'img' => $this->string()->comment('缩略图'),
            'price' => $this->string()->comment('价格'),
            'specs' => $this->text()->comment('规格'),
            'specs_price' => $this->text()->comment('规格价格'),
            'goods_attributes' => $this->text()->comment('属性'),
            'goods_id' => $this->integer()->unique()->comment('商品ID'),
            'detail' => $this->text()->comment('详情'),
            'is_download' => $this->boolean()->notNull()->defaultValue(false)->comment('是否已下载'),
        ], $this->setTableComment('候鸟供应商'));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('houniao');
    }
}
