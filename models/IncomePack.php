<?php

namespace halumein\balance\models;

use Yii;
use halumein\balance\models\Transaction;

/**
 * This is the model class for table "balance_income_pack".
 *
 * @property int $id
 * @property int $balance_id
 * @property string $amount
 * @property string $current_balance
 * @property string $ident
 * @property int $source_transaction_id
 * @property string $date_created
 * @property string $date_updated
 */
class IncomePack extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'balance_income_pack';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['balance_id', 'amount', 'current_balance', 'ident', 'source_transaction_id'], 'required'],
            [['balance_id', 'source_transaction_id'], 'integer'],
            [['amount', 'current_balance'], 'number'],
            [['date_created', 'date_updated'], 'safe'],
            [['ident'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'balance_id' => 'id кошелька',
            'amount' => 'Изначальное количество',
            'current_balance' => 'Текущий остаток',
            'ident' => 'Идентификатор',
            'source_transaction_id' => 'id транзакции прихода',
            'date_created' => 'Дата создания',
            'date_updated' => 'Дата изменения',
        ];
    }

    public function getIncomeTransaction()
    {
        return $this->hasOne(Transaction::className(), ['id' => 'source_transaction_id'])->one();
    }
}
