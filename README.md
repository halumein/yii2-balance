Yii2-balance
==========
Это модуль для реализации кошелька пользователя. Кошелек создается автоматически для нового пользователя, так же можно создать кошелек для уже существующего пользователя, или для всех пользователей, у которых еще нет кошелька, по нажатию на соответствующую кнопку на странице кошельков пользователей.
Установка
---------------------------------
Выполнить команду

```
php composer require halumein/yii2-balance "*"
```

Или добавить в composer.json

```
"halumein/yii2-balance": "*",
```

И выполнить

```
php composer update
```

Далее, мигрируем базу:

```
php yii migrate --migrationPath=vendor/halumein/yii2-balance/migrations
```

Подключение и настройка
---------------------------------
Для пользования необходимо подключить модуль в конфиге:

```'php'
	'modules' => [
		'balance' => [
				'class' => 'halumein\balance\Module',
				'adminRoles' => ['superadmin', 'administrator'],
				'otherRoles' => ['manager', 'user'],
				'currencyName' => 'баллов'
				],
	...
	]
```
Подключить компонент:
```'php'
	'components' => [
	...
		'balance' => [
			'class' => 'halumein\balance\Balance'
		],
	...
	]
```

Доступные следующие методы (подробнее см. компонент balance):
```
	// начислить 500 баллов на кошелёк с id 12
	\Yii::$app->balance->addFounds(12, 500, 'Начисление бонусных баллов по акции', 123456);
	\Yii::$app->balance->removeFounds(12, 250, 'Оплата заказа 1234125');

```

Для того, чтобы кошелек автоматически создавался для пользователя нужно модифицировать стандартную модель 'User'(commmon\models\User) следующим образом:

```'php'
...
use halumein\balance\models\Score;
...
	public function afterSave($p1, $p2)
	{
		$findUser = Score::find()->where(['user_id' => $this->getId()])->one();
		if (!$findUser){
			$userBalance = new Score;
			$userBalance->user_id = $this->getId();
			$userBalance->balance = 0;

			if($userBalance->validate()){
				return $userBalance->save();
			} else die('Uh-oh, somethings went wrong!');
		}
	}
```
В этой же модели (ниже) необходимо добавить метод getScore, который отвечает за получение текущего остатка пользователя:

```'php'
public function getScore($userId = null)
	{
			if ($userId){
				return $userScore = Score::find()->where(['user_id' => $userId])->one()->balance;
			}
			return $userScore = Score::find()->where(['user_id' => Yii::$app->user->id])->one()->balance;
	}
```
Если модель подключаемого User не соответствует 'common\models\User' то ее необходимо задать в Модуле(Module.php) изменив переменную $userModule;
Для облегчения переходов и информатирования пользователя о количестве баланса предусмотренны следующие виджеты:
```'php'
<?php
use halumein\balance\widgets\BalanceWidget;			 //Виджет для вывода пользователю его баланса со ссылкой на историю его транзакций
use halumein\balance\widgets\ScoreButtonWidget;		 //Виджет для перехода на страницу кошельков
use halumein\balance\widgets\TransactionButtonWidget; //Виджет для перехода на страницу транзакций
...
echo BalanceWidget::widget();
echo ScoreButtonWidget::widget();
echo TransactionButtonWidget::widget();
?>
```
