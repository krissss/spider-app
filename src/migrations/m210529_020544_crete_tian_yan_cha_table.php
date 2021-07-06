<?php

use spider\migrations\Migration;

/**
 * Class m210529_020544_crete_tian_yan_cha_table
 */
class m210529_020544_crete_tian_yan_cha_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('tian_yan_cha', [
            'id' => $this->primaryKey(),
            'batch_num' => $this->integer()->notNull()->comment('导入批次'),
            'company_name' => $this->string()->notNull()->comment('公司名称'),
            'page_url' => $this->string()->comment('企查查地址'),
            'num_na_shui_ren' => $this->string()->comment('纳税人识别号'),
            'leader_person' => $this->string()->comment('法人'),
            'reg_money' => $this->string()->comment('注册资金'),
            'full_address' => $this->string()->comment('地址'),
            'province' => $this->string()->comment('省'),
            'city' => $this->string()->comment('市'),
            'region' => $this->string()->comment('区'),
            'street' => $this->string()->comment('街道'),
        ], $this->setTableComment('企查查'));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('tian_yan_cha');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m210529_020544_crete_tian_yan_cha_table cannot be reverted.\n";

        return false;
    }
    */
}
