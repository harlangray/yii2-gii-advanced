<?php

use yii\helpers\Inflector;
use yii\helpers\StringHelper;


/* @var $this yii\web\View */
/* @var $generator yii\gii\generators\crud\Generator */

/* @var $model \yii\db\ActiveRecord */
$model = new $generator->modelClass();
$safeAttributes = $model->safeAttributes();
if (empty($safeAttributes)) {
    $safeAttributes = $model->attributes();
}

echo "<?php\n";
?>

use yii\helpers\Html;
use yii\widgets\ActiveForm;
<?php
if($generator->hasDateField()) 
    echo "use kartik\widgets\DatePicker;\n";

if($generator->hasDateTimeField()) 
    echo "use kartik\widgets\DateTimePicker;\n";

if($generator->hasForeignTable()){
    echo "use yii\helpers\ArrayHelper;\n";
    
    $tableSchema = $generator->getTableSchema();
    foreach ($tableSchema->foreignKeys as $foreignKeys) {       
        $tableName = $foreignKeys['0'];
        $modGen = new yii\gii\generators\model\Generator;
        $className = $modGen->generateClassName($tableName);
        echo "use app\models\\{$className};\n";
          
    }

}
?>
/* @var $this yii\web\View */
/* @var $model <?= ltrim($generator->modelClass, '\\') ?> */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="<?= Inflector::camel2id(StringHelper::basename($generator->modelClass)) ?>-form">

    <?= "<?php " ?>$form = ActiveForm::begin(); ?>

<?php foreach ($generator->getColumnNames() as $attribute) {
    if (in_array($attribute, $safeAttributes)) {
        if(!($generator->dontGenerateField($attribute))){
            echo "    <?= " . $generator->generateActiveField($attribute) . " ?>\n\n";
        }
    }
} ?>
    <div class="form-group">
        <?= "<?= " ?>Html::submitButton($model->isNewRecord ? <?= $generator->generateString('Create') ?> : <?= $generator->generateString('Update') ?>, ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
    </div>

    <?= "<?php " ?>ActiveForm::end(); ?>

</div>
