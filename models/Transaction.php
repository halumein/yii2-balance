<?php

namespace halumein\balance\models;

use Yii;
use halumein\balance\models\IncomePack;

/**
 * This is the model class for table "balance_transaction".
 *
 * @property integer $id
 * @property integer $balance_id
 * @property string $date
 * @property string $type
 * @property string $amount
 * @property string $balance
 * @property integer $user_id
 * @property string $refill_type
 * @property string $canceled
 */
class Transaction extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'balance_transaction';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['balance_id', 'type', 'amount', 'refill_type'], 'required'],
            [['balance_id', 'user_id'], 'integer'],
            [['date', 'canceled'], 'safe'],
            [['type'], 'string'],
            [['amount', 'balance'], 'number'],
            [['refill_type', 'comment'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'balance_id' => 'ID кошелька',
            'date' => 'Дата создания транзакции',
            'type' => 'Тип транзакции',
            'amount' => 'Средств',
            'balance' => 'Остаток',
            'user_id' => 'ID пользователя',
            'refill_type' => 'Тип операции',
            'canceled' => 'Дата отмены транзакции ',
			'comment' => 'Комментарий'
        ];
    }

    public function getPack()
    {
        return $this->hasOne(IncomePack::className(), ['id' => 'pack_id'])->one();
    }
}
