<?php
/**
 * This is the template for generating the model class of a specified table.
 */

/* @var $this yii\web\View */
/* @var $generator yii\gii\generators\model\Generator */
/* @var $tableName string full table name */
/* @var $className string class name */
/* @var $tableSchema yii\db\TableSchema */
/* @var $labels string[] list of attribute labels (name => label) */
/* @var $rules string[] list of validation rules */
/* @var $relations array list of relations (name => relation declaration) */

echo "<?php\n";
?>

<?php
    //find the most used prefix
    $firstWordArr = [];
    $totalLabels = 0;
    $totalMostUsed = 0;
    foreach ($labels as $name => $label){
        $arr =  explode(" ", $label);
        $firstWordArr[] = $arr[0];
    }
    $c = array_count_values($firstWordArr); 
    $totalLabels = count($labels);
    $totalMostUsed = max($c);
    
    if($totalMostUsed > $totalLabels / 2){
        $mostUsed = array_search($totalMostUsed, $c);

        $prefix = $mostUsed . ' ';

        //remove the most used prefix
        foreach ($labels as $name => $label){  
            $str = $label;
            if (substr($str, 0, strlen($prefix)) == $prefix) {
                $str = substr($str, strlen($prefix));
                $labels[$name] = $str;
            }         
        }
    }
    
    //remov ID at the end
    foreach ($labels as $name => $label){
        $label = str_replace(' ID', '', $label);
        $labels[$name] = $label;
    }
?>
namespace <?= $generator->ns ?>;

use Yii;
<?php 
//echo '<pre>';
//echo print_r($relations);
//echo '</pre>';
    $createdTimeColumn = $generator->findMatchingField('created_at');
    $updatedTimeColumn = $generator->findMatchingField('updated_at'); 
    
    if($createdTimeColumn || $updatedTimeColumn){
        echo 'use yii\db\Expression;';
    }
?>
/**
 * This is the model class for table "<?= $generator->generateTableName($tableName) ?>".
 *
<?php foreach ($tableSchema->columns as $column): ?>
 * @property <?= "{$column->phpType} \${$column->name}\n" ?>
<?php endforeach; ?>
<?php if (!empty($relations)): ?>
 *
<?php foreach ($relations as $name => $relation): ?>
 * @property <?= $relation[1] . ($relation[2] ? '[]' : '') . ' $' . lcfirst($name) . "\n" ?>
<?php endforeach; ?>
<?php endif; ?>
 */
class <?= $className ?> extends <?= '\\' . ltrim($generator->baseClass, '\\') . "\n" ?>
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '<?= $generator->generateTableName($tableName) ?>';
    }
<?php if ($generator->db !== 'db'): ?>

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('<?= $generator->db ?>');
    }
<?php endif; ?>

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [<?= "\n            " . implode(",\n            ", $rules) . "\n        " ?>];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
<?php foreach ($labels as $name => $label): ?>
            <?= "'$name' => " . $generator->generateString($label) . ",\n" ?>
<?php endforeach; ?>
        ];
    }
<?php foreach ($relations as $name => $relation): ?>

    /**
     * @return \yii\db\ActiveQuery
     */
    public function get<?= $name ?>()
    {
        <?= $relation[0] . "\n" ?>
    }
<?php endforeach; ?>
    
    public function behaviors() {
        return [
            'LoggableBehavior' => [
                'class' => 'yii\behaviors\LoggableBehavior',
                'storeTimestamp' => false
            ],
            
            <?php
            $createdByColumn = $generator->findMatchingField('created_by');
            $updatedByColumn = $generator->findMatchingField('updated_by');
            $createdTimeColumn = $generator->findMatchingField('created_at');
            $updatedTimeColumn = $generator->findMatchingField('updated_at'); 
            $deletedColumn = $generator->findMatchingField('is_deleted');

            ?>
            
            
            <?php
            if($createdByColumn || $updatedByColumn){
            ?>
       'AttributeBehaviorUser' => [
            'class' => 'yii\behaviors\AttributeBehavior',
            'attributes' => [
                <?= ($createdByColumn)?"$className::EVENT_BEFORE_INSERT => '$createdByColumn',\n":""; ?>
                <?= ($updatedByColumn)?"$className::EVENT_BEFORE_UPDATE => '$updatedByColumn',\n":""; ?>
            ],  
            'value' => Yii::$app->getUser()->id,
            ],
            <?php
            }
            ?>
            
            <?php
            if($createdTimeColumn || $updatedTimeColumn){
            ?>
       'AttributeBehaviorTime' => [
            'class' => 'yii\behaviors\AttributeBehavior',
            'attributes' => [
                <?= ($createdTimeColumn)?"$className::EVENT_BEFORE_INSERT => '$createdTimeColumn',\n":""; ?>
                <?= ($updatedTimeColumn)?"$className::EVENT_BEFORE_UPDATE => '$updatedTimeColumn',\n":""; ?>
            ],  
            'value' => new Expression('NOW()'),
            ],
            <?php
            }
            ?>
            
            <?php
            if($deletedColumn){
            ?>
       'AttributeBehaviorDelete' => [
            'class' => 'yii\behaviors\AttributeBehavior',
            'attributes' => [
                <?= ($deletedColumn)?"$className::EVENT_BEFORE_INSERT => '$deletedColumn',\n":""; ?>
            ],  
            'value' => false,
            ],
            <?php
            }
            ?>            
            
            
            
            <?php
            $deletedColumn = $generator->findMatchingField('is_deleted');
            $deletedByColumn = $generator->findMatchingField('deleted_by');
            $deletedTimeColumn = $generator->findMatchingField('deleted_at'); 
            
            if($deletedColumn || $deletedByColumn || $deletedTimeColumn){
                ?>
            'SoftDeleteBehavior' => [
                'class' => 'yii\behaviors\SoftDeleteBehavior',
                'deletedColumn' => '<?= $deletedColumn?>',
                'deletedByColumn' => '<?= $deletedByColumn?>',
                'deletedTimeColumn' => '<?= $deletedTimeColumn?>',
            ]                
            <?php
            }
            ?>
        ];
    }
    
    <?php if($deletedColumn){ ?>
    public static function find() {
        return parent::find()->where(['<?= $deletedColumn?>' => false]);
    }    
    <?php } ?>

}
