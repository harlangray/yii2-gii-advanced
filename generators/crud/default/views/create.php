<?php

use yii\helpers\Inflector;
use yii\helpers\StringHelper;

/* @var $this yii\web\View */
/* @var $generator yii\gii\generators\crud\Generator */

echo "<?php\n";
?>

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model <?= ltrim($generator->modelClass, '\\') ?> */

$this->title = <?= $generator->generateString('Create {modelClass}', ['modelClass' => Inflector::camel2words(StringHelper::basename($generator->modelClass))]) ?>;
$this->params['breadcrumbs'][] = ['label' => <?= $generator->generateString(Inflector::pluralize(Inflector::camel2words(StringHelper::basename($generator->modelClass)))) ?>, 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="<?= Inflector::camel2id(StringHelper::basename($generator->modelClass)) ?>-create">

    <h1><?= "<?= " ?>Html::encode($this->title) ?></h1>

    <?= "<?= " ?>$this->render('_form', [
        'model' => $model,
        <?php
            $schema = $generator->getTableSchema();
            $masterTable = $schema->fullName; 
          
            $relatedDetailTables = $generator->getRelatedTableAndFields($masterTable);
            foreach ($relatedDetailTables as $relatedTable){
                $tableName = $relatedTable['tabelName'];
                $fieldName = $relatedTable['relatedField'];
                echo "'{$tableName}Mods' => \${$tableName}Mods,\n";
            }
        ?>            
    ]) ?>

</div>
