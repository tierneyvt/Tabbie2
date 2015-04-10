<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model common\models\feedback */

$this->title = Yii::t('app', 'Create Feedback');
$tournament = $this->context->_getContext();
$this->params['breadcrumbs'][] = ['label' => $tournament->fullname, 'url' => ['tournament/view', "id" => $tournament->id]];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="feedback-create">

	<h1><?= Html::encode($this->title) ?></h1>

	<?= $this->render('_form', [
		'models' => $models,
	]) ?>

</div>
