<?php

use yii\helpers\Inflector;
use yii\helpers\StringHelper;

/* @var $this yii\web\View */
/* @var $generator yii\gii\generators\crud\Generator */

$urlParams = $generator->generateUrlParams();
$nameAttribute = $generator->getNameAttribute();

echo "<?php\n";
?>

use yii\helpers\Html;
use <?= $generator->indexWidgetType === 'grid' ? "yii\\grid\\GridView" : "yii\\widgets\\ListView" ?>;

/* @var $this yii\web\View */
<?= !empty($generator->searchModelClass) ? "/* @var \$searchModel " . ltrim($generator->searchModelClass, '\\') . " */\n" : '' ?>
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = <?= $generator->generateString(Inflector::pluralize(Inflector::camel2words(StringHelper::basename($generator->modelClass)))) ?>;
$this->params['breadcrumbs'][] = $this->title;
?>

<?php
echo "<?php\n";
?>
foreach (Yii::$app->session->getAllFlashes() as $key => $message) {
    echo '<div class="alert alert-' . $key . '">' . $message . '</div>';
}
<?php
echo "?>\n";
?>

<div class="<?= Inflector::camel2id(StringHelper::basename($generator->modelClass)) ?>-index">

    <h1><?= "<?= " ?>Html::encode($this->title) ?></h1>
<?php if(!empty($generator->searchModelClass)): ?>
<?= "    <?php " . ($generator->indexWidgetType === 'grid' ? "// " : "") ?>echo $this->render('_search', ['model' => $searchModel]); ?>
<?php endif; ?>

    <p>
        <?= "<?= " ?>Html::a(<?= $generator->generateString('Create {modelClass}', ['modelClass' => Inflector::camel2words(StringHelper::basename($generator->modelClass))]) ?>, ['create'], ['class' => 'btn btn-success']) ?>
    </p>

<?php if ($generator->indexWidgetType === 'grid'): ?>
    <?= "<?= " ?>GridView::widget([
        'dataProvider' => $dataProvider,
        <?= !empty($generator->searchModelClass) ? "'filterModel' => \$searchModel,\n        'columns' => [\n" : "'columns' => [\n"; ?>
            ['class' => 'yii\grid\SerialColumn'],

<?php
$count = 0;
if (($tableSchema = $generator->getTableSchema()) === false) {
    foreach ($generator->getColumnNames() as $name) {
        $relatedFieldDtls = $generator->getRelatedFieldDtls($name);
        $field = '';  
        if($relatedFieldDtls !== null){
            $relationName = $relatedFieldDtls['relationName'];
            $foreignFieldName = $relatedFieldDtls['foreignFieldName'];
            $relationName = lcfirst($relationName);
            
            $field .= "            [\n";
            $field .= "                'attribute' => '$column->name',\n";
            $field .= "                'value'=>'$relationName.$foreignFieldName',\n";
            $field .= "            ],";            
        }
        else{
            $field = "            '" . $name . "',";
        }
        if (++$count < 6) {
            echo "{$field}\n";
        } else {
            echo "/*{$field}*/\n";
        }
    }
} else {
    foreach ($tableSchema->columns as $column) {
        $format = $generator->generateColumnFormat($column);
        
        $relatedFieldDtls = $generator->getRelatedFieldDtls($column->name);
        $field = '';  
        if($relatedFieldDtls !== null){
            $relationName = $relatedFieldDtls['relationName'];
            $foreignFieldName = $relatedFieldDtls['foreignFieldName'];
            $relationName = lcfirst($relationName);
            
            $field .= "            [\n";
            $field .= "                'attribute' => '$column->name',\n";
            $field .= "                'value'=>'$relationName.$foreignFieldName',\n";
            $field .= "            ],";            
        }
        else{
            $field = "            '" . $column->name . ($format === 'text' ? "" : ":" . $format) . "',";
        }

        
        if (++$count < 6) {
            echo "{$field}\n";
        } else {
            echo "/*{$field}*/\n";
        }
    }
}
?>

            ['class' => 'yii\grid\ActionColumn'],
        ],
    ]); ?>
<?php else: ?>
    <?= "<?= " ?>ListView::widget([
        'dataProvider' => $dataProvider,
        'itemOptions' => ['class' => 'item'],
        'itemView' => function ($model, $key, $index, $widget) {
            return Html::a(Html::encode($model-><?= $nameAttribute ?>), ['view', <?= $urlParams ?>]);
        },
    ]) ?>
<?php endif; ?>

</div>
