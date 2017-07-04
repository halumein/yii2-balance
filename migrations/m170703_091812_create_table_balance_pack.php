<?php

use yii\db\Schema;
use yii\db\Migration;

class m170703_091812_create_table_balance_pack extends Migration
{

    public function safeUp()
    {
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }
        else {
            $tableOptions = null;
        }

        $this->createTable('{{%balance_income_pack}}',[
            'id' => $this->primaryKey(11),
            'balance_id' => $this->integer()->notNull(),
            'amount' => Schema::TYPE_DECIMAL . '(15.2) NOT NULL',
            'current_balance' => Schema::TYPE_DECIMAL . '(15.2) NOT NULL',
            'ident' => $this->string(255)->notNull(),
            'source_transaction_id' => $this->integer()->notNull(),
            'date_created' => $this->datetime()->null()->defaultValue(null),
            'date_updated' => $this->datetime()->null()->defaultValue(null),
        ], $tableOptions);

    }

    public function safeDown()
    {
        $this->dropTable('{{%balance_pack}}');
    }
}
