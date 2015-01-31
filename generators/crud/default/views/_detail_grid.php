<?php
$modGen = new yii\gii\generators\model\Generator;
$className = $modGen->generateClassName($tableName);
$title = yii\helpers\Inflector::pluralize($className);
?>
<?= "<?php\n"; ?>
use app\models\<?= $className; ?>; 
use yii\helpers\Html;
<?= "?>"; ?>

<div class="panel panel-primary">
    <div class="panel-heading">
        <h3 class="panel-title"><i class="glyphicon glyphicon-list"></i>Manage <?= $title; ?></h3>        
    </div>
    <table id="<?= $tableName; ?>_grid">
        <tr>
            <?= "<?php \${$tableName}Mod = new {$className}; ?>\n"; ?>
         <?php
         $columns = $generator->getColumnNamesOfTable($tableName);
         $class = 'detail-header';
         foreach ($columns as $columnName){
             if($generator->dontGenerateField($columnName)){
                 continue;
             }  
             if($columnName == $fieldName){//no need to display the linked column
                 $class .= ' hidden';
//                 continue;
             }
             else{
                 $class = 'detail-header';
             }
         ?>
            <th class="<?= $class; ?>"><?= "<?= \${$tableName}Mod->getAttributeLabel('$columnName'); ?>"; ?></th>
          <?php
         }
          ?>
            <th class="detail-header">Action</th>
     </tr>
     
<?= "<?php\n"; ?>
$index = 0;
foreach ($<?= $tableName; ?>Mods as $index => $<?= $tableName; ?>Mod){
     echo $this->render('_<?= $tableName; ?>_row', [
         'form' => $form,
         '<?= $tableName; ?>Mod' => $<?= $tableName; ?>Mod,
         'index' => $index,
     ]);     
}
$<?= $tableName; ?>Cnt = $index + 1; 
<?= "?>"; ?>

</table>
    <div class="detail-button-panel">
<?= "<?php\n"; ?>
echo Html::button('Add New', [ 'class' => 'btn btn-success glyphicon glyphicon-plus', 'onClick' => 'add<?= $tableName; ?>Row();' ]);
<?= '?>'; ?>
    </div>
</div>


<?= "<script>\n"; ?>
    <?= "var {$tableName}Cnt= <?= \${$tableName}Cnt; ?>;"; ?>
    
    function add<?= $tableName; ?>Row(){
        <?= "<?php\n"; ?>
            $<?= $tableName; ?>Mod = new <?= $className; ?>();
            $<?= $tableName; ?>Mod-><?= $fieldName; ?> = isset($model-><?= $generator->getPrimaryKeyOfTable(); ?>)?$model-><?= $generator->getPrimaryKeyOfTable(); ?>:0;
            $rowHtml = $this->render('_<?= $tableName; ?>_row',
                array('form' => $form,
                    '<?= $tableName; ?>Mod' => $<?= $tableName; ?>Mod,
                    'index' => '#replace#'));
//            $rowHtml = str_replace(array("\r\n", "\r", "\n"), "", $rowHtml);
//            $rowHtml = str_replace("'", "\"", $rowHtml);
        <?= "?>\n"; ?>
       rowHtml = <?= "<?= json_encode(\$rowHtml); ?>"; ?>;    
       
       rowHtml = rowHtml.replace(/#replace#/g, <?= $tableName; ?>Cnt);
       <?= $tableName; ?>Cnt++;
           
       $('#<?= $tableName; ?>_grid').append(rowHtml);
       attachDatePickerForMDRows();
       attachDateTimePickerForMDRows();
    } 
    
    function remove<?= $tableName; ?>Row(id){
        var row = $("#" + id);
        row.html('');
    }    
<?= "</script>\n"; ?>