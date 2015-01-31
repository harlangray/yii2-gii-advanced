<?php

/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\gii\generators\crud;

use Yii;
use yii\db\ActiveRecord;
use yii\db\BaseActiveRecord;
use yii\db\Schema;
use yii\gii\CodeFile;
use yii\helpers\Inflector;
use yii\helpers\VarDumper;
use yii\web\Controller;

/**
 * Generates CRUD
 *
 * @property array $columnNames Model column names. This property is read-only.
 * @property string $controllerID The controller ID (without the module ID prefix). This property is
 * read-only.
 * @property array $searchAttributes Searchable attributes. This property is read-only.
 * @property boolean|\yii\db\TableSchema $tableSchema This property is read-only.
 * @property string $viewPath The controller view path. This property is read-only.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class Generator extends \yii\gii\Generator {

     public $modelClass;
     public $controllerClass;
     public $viewPath;
     public $baseControllerClass = 'yii\web\Controller';
     public $indexWidgetType = 'grid';
     public $searchModelClass = '';

     /**
      * @inheritdoc
      */
     public function getName() {
          return 'CRUD Generator';
     }

     /**
      * @inheritdoc
      */
     public function getDescription() {
          return 'This generator generates a controller and views that implement CRUD (Create, Read, Update, Delete)
            operations for the specified data model.';
     }

     /**
      * @inheritdoc
      */
     public function rules() {
          return array_merge(parent::rules(), [
              [['controllerClass', 'modelClass', 'searchModelClass', 'baseControllerClass'], 'filter', 'filter' => 'trim'],
              [['modelClass', 'controllerClass', 'baseControllerClass', 'indexWidgetType'], 'required'],
              [['searchModelClass'], 'compare', 'compareAttribute' => 'modelClass', 'operator' => '!==', 'message' => 'Search Model Class must not be equal to Model Class.'],
              [['modelClass', 'controllerClass', 'baseControllerClass', 'searchModelClass'], 'match', 'pattern' => '/^[\w\\\\]*$/', 'message' => 'Only word characters and backslashes are allowed.'],
              [['modelClass'], 'validateClass', 'params' => ['extends' => BaseActiveRecord::className()]],
              [['baseControllerClass'], 'validateClass', 'params' => ['extends' => Controller::className()]],
              [['controllerClass'], 'match', 'pattern' => '/Controller$/', 'message' => 'Controller class name must be suffixed with "Controller".'],
              [['controllerClass'], 'match', 'pattern' => '/(^|\\\\)[A-Z][^\\\\]+Controller$/', 'message' => 'Controller class name must start with an uppercase letter.'],
              [['controllerClass', 'searchModelClass'], 'validateNewClass'],
              [['indexWidgetType'], 'in', 'range' => ['grid', 'list']],
              [['modelClass'], 'validateModelClass'],
              [['enableI18N'], 'boolean'],
              [['messageCategory'], 'validateMessageCategory', 'skipOnEmpty' => false],
              ['viewPath', 'safe'],
          ]);
     }

     /**
      * @inheritdoc
      */
     public function attributeLabels() {
          return array_merge(parent::attributeLabels(), [
              'modelClass' => 'Model Class',
              'controllerClass' => 'Controller Class',
              'viewPath' => 'View Path',
              'baseControllerClass' => 'Base Controller Class',
              'indexWidgetType' => 'Widget Used in Index Page',
              'searchModelClass' => 'Search Model Class',
          ]);
     }

     /**
      * @inheritdoc
      */
     public function hints() {
          return array_merge(parent::hints(), [
              'modelClass' => 'This is the ActiveRecord class associated with the table that CRUD will be built upon.
                You should provide a fully qualified class name, e.g., <code>app\models\Post</code>.',
              'controllerClass' => 'This is the name of the controller class to be generated. You should
                provide a fully qualified namespaced class (e.g. <code>app\controllers\PostController</code>),
                and class name should be in CamelCase with an uppercase first letter. Make sure the class
                is using the same namespace as specified by your application\'s controllerNamespace property.',
              'viewPath' => 'Specify the directory for storing the view scripts for the controller. You may use path alias here, e.g.,
                <code>/var/www/basic/controllers/views/post</code>, <code>@app/views/post</code>. If not set, it will default
                to <code>@app/views/ControllerID</code>',
              'baseControllerClass' => 'This is the class that the new CRUD controller class will extend from.
                You should provide a fully qualified class name, e.g., <code>yii\web\Controller</code>.',
              'indexWidgetType' => 'This is the widget type to be used in the index page to display list of the models.
                You may choose either <code>GridView</code> or <code>ListView</code>',
              'searchModelClass' => 'This is the name of the search model class to be generated. You should provide a fully
                qualified namespaced class name, e.g., <code>app\models\PostSearch</code>.',
          ]);
     }

     /**
      * @inheritdoc
      */
     public function requiredTemplates() {
          return ['controller.php'];
     }

     /**
      * @inheritdoc
      */
     public function stickyAttributes() {
          return array_merge(parent::stickyAttributes(), ['baseControllerClass', 'indexWidgetType']);
     }

     /**
      * Checks if model class is valid
      */
     public function validateModelClass() {
          /* @var $class ActiveRecord */
          $class = $this->modelClass;
          $pk = $class::primaryKey();
          if (empty($pk)) {
               $this->addError('modelClass', "The table associated with $class must have primary key(s).");
          }
     }

     /**
      * @inheritdoc
      */
     public function generate() {
          $controllerFile = Yii::getAlias('@' . str_replace('\\', '/', ltrim($this->controllerClass, '\\')) . '.php');

          $files = [
              new CodeFile($controllerFile, $this->render('controller.php')),
          ];

          if (!empty($this->searchModelClass)) {
               $searchModel = Yii::getAlias('@' . str_replace('\\', '/', ltrim($this->searchModelClass, '\\') . '.php'));
               $files[] = new CodeFile($searchModel, $this->render('search.php'));
          }

          $viewPath = $this->getViewPath();
          $templatePath = $this->getTemplatePath() . '/views';
          foreach (scandir($templatePath) as $file) {
               if (empty($this->searchModelClass) && $file === '_search.php') {
                    continue;
               }

               //don't add detailed files here
               if ($file === '_detail_grid.php' || $file === '_detail_row.php') {
                    continue;
               }

               if (is_file($templatePath . '/' . $file) && pathinfo($file, PATHINFO_EXTENSION) === 'php') {
                    $files[] = new CodeFile("$viewPath/$file", $this->render("views/$file"));
               }
          }

          $schema = $this->getTableSchema();
          $masterTable = $schema->fullName;
          
          $relatedDetailTables = $this->getRelatedTableAndFields($masterTable);
//         echo '<pre>';
//         echo $masterTable . '<br>';
//         echo print_r($relatedDetailTables);
//         echo '</pre>';
         
         foreach ($relatedDetailTables as $relatedTable){
             $tableName = $relatedTable['tabelName'];
             $fieldName = $relatedTable['relatedField'];
             
             //grid file
             $file = '_' . $tableName . '_grid.php';
             $files[] = new CodeFile("$viewPath/$file", $this->render("views/_detail_grid.php", ['tableName' => $tableName, 'fieldName' => $fieldName]));
             
             //row file
             $file = '_' . $tableName . '_row.php';
             $files[] = new CodeFile("$viewPath/$file", $this->render("views/_detail_row.php", ['tableName' => $tableName, 'fieldName' => $fieldName]));
             
         }
                         
          return $files;
     }

     /**
      * @return string the controller ID (without the module ID prefix)
      */
     public function getControllerID() {
          $pos = strrpos($this->controllerClass, '\\');
          $class = substr(substr($this->controllerClass, $pos + 1), 0, -10);

          return Inflector::camel2id($class);
     }

     /**
      * @return string the controller view path
      */
     public function getViewPath() {
          if (empty($this->viewPath)) {
               return Yii::getAlias('@app/views/' . $this->getControllerID());
          } else {
               return Yii::getAlias($this->viewPath);
          }
     }

     public function getNameAttribute() {
          foreach ($this->getColumnNames() as $name) {
//            if (!strcasecmp($name, 'name') || !strcasecmp($name, 'title')) {
               if (strpos($name, 'name') !== false || strpos($name, 'title') !== false) {
                    return $name;
               }
          }
          /* @var $class \yii\db\ActiveRecord */
          $class = $this->modelClass;
          $pk = $class::primaryKey();

          return $pk[0];
     }

     /**
      * Generates code for active field
      * @param string $attribute
      * @return string
      */
     public function generateActiveField($attribute) {
          $tableSchema = $this->getTableSchema(); //die(print_r($tableSchema));
          if ($tableSchema === false || !isset($tableSchema->columns[$attribute])) {
               if (preg_match('/^(password|pass|passwd|passcode)$/i', $attribute)) {
                    return "\$form->field(\$model, '$attribute')->passwordInput()";
               } else {
                    return "\$form->field(\$model, '$attribute')";
               }
          }
          $column = $tableSchema->columns[$attribute];
          if ($column->phpType === 'boolean') {
               return "\$form->field(\$model, '$attribute')->checkbox()";
          } elseif ($column->type === 'text') {
               return "\$form->field(\$model, '$attribute')->textarea(['rows' => 6])";
          } elseif ($column->type === 'date') {
               return " ''
                ?>
                 <?php
    echo Html::activeLabel(\$model,'$attribute');;
    echo DatePicker::widget([
        'model' => \$model,
        'attribute' => '$attribute',
        'options' => ['placeholder' => 'Select date ...'],
        'pluginOptions' => [      
            'todayHighlight' => true,
            'autoclose'=>true,
            'format' => 'yyyy-mm-dd'                    
        ]
]);
    ?>
    <?= ''
             ";
          } elseif ($column->type === 'datetime') {
               return " ''
                ?>
                 <?php
    echo Html::activeLabel(\$model,'$attribute');;
    echo DateTimePicker::widget([
        'model' => \$model,
        'attribute' => '$attribute',
        'options' => ['placeholder' => 'Select time ...'],
        'pluginOptions' => [      
            'todayHighlight' => true,
            'autoclose'=>true,
            'format' => 'yyyy-mm-dd hh:ii'                    
        ]
]);
    ?>
    <?= ''
             ";
          } elseif ($column->type === 'integer' && $this->getForeignKeyInfo($attribute) !== null) {
               $foreignKeyInfo = $this->getForeignKeyInfo($attribute);
               $foreignTable = $foreignKeyInfo['foreignTable'];
               $foreignKey = $foreignKeyInfo['foreignKey'];


               $modGen = new yii\gii\generators\model\Generator;
               $className = $modGen->generateClassName($foreignTable);

               $foreignFieldName = $this->getNameAttributeOfTable($foreignTable);

               return "\$form->field(\$model, '$attribute')->dropDownList(ArrayHelper::map($className::find()->orderBy('$foreignFieldName')->asArray()->all(), '$foreignKey', '$foreignFieldName'))";
          } else {
               if (preg_match('/^(password|pass|passwd|passcode)$/i', $column->name)) {
                    $input = 'passwordInput';
               } else {
                    $input = 'textInput';
               }
               if (is_array($column->enumValues) && count($column->enumValues) > 0) {
                    $dropDownOptions = [];
                    foreach ($column->enumValues as $enumValue) {
                         $dropDownOptions[$enumValue] = Inflector::humanize($enumValue);
                    }
                    return "\$form->field(\$model, '$attribute')->dropDownList("
                            . preg_replace("/\n\s*/", ' ', VarDumper::export($dropDownOptions)) . ", ['prompt' => ''])";
               } elseif ($column->phpType !== 'string' || $column->size === null) {
                    return "\$form->field(\$model, '$attribute')->$input()";
               } else {
                    return "\$form->field(\$model, '$attribute')->$input(['maxlength' => $column->size])";
               }
          }
     }

     public function generateTabularActiveField($tableName, $attribute) {
         $db = Yii::$app->get('db', false);
         $tableSchema = $db->getSchema()->getTableSchema($tableName);

          if ($tableSchema === false || !isset($tableSchema->columns[$attribute])) {
               if (preg_match('/^(password|pass|passwd|passcode)$/i', $attribute)) {
                    return "\$form->field(\${$tableName}Mod, '$attribute', ['template' => '{input}{hint}{error}'])->passwordInput()";
               } else {
                    return "\$form->field(\${$tableName}Mod, '$attribute', ['template' => '{input}{hint}{error}'])";
               }
          }
          $column = $tableSchema->columns[$attribute];
          
          if($column->isPrimaryKey){
                return "\$form->field(\${$tableName}Mod, \"[\$index]$attribute\", ['template' => '{input}{hint}{error}'])->textInput(['readonly' => true, 'style' => 'width : 60px']);";
          }elseif ($column->phpType === 'boolean') {
                return "\$form->field(\${$tableName}Mod, \"[\$index]$attribute\", ['template' => '{input}{hint}{error}'])->checkbox([], false)";
          } elseif ($column->type === 'smallint' && $column->size == 1){
                return "\$form->field(\${$tableName}Mod, \"[\$index]$attribute\", ['template' => '{input}{hint}{error}'])->checkbox([], false)";
          }elseif ($column->type === 'text') {
               return "\$form->field(\${$tableName}Mod, \"[\$index]$attribute\", ['template' => '{input}{hint}{error}'])->textarea(['rows' => 6])";
          } elseif ($column->type === 'date') {
               return " ''
                ?>
                 <?php
    echo \kartik\date\DatePicker::widget([
        'model' => \${$tableName}Mod,
        'attribute' => \"[\$index]$attribute\",
        'options' => ['placeholder' => 'Select date ...', 'class' => 'detaildatepicker'],
        'pluginOptions' => [      
            'todayHighlight' => true,
            'autoclose'=>true,
            'format' => 'yyyy-mm-dd'                    
        ]
]);
    ?>
    <?= ''
             ";
          } elseif ($column->type === 'datetime') {
               return " ''
                ?>
                 <?php
    echo kartik\datetime\DateTimePicker::widget([
        'model' => \${$tableName}Mod,
        'attribute' => \"[\$index]$attribute\",
        'options' => ['placeholder' => 'Select time ...', 'class' => 'detaildatetimepicker'],
        'pluginOptions' => [      
            'todayHighlight' => true,
            'autoclose'=>true,
            'format' => 'yyyy-mm-dd hh:ii'                    
        ]
]);
    ?>
    <?= ''
             ";
          } elseif ($column->type === 'integer' && $this->getForeignKeyInfo($attribute, $tableName) !== null) {
               $foreignKeyInfo = $this->getForeignKeyInfo($attribute, $tableName);
               $foreignTable = $foreignKeyInfo['foreignTable'];
               $foreignKey = $foreignKeyInfo['foreignKey'];


               $modGen = new yii\gii\generators\model\Generator;
               $className = $modGen->generateClassName($foreignTable);

               $foreignFieldName = $this->getNameAttributeOfTable($foreignTable);

               return "\$form->field(\${$tableName}Mod, \"[\$index]$attribute\", ['template' => '{input}{hint}{error}'])->dropDownList(\yii\helpers\ArrayHelper::map(app\models\\{$className}::find()->orderBy('$foreignFieldName')->asArray()->all(), '$foreignKey', '$foreignFieldName'), ['prompt' => 'Select...'])";
          } else {
               if (preg_match('/^(password|pass|passwd|passcode)$/i', $column->name)) {
                    $input = 'passwordInput';
               } else {
                    $input = 'textInput';
               }
               if (is_array($column->enumValues) && count($column->enumValues) > 0) {
                    $dropDownOptions = [];
                    foreach ($column->enumValues as $enumValue) {
                         $dropDownOptions[$enumValue] = Inflector::humanize($enumValue);
                    }
                    return "\$form->field(\${$tableName}Mod, \"[\$index]$attribute\", ['template' => '{input}{hint}{error}'])->dropDownList("
                            . preg_replace("/\n\s*/", ' ', VarDumper::export($dropDownOptions)) . ", ['prompt' => ''])";
               } elseif ($column->phpType !== 'string' || $column->size === null) {
                    return "\$form->field(\${$tableName}Mod, \"[\$index]$attribute\", ['template' => '{input}{hint}{error}'])->$input()";
               } else {
                    return "\$form->field(\${$tableName}Mod, \"[\$index]$attribute\", ['template' => '{input}{hint}{error}'])->$input(['maxlength' => $column->size])";
               }
          }
     }
     
     public function generateKartikField($attribute) {
          $tableSchema = $this->getTableSchema();
          $column = $tableSchema->columns[$attribute];
          $placeHolder = '';
          $hint = '';
//        return $column->phpType;
//        return $column->size;
          if ($column->phpType === 'boolean') {
               return "['type'=>Form::INPUT_CHECKBOX]";
          } elseif ($column->type === 'smallint' && $column->size == 1) {
               return "['type'=>Form::INPUT_CHECKBOX]";
          } elseif ($column->type === 'text') {
               return "['type'=>Form::INPUT_TEXTAREA, 'options'=>['placeholder'=>'$placeHolder']]";
          } elseif ($column->type === 'date') {
               return "['type'=>Form::INPUT_WIDGET, "
                       . "'widgetClass'=>'\kartik\widgets\DatePicker',"
                       . "'hint'=>'$hint',"
                       . "'options' => ['pluginOptions' => ['format' => 'yyyy-mm-dd', 'autoclose'=>true, 'todayHighlight' => true]]]";
          } elseif ($column->type === 'datetime') {
               return "['type'=>Form::INPUT_WIDGET, "
                       . "'widgetClass'=>'\kartik\widgets\DateTimePicker',"
                       . "'hint'=>'$hint',"
                       . "'options' => ['pluginOptions'=>['format' => 'yyyy-mm-dd h:i', 'autoclose'=>true, 'todayHighlight' => true]]]";
          } elseif ($column->type === 'integer' && $this->getForeignKeyInfo($attribute) !== null) {
               $foreignKeyInfo = $this->getForeignKeyInfo($attribute);
               $foreignTable = $foreignKeyInfo['foreignTable'];
               $foreignKey = $foreignKeyInfo['foreignKey'];


               $modGen = new yii\gii\generators\model\Generator;
               $className = $modGen->generateClassName($foreignTable);

               $foreignFieldName = $this->getNameAttributeOfTable($foreignTable);

               return "['type'=>Form::INPUT_DROPDOWN_LIST, "
                       . "'items' => ArrayHelper::map($className::find()->orderBy('$foreignFieldName')->asArray()->all(), '$foreignKey', '$foreignFieldName'),"
                       . "'options'=>['prompt' => 'Select...', 'placeholder'=>'$placeHolder']]";
          } else {
               return "['type'=>Form::INPUT_TEXT, 'options'=>['placeholder'=>'$placeHolder']]";
          }
     }

     /**
      * Generates code for active search field
      * @param string $attribute
      * @return string
      */
     public function generateActiveSearchField($attribute) {
          $tableSchema = $this->getTableSchema();
          if ($tableSchema === false) {
               return "\$form->field(\$model, '$attribute')";
          }
          $column = $tableSchema->columns[$attribute];
          if ($column->phpType === 'boolean') {
               return "\$form->field(\$model, '$attribute')->checkbox()";
          } else {
               return "\$form->field(\$model, '$attribute')";
          }
     }

     /**
      * Generates column format
      * @param \yii\db\ColumnSchema $column
      * @return string
      */
     public function generateColumnFormat($column) {
          if ($column->phpType === 'boolean') {
               return 'boolean';
          } elseif ($column->type === 'text') {
               return 'ntext';
          } elseif (stripos($column->name, 'time') !== false && $column->phpType === 'integer') {
               return 'datetime';
          } elseif (stripos($column->name, 'email') !== false) {
               return 'email';
          } elseif (stripos($column->name, 'url') !== false) {
               return 'url';
          } else {
               return 'text';
          }
     }

     /**
      * Generates validation rules for the search model.
      * @return array the generated validation rules
      */
     public function generateSearchRules() {
          if (($table = $this->getTableSchema()) === false) {
               return ["[['" . implode("', '", $this->getColumnNames()) . "'], 'safe']"];
          }
          $types = [];
          foreach ($table->columns as $column) {
               switch ($column->type) {
                    case Schema::TYPE_SMALLINT:
                    case Schema::TYPE_INTEGER:
                    case Schema::TYPE_BIGINT:
                         $types['integer'][] = $column->name;
                         break;
                    case Schema::TYPE_BOOLEAN:
                         $types['boolean'][] = $column->name;
                         break;
                    case Schema::TYPE_FLOAT:
                    case Schema::TYPE_DECIMAL:
                    case Schema::TYPE_MONEY:
                         $types['number'][] = $column->name;
                         break;
                    case Schema::TYPE_DATE:
                    case Schema::TYPE_TIME:
                    case Schema::TYPE_DATETIME:
                    case Schema::TYPE_TIMESTAMP:
                    default:
                         $types['safe'][] = $column->name;
                         break;
               }
          }

          $rules = [];
          foreach ($types as $type => $columns) {
               $rules[] = "[['" . implode("', '", $columns) . "'], '$type']";
          }

          return $rules;
     }

     /**
      * @return array searchable attributes
      */
     public function getSearchAttributes() {
          return $this->getColumnNames();
     }

     /**
      * Generates the attribute labels for the search model.
      * @return array the generated attribute labels (name => label)
      */
     public function generateSearchLabels() {
          /* @var $model \yii\base\Model */
          $model = new $this->modelClass();
          $attributeLabels = $model->attributeLabels();
          $labels = [];
          foreach ($this->getColumnNames() as $name) {
               if (isset($attributeLabels[$name])) {
                    $labels[$name] = $attributeLabels[$name];
               } else {
                    if (!strcasecmp($name, 'id')) {
                         $labels[$name] = 'ID';
                    } else {
                         $label = Inflector::camel2words($name);
                         if (!empty($label) && substr_compare($label, ' id', -3, 3, true) === 0) {
                              $label = substr($label, 0, -3) . ' ID';
                         }
                         $labels[$name] = $label;
                    }
               }
          }

          return $labels;
     }

     /**
      * Generates search conditions
      * @return array
      */
     public function generateSearchConditions() {
          $columns = [];
          if (($table = $this->getTableSchema()) === false) {
               $class = $this->modelClass;
               /* @var $model \yii\base\Model */
               $model = new $class();
               foreach ($model->attributes() as $attribute) {
                    $columns[$attribute] = 'unknown';
               }
          } else {
               foreach ($table->columns as $column) {
                    $columns[$column->name] = $column->type;
               }
          }

          $likeConditions = [];
          $hashConditions = [];
          foreach ($columns as $column => $type) {
               switch ($type) {
                    case Schema::TYPE_SMALLINT:
                    case Schema::TYPE_INTEGER:
                    case Schema::TYPE_BIGINT:
                    case Schema::TYPE_BOOLEAN:
                    case Schema::TYPE_FLOAT:
                    case Schema::TYPE_DECIMAL:
                    case Schema::TYPE_MONEY:
                    case Schema::TYPE_DATE:
                    case Schema::TYPE_TIME:
                    case Schema::TYPE_DATETIME:
                    case Schema::TYPE_TIMESTAMP:
                         $hashConditions[] = "'{$column}' => \$this->{$column},";
                         break;
                    default:
                         $likeConditions[] = "->andFilterWhere(['like', '{$column}', \$this->{$column}])";
                         break;
               }
          }

          $conditions = [];
          if (!empty($hashConditions)) {
               $conditions[] = "\$query->andFilterWhere([\n"
                       . str_repeat(' ', 12) . implode("\n" . str_repeat(' ', 12), $hashConditions)
                       . "\n" . str_repeat(' ', 8) . "]);\n";
          }
          if (!empty($likeConditions)) {
               $conditions[] = "\$query" . implode("\n" . str_repeat(' ', 12), $likeConditions) . ";\n";
          }

          return $conditions;
     }

     /**
      * Generates URL parameters
      * @return string
      */
     public function generateUrlParams() {
          /* @var $class ActiveRecord */
          $class = $this->modelClass;
          $pks = $class::primaryKey();
          if (count($pks) === 1) {
               if (is_subclass_of($class, 'yii\mongodb\ActiveRecord')) {
                    return "'id' => (string)\$model->{$pks[0]}";
               } else {
                    return "'id' => \$model->{$pks[0]}";
               }
          } else {
               $params = [];
               foreach ($pks as $pk) {
                    if (is_subclass_of($class, 'yii\mongodb\ActiveRecord')) {
                         $params[] = "'$pk' => (string)\$model->$pk";
                    } else {
                         $params[] = "'$pk' => \$model->$pk";
                    }
               }

               return implode(', ', $params);
          }
     }

     /**
      * Generates action parameters
      * @return string
      */
     public function generateActionParams() {
          /* @var $class ActiveRecord */
          $class = $this->modelClass;
          $pks = $class::primaryKey();
          if (count($pks) === 1) {
               return '$id';
          } else {
               return '$' . implode(', $', $pks);
          }
     }

     /**
      * Generates parameter tags for phpdoc
      * @return array parameter tags for phpdoc
      */
     public function generateActionParamComments() {
          /* @var $class ActiveRecord */
          $class = $this->modelClass;
          $pks = $class::primaryKey();
          if (($table = $this->getTableSchema()) === false) {
               $params = [];
               foreach ($pks as $pk) {
                    $params[] = '@param ' . (substr(strtolower($pk), -2) == 'id' ? 'integer' : 'string') . ' $' . $pk;
               }

               return $params;
          }
          if (count($pks) === 1) {
               return ['@param ' . $table->columns[$pks[0]]->phpType . ' $id'];
          } else {
               $params = [];
               foreach ($pks as $pk) {
                    $params[] = '@param ' . $table->columns[$pk]->phpType . ' $' . $pk;
               }

               return $params;
          }
     }

     /**
      * Returns table schema for current model class or false if it is not an active record
      * @return boolean|\yii\db\TableSchema
      */
     public function getTableSchema() {
          /* @var $class ActiveRecord */
          $class = $this->modelClass;
          if (is_subclass_of($class, 'yii\db\ActiveRecord')) {
               return $class::getTableSchema();
          } else {
               return false;
          }
     }

     /**
      * @return array model column names
      */
     public function getColumnNames() {
          /* @var $class ActiveRecord */
          $class = $this->modelClass;
          if (is_subclass_of($class, 'yii\db\ActiveRecord')) {
               return $class::getTableSchema()->getColumnNames();
          } else {
               /* @var $model \yii\base\Model */
               $model = new $class();

               return $model->attributes();
          }
     }

     public function hasDateField() {
          $tableSchema = $this->getTableSchema();

          foreach ($tableSchema->columns as $column) {
               if ($column->type === 'date') {
                    return true;
               }
          }
          return false;
     }

     public function hasDateTimeField() {
          $tableSchema = $this->getTableSchema();

          foreach ($tableSchema->columns as $column) {
               if ($column->type === 'datetime') {
                    return true;
               }
          }
          return false;
     }

     public function hasForeignTable() {
          $tableSchema = $this->getTableSchema();

          $foreignKeys = $tableSchema->foreignKeys;
          if ($foreignKeys === null || count($foreignKeys) == 0) {
               return false;
          }
          return true;
     }

     public function getForeignKeyInfo($columnName, $tableName = '') {
         if($tableName == ''){
            $tableSchema = $this->getTableSchema();
         }
         else{
             $db = Yii::$app->get('db', false);
             $tableSchema = $db->getSchema()->getTableSchema($tableName);
         }

          $foreignKeys = [];

          foreach ($tableSchema->foreignKeys as $fKey) {
               if (isset($fKey[$columnName])) {
                    $foreignKeys['foreignTable'] = $fKey['0'];
                    $foreignKeys['foreignKey'] = $fKey[$columnName];

                    return $foreignKeys;
               }
          }

          return;
     }

     public function getNameAttributeOfTable($table) {
          $db = Yii::$app->get('db', false);
          $schema = $db->getSchema()->getTableSchema($table);

          $secondCol = '';
          $cnt = 0;

          foreach ($schema->columns as $columnName => $column) {
               if (strpos($columnName, 'name') !== false || strpos($columnName, 'title') !== false) {
                    return $columnName;
               }
               $cnt++;
               if ($cnt == 2) {
                    $secondCol = $columnName;
               }
          }
          if ($secondCol != '')
               return $secondCol;

          return $schema->primaryKey[0];
     }

     public function getRelatedFieldDtls($columnName) {
          $relatedFieldDtls = [];
          $foreignKeyInfo = [];

          $foreignKeyInfo = $this->getForeignKeyInfo($columnName);
          if ($foreignKeyInfo !== null) {
               $foreignTable = $foreignKeyInfo['foreignTable'];
               $foreignFieldName = $this->getNameAttributeOfTable($foreignTable);

               $modGen = new yii\gii\generators\model\Generator;
               $relationName = $modGen->generateRelationName([], '', '', $columnName, false);
//            $relationName = lcfirst($relationName);
               $relatedFieldDtls['relationName'] = $relationName;
               $relatedFieldDtls['foreignFieldName'] = $foreignFieldName;

               return $relatedFieldDtls;
          }
          return;
     }

     public function dontGenerateField($columnName) {
          if (strpos($columnName, 'created_at') == true ||
                  strpos($columnName, 'created_by') == true ||
                  strpos($columnName, 'updated_at') == true ||
                  strpos($columnName, 'updated_by') == true ||
                  strpos($columnName, 'is_deleted') == true ||
                  strpos($columnName, 'deleted_by') == true ||
                  strpos($columnName, 'deleted_at') == true
          ) {
               return true;
          }
          return false;
     }

     public function getRelatedTableAndFields($masterTable) {
          $db = Yii::$app->get('db', false);
          if ($db === null) {
               return [];
          }
          $relatedTables = [
//              [
//                  'tabelName' => 
//                  'relatedField' => ''
//              ],
          ];
          $dbchema = $db->getSchema();
          foreach ($dbchema->getTableNames() as $tableName) {
               $tableSchema = $dbchema->getTableSchema($tableName);

               $foreignKeys = [];

               foreach ($tableSchema->foreignKeys as $fKey) {
                    //The foreign key array ($fKey) has the following format
                    //
//                    ex - for 'country' table
//                    Array
//                    (
//                        [0] => continent
//                        [cn_continent_id] => co_id
//                    )
                    
                    if($fKey['0'] == $masterTable){
                        
//                         echo '<pre>';
//                         echo $tableName . '</br>';
//                         echo print_r($tableSchema);
//                         echo '</pre>';
                         
                         $relatedTable = [];
                         $masterDetailForm = false;
                         foreach ($fKey as $index => $value){
                              if($index == '0'){
                                   $relatedTable['tabelName'] = $tableName;
                              }
                              else{                                  
                                   $relatedTable['relatedField'] = $index;
                                   if($tableSchema->columns[$index]->comment == '<md>'){
                                       $masterDetailForm = true;
                                   }
                              }
                         }
                         if($masterDetailForm){
                            $relatedTables[] = $relatedTable;
                         }
                    }

               }
        }

        return $relatedTables;
    }

    public function getColumnNamesOfTable($tableName) {
        $db = Yii::$app->get('db', false);
        $schema = $db->getSchema()->getTableSchema($tableName);
        $fields = [];
        foreach ($schema->columns as $columnName => $column) {
            $fields[] = $columnName;
        }
        
        return $fields;
    }
    
    public function getPrimaryKeyOfTable($tableName = '') {
         if($tableName == ''){
            $tableSchema = $this->getTableSchema();
         }
         else{
             $db = Yii::$app->get('db', false);
             $tableSchema = $db->getSchema()->getTableSchema($tableName);
         } 
         
         return $tableSchema->primaryKey[0];
    }
}