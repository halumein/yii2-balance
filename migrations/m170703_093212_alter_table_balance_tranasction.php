<?php

use yii\db\Schema;
use yii\db\Migration;

class m170703_093212_alter_table_balance_tranasction extends Migration
{

    public function safeUp()
    {
        $this->addColumn('balance_transaction', 'pack_id', $this->integer()->null());

    }

    public function safeDown()
    {
        $this->dropColumn('balance_transaction', 'pack_id');
        return true;

    }
}
