<?php
namespace halumein\balance\widgets;

use Yii;
use yii\base\Widget;
use yii\helpers\Html;
use yii\helpers\Url;
use halumein\balance\models\Score;

class ScoreButtonWidget extends Widget{
	 
	public function init()
	{
		parent::init();
		return true;
	}
	
	public function run()
	{
		echo Html::a('Кошельки', Url::to(['/balance/score']));
	}
	
}