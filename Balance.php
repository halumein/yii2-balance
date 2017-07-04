<?php
namespace halumein\balance;

use Yii;
use yii\data\Sort;
use yii\base\Component;
use halumein\balance\models\Score;
use halumein\balance\models\IncomePack;
use halumein\balance\models\Transaction;
use halumein\balance\models\SearchTransaction;

class Balance extends Component{

	public $currencyName = 'баллов';
	public $adminRoles = ['admin'];
	public $otherRoles = ['user'];

	public function init()
	{
			parent::init();
	}

	public function getUserScore($userId = null)
	{
		if ($userId){
			return $userScore = Score::find()->where(['user_id' => $userId])->one();
		}
		return $userScore = Score::find()->where(['user_id' => Yii::$app->user->id])->one();
	}

	public function addFunds($balanceId, $amount, $refillType, $comment = null)
	{
		$additionalData = [
			'refillType' => $refillType,
			'comment' => $comment,
		];
		$tranasaction = $this->addTransaction($balanceId, 'in', $amount, $additionalData);
		if ($tranasaction) {
			// $balanceId, $tranasctionId, $amount, $ident
			// TODO 
			$this->addIncomePack($balanceId, $tranasaction->id, $amount, $ident);
		}
	}

	public function removeFunds($balanceId, $amount, $refillType, $comment = null)
	{
		// находим все пачки прихода средств что бы начать списывать с наиболее ранней
		$incomePacks = IncomePack::find()
						->where(['balance_id' => $balanceId])
						->andWhere(['>', 'balance', 0])
						->orderBy(['id' => SORT_ASC])
						->all();

		$balance = $amount;

		// начинаем списывать
		foreach ($incomePacks as $key => $pack) {

			if ($pack->balance - $amount < 0) {

			}

		}

		$additionalData = [
			'refillType' => $refillType,
			'comment' => $comment,
			'packId' => $comment,
		];
		return $this->addTransaction($balanceId, 'out', $amount, $additionalData);
	}

	public function revertTransaction($transactionId, $comment = null)
	{
		$transaction = Transaction::findOne($transactionId);

		if ($transaction) {
			// опеределяем обратную операцию
			$type = $transaction->type == 'in' ? 'in' : 'out';
			$this->addTransaction($transaction->balanceId, $type, $transaction->amount, $transaction->pack_id, 'Отмена транзакции '.$transaction->id, $comment);
		} else {
			return [
				'status' => 'error',
				'message' => 'Транзакция не найдена',
			];
		}
	}

	/**
	* $balanceId - id кошелька
	* $type - in/out тип операции: приход/расход
	* $amount - количество
	* $additionalData = [
	*	'refillType' => string - причина - любое описание за что произведена операция
	*	'comment' => string - комментарий
	*	'packId' => int - id пачки прихода
	* ]
	*/
	protected function addTransaction($balanceId, $type, $amount, $additionalData = [])
	{

		$model = new Transaction;
		$model->balance_id = $balanceId;
		$model->type = $type;
		$model->amount = $amount;
		if (isset($additionalData['refillType'])) {
			$model->refill_type = $additionalData['refillType'];
		}

		if (isset($additionalData['comment'])) {
			$model->comment = $additionalData['comment'];
		}

		if (isset($additionalData['packId'])) {
			$model->pack_id = $additionalData['packId'];
		}

		$model->date = date('Y-m-d H:i:s');
		$model->user_id = \Yii::$app->user->id; //Score::find()->where(['id' => $balanceId])->one()->user_id;
		$lastTransaction = Transaction::find()->where(['balance_id' => $balanceId])->orderBy(['id' => SORT_DESC])->one();

		$score = Score::findOne($balanceId);
		if(!$lastTransaction){
			$model->balance = $model->amount;
			$score->balance = $model->amount;
		} elseif ($type == 'in') {	//приход средств
			$model->balance = $lastTransaction->balance+$amount;
			$score->balance = $score->balance + $amount;
		} else {				//расход средств
			$model->balance = $lastTransaction->balance-$amount;
			$score->balance = $score->balance - $amount;
		}
		$score->update();

		if($model->save()){
			return $model->id;
		} else {
			return $model->getErrors();
		}
	}



	/**
	*
	*/
	protected function addIncomePack($balanceId, $tranasctionId, $amount, $ident)
	{
		$incomePack = new IncomePack();

		$incomePack->balance_id = $balanceId;
		$incomePack->amount = $amount;
		$incomePack->current_balance = $amount;
		$incomePack->ident = $ident;
		$incomePack->source_transaction_id = $tranasctionId;
		$incomePack->date_created = date("Y-m-d");
		$incomePack->date_updated = date("Y-m-d");

		return $incomePack->save();

	}

}
