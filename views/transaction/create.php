<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model common\modules\halumein\balance\models\Transaction */

$this->title = 'Создать транзакцию';
$this->params['breadcrumbs'][] = ['label' => 'Транзакции', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="transaction-create">

    <?php echo $this->render('_form', [
        'model' => $model,
		'scores' => $scores
    ]) ?>

</div>
