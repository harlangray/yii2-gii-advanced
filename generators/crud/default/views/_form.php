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
?>
<?= "<?php\n";?>
use yii\helpers\Html;
use kartik\widgets\ActiveForm;
use kartik\builder\FormGrid;
use kartik\builder\Form;

<?php
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

$form = ActiveForm::begin();
echo FormGrid::widget([
    'model' => $model,
    'form' => $form,
    'autoGenerateColumns' => true,
    'rows' => [
    
    
<?php 
$attributes = $generator->getColumnNames();

foreach ($attributes as $key => $attribute){
    if($generator->dontGenerateField($attribute) || !in_array($attribute, $safeAttributes)){
        unset($attributes[$key]);
    }
}
$size = 3;
$attributChunks = array_chunk($attributes, $size);

foreach ($attributChunks as $attributes) {
    echo "[\n";
    echo "'attributes' => [\n";
    foreach ($attributes as $attribute){
 
            
                ?>
        '<?= $attribute;?>' => <?= $generator->generateKartikField($attribute)?>,

                <?php
            
 
    }
    echo "],\n";
    echo "],\n";
}
?>
   
    
    ]
]);


<?php
    $schema = $generator->getTableSchema();
    $masterTable = $schema->fullName;  
  
    $relatedDetailTables = $generator->getRelatedTableAndFields($masterTable);
    $generateDetails = false;
    
    if(count($relatedDetailTables) > 0)
         $generateDetails = true;
    if($generateDetails){
?>
use kartik\tabs\TabsX;
echo TabsX::widget([
    'position'=>TabsX::POS_ABOVE,
    'bordered'=>true,
    'encodeLabels'=>false,
    'items' => [    
    <?php

      foreach ($relatedDetailTables as $relatedTable){
        $tableName = $relatedTable['tabelName'];
        $fieldName = $relatedTable['relatedField'];
        
        $modGen = new yii\gii\generators\model\Generator;
        $className = $modGen->generateClassName($tableName);
        $className = Inflector::pluralize($className);
?>
[
'label' => '<?= $className; ?>',
'content' => $this->render('_<?= $tableName; ?>_grid', [
         'form' => $form,
         'model' => $model,
         '<?= $tableName; ?>Mods' => $<?= $tableName; ?>Mods,
     ])
],
<?php
    }
?>
     ]
]);
<?php
    } //end of if($generateDetails)
?>

<?= '?>'?>
    <div class="form-group">
        <?= "<?= " ?>Html::submitButton($model->isNewRecord ? <?= $generator->generateString('Create') ?> : <?= $generator->generateString('Update') ?>, ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
    </div>

<?= "<?php\n";?>
ActiveForm::end();
<?= '?>'?>