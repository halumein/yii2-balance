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

	/**
	* Пополнение кошелька
	* $balanceId - ид кошелька
	* $amount - сумма пополнения
	* $refillType - тип пополнения - любая строка, например "начисление через платёжную систему"
	* $ident - идентификатор пачки - любая строка, например номер платёжного поручения или название акции
	* $comment - комментарий
	*/

	public function addFunds($balanceId, $amount, $refillType, $ident, $comment = null)
	{
		$additionalData = [
			'refillType' => $refillType,
			'comment' => $comment,
		];
		// добавляем транзакцию
		$tranasaction = $this->addTransaction($balanceId, 'in', $amount, $additionalData);
		if ($tranasaction) {
			// добавляем пачку
			$this->addIncomePack($balanceId, $tranasaction->id, $amount, $ident);
		}
	}

	/**
	*	Списание средств с кошелька
	* 	$balanceId - ид кошелька
	* 	$amount - сумма пополнения
	* 	$refillType - тип пополнения - любая строка, например "начисление через платёжную систему"
	* 	$ident - идентификатор пачки - любая строка, например номер платёжного
	*   поручения или название акции. если не передан - списание будет с наиболее раннего
	* 	$comment - комментарий
	*/
	public function removeFunds($balanceId, $amount, $refillType, $ident = null, $comment = null)
	{
		while ($amount > 0) {

			// если нужно списать с конкретной пачки
			if ($ident) {
				$pack = $this->getPackByIdent($ident);
			} else {
				// находим наиболее раннюю пачку, списывать будем с нее
				$pack = $this->getEarliestPack($balanceId);
			}

			if (!$pack) {
				return [
					'status' => 'error',
					'error' => "нет средств для списания остатка $amount"
				];
			}

			$additionalData = [
				'refillType' => $refillType,
				'comment' => $comment,
				'packId' => $pack->id,
			];

			if ($pack->current_balance >= $amount ) {
				// если в пачке сумма больше или равна сумме списания - списываем сумму списания
				$pack->current_balance = $pack->current_balance - $amount;
				$transactionAmount = $amount;
				$amount = 0;
			} else {
				// если в пачке сумма меньше суммы списания - списываем сумму в
				// пачке (до нуля) и остаток списываем со следующей пачки
				$transactionAmount = $pack->current_balance;
				$amount = $amount - $pack->current_balance;
				$pack->current_balance = 0;
			}

			$this->addTransaction($balanceId, 'out', $transactionAmount, $additionalData);
			$pack->update();
		}

		return true;
	}

	public function removeAllFundsFromPack($packIdent, $reason)
	{
		$pack = IncomePack::find()
				->where(['ident' => $packIdent])
				->one();

		if ($pack) {
			if ($pack->current_balance == 0) {
				return true;
			}
			$additionalData = [
				'refillType' => $reason,
				'packId' => $pack->id,
			];

			$amount = $pack->current_balance;

			$this->addTransaction($pack->balance_id, 'out', $amount, $additionalData);
			$pack->current_balance = 0;
			$pack->update();

			return true;

		}

		return false;

	}

	public function revertTransaction($transactionId, $comment = null)
	{
		$transaction = Transaction::findOne($transactionId);

		if ($transaction) {
			// опеределяем обратную операцию

			// если был приход - то начинаем списание с пачки прихода
			if ($transaction->type == 'in') {
				$pack = IncomePack::find()
						->where(['source_transaction_id' => $transaction->id])
						->one();
				$this->removeFunds($transaction->balance_id, $transaction->amount, 'Отмена транзакции '. $transaction->id, $pack->ident, $comment);
			} else {

				// если списание - то находим пачку с которой списывали
				$pack = IncomePack::findOne($transaction->pack_id);
				// и возвращаем в нее списанную сумму
				$pack->current_balance = $pack->current_balance + $transaction->amount;
				$pack->update();

				// и проводим транзакцию

				$additionalData = [
					'refillType' => 'отмена транзакции ' . $transaction->id,
					'comment' => $comment,
					'packId' => $transaction->pack_id,
				];

				$this->addTransaction($transaction->balance_id, 'in', $transaction->amount, $additionalData);
			}

			$transaction->canceled = date('Y-m-d H:i:s');
			$transaction->update();

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
			return $model;
		} else {
			// return $model->getErrors();
			return false;
		}
	}

	protected function getEarliestPack($balanceId)
	{
		return IncomePack::find()
						->where(['balance_id' => $balanceId])
						->andWhere(['>', 'current_balance', 0])
						->orderBy(['id' => SORT_ASC])
						->one();
	}

	protected function getPackByIdent($ident)
	{
		return IncomePack::find()
						->where(['ident' => $ident])
						->one();
	}

	/**
	*	Добавляет входящую пачку
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
