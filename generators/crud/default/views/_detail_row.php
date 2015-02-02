<?php
$modGen = new yii\gii\generators\model\Generator;
$className = $modGen->generateClassName($tableName);
?>
<?= "<?php\n"; ?>


<?php

?>

<?= "?>"; ?>

<?= "<tr id=\"{$tableName}_row_<?= \$index; ?>\">"; ?>
    <?php        
        $columns = $generator->getColumnNamesOfTable($tableName);
        foreach ($columns as $columnName){
             if($generator->dontGenerateField($columnName)){
                 continue;
             }
             if($columnName == $fieldName){//no need to display the linked column
                 echo "<td class=\"v-align-top hidden\"><?=\$form->field(\${$tableName}Mod, \"[\$index]{$columnName}\", ['template' => '{input}{hint}{error}'])->textInput(['readonly' => true, 'style' => 'width : 60px']); ?></td>\n";
                 continue;
             }             
    ?>    
    <td class="v-align-top"><?= "<?=" . $generator->generateTabularActiveField($tableName, $columnName) . " ?>";?></td>
    <?php
         }
    ?>
    <td class="h-align-center">
        <a title="Remove" onclick="remove<?= $tableName; ?>Row('<?= $tableName; ?>_row_<?= "<?= \$index ?>"; ?>')">
            <span class="glyphicon glyphicon-trash"></span>
        </a>
    </td>      
</tr>
