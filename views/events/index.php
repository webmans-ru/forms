<?php

use yii\helpers\Html;
//use yii\grid\GridView;
use yii\bootstrap\Tabs;

/* @var $this yii\web\View */
/* @var $searchModel app\modules\b24\models\B24portalSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */
//$this->title = 'Распределение';
//$this->params['breadcrumbs'][] = $this->title;
?>
<div class="b24portal-index">

    <h1><?= Html::encode($this->title) ?></h1>
</div>
<div class="b24-default-index">
    <h1><?= $this->context->action->uniqueId ?></h1>
    <p>
        This is the view content for action "<?= $this->context->action->id ?>".
        The action belongs to the controller "<?= get_class($this->context) ?>"
        in the "<?= $this->context->module->id ?>" module.
    </p>
    <p>
        You may customize this page by editing the following file:<br>
        <code><?= __FILE__ ?></code>
    </p>

    <p>
        <?php //echo '<pre>' .print_r($arCurrentB24User, true). '<pre>';  ?>
    </p>
</div>
