<?php
/**
* Create and set jForms controls based on QGIS form edit type.
* @package   lizmap
* @subpackage lizmap
* @author    3liz
* @copyright 2012 3liz
* @link      http://3liz.com
* @license Mozilla Public License : http://www.mozilla.org/MPL/
*/


class qgisFormControl{

    public $ref = '';
    
    // JForm control object
    public $ctrl = '';
    
    // Qgis edittype as a simpleXml object
    public $edittype = '';
    
    // Qgis field name
    public $fieldName = '';
    
    // Qgis field type
    public $fieldEditType = '';
    
    // Qgis field alias
    public $fieldAlias = '';
    
    // Qgis rendererCategories
    public $rendererCategories = '';
    
    // Qgis data type (text, float, integer, etc.)
    public $fieldDataType = '';
    
    // Read-only
    public $isReadOnly = False;
    
    // required
    public $required = False;
    
    // Value relation : one of the edittypes. We store information in an array
    public $valueRelationData = Null;
    
    // Table mapping QGIS and jelix forms
    public $qgisEdittypeMap = array(
      0 => array (
            'qgis'=>array('name'=>'Line edit', 'description'=>'Simple edit box'), 
            'jform'=>array('markup'=>'input')
      ),
      4 => array (
            'qgis'=>array('name'=>'Classification', 'description'=>'Display combobox containing values of attribute used for classification'), 
            'jform'=>array('markup'=>'menulist')
      ),
      5 => array (
            'qgis'=>array('name'=>'Range', 'description'=>'Allow one to set numeric values from a specified range. the edit widget can be either a slider or a spin box'), 
            'jform'=>array('markup'=>'menulist')
      ),
      2 => array (
            'qgis'=>array('name'=>'Unique values', 'description'=>'the user can select one of the values already used in the attribute. If editable, a line edit is shown with autocompletion support, otherwise a combo box is used'), 
            'jform'=>array('markup'=>'input')
      ),
      8 => array (
            'qgis'=>array('name'=>'File name', 'description'=>'Simplifies file selection by adding a file chooser dialog.'), 
            'jform'=>array('markup'=>'input')
      ),
      3 => array (
            'qgis'=>array('name'=>'Value map', 'description'=>'Combo box with predefined items. Value is stored in the attribute, description is shown in the combobox'), 
            'jform'=>array('markup'=>'menulist')
      ),
      -1 => array (
            'qgis'=>array('name'=>'Enumeration', 'description'=>'Combo box with values that can be used within the column s type. Must be supported by the provider.'), 
            'jform'=>array('markup'=>'input')
      ),
      10 => array (
            'qgis'=>array('name'=>'Immutable', 'description'=>'An immutable attribute is read-only- the user is not able to modify the contents.'), 
            'jform'=>array('markup'=>'input', 'readonly'=>true)
      ),
      11 => array (
            'qgis'=>array('name'=>'Hidden', 'description'=>'A hidden attribute will be invisible- the user is not able to see its contents'), 
            'jform'=>array('markup'=>'hidden')
      ),
      7 => array (
            'qgis'=>array('name'=>'Checkbox', 'description'=>'A checkbox with a value for checked state and a value for unchecked state'), 
            'jform'=>array('markup'=>'checkbox')
      ),
      12 => array (
            'qgis'=>array('name'=>'Text edit', 'description'=>'A text edit field that accepts multiple lines will be used'), 
            'jform'=>array('markup'=>'textarea')
      ),
      13 => array (
            'qgis'=>array('name'=>'Calendar', 'description'=>'A calendar widget to enter a date'), 
            'jform'=>array('markup'=>'date')
      ),
      15 => array (
            'qgis'=>array('name'=>'Value relation', 'description'=>'Select layer, key column and value column'), 
            'jform'=>array('markup'=>'menulist')
      ),
      16 => array (
            'qgis'=>array('name'=>'UUID generator', 'description'=>'Read-only field that generates a UUID if empty'), 
            'jform'=>array('markup'=>'input', 'readonly'=>true)
      )
    );
    
    // Table to map arbitrary data types to expected ones
    public $castDataType = array(
      'float'=>'float',
      'real'=>'float',
      'double'=>'float',
      'double decimal'=>'float',
      'numeric'=>'float',
      'int'=>'integer',
      'integer'=>'integer',
      'int4'=>'integer',
      'int8'=>'integer',
      'text'=>'text',
      'string'=>'text',
      'varchar'=>'text',
      'char'=>'text',
      'blob'=>'blob',
      'bytea'=>'blob',
      'geometry'=>'geometry',
      'geometrycollection'=>'geometry',
      'point'=>'geometry',
      'multipoint'=>'geometry',
      'line'=>'geometry',
      'linestring'=>'geometry',
      'multilinestring'=>'geometry',
      'polygon'=>'geometry',
      'multipolygon'=>'geometry',
      'bool'=>'boolean',
      'boolean'=>'boolean',
      'date'=>'date',
      'datetime'=>'datetime'
    );
    



  /**
  * Create an jForms control object based on a qgis edit widget.
  * And add it to the passed form.
  * @param string $ref Name of the control.
  * @param object $edittype simplexml object corresponding to the QGIS edittype for this field.
  * @param object $aliasXml simplexml object corresponding to the QGIS alias for this field.
  * @param object $rendererCategories simplexml object corresponding to the QGIS categories of the renderer.
  * @param object $prop Jelix object with field properties (datatype, required, etc.)
  */
  public function __construct ($ref, $edittype, $aliasXml=Null, $rendererCategories=Null, $prop){
  
    // Set class attributes
    $this->ref = $ref;
    $this->fieldName = $ref;
    if($aliasXml and $aliasXml[0])
      $this->fieldAlias = (string)$aliasXml[0]->attributes()->name;    
    $this->fieldDataType = $this->castDataType[strtolower($prop->type)];
    
    if($prop->notNull && !$prop->autoIncrement)
      $this->required = True;
    
    if($this->fieldDataType != 'geometry'){
      $this->edittype = $edittype;
      $this->rendererCategories = $rendererCategories;

      // Get qgis edittype data
      if($this->edittype)
        $this->fieldEditType = (integer)$this->edittype[0]->attributes()->type;   
      else
        $this->fieldEditType = 0;

      // Get jform control type
      $markup = $this->qgisEdittypeMap[$this->fieldEditType]['jform']['markup'];
    }else{
      $markup='hidden';
    }

    // Create the control
    switch($markup){
    
      case 'input':
        $this->ctrl = new jFormsControlInput($this->ref);
        break;
        
      case 'menulist':
        $this->ctrl = new jFormsControlMenulist($this->ref);
        $this->fillControlDatasource();
        break;
        
      case 'hidden':
        $this->ctrl = new jFormsControlHidden($this->ref);
        break;
        
      case 'checkbox':
        $this->ctrl = new jFormsControlCheckbox($this->ref);
        $this->fillCheckboxValues();
        break;
        
      case 'textarea':
        $this->ctrl = new jFormsControlTextarea($this->ref);
        break;
        
      case 'date':
        $this->ctrl = new jFormsControlDate($this->ref);
        break;
        
      default:
        $this->ctrl = new jFormsControlInput($this->ref);
        break;      
    }
    
    // Set control main properties
    $this->setControlMainProperties();

  }



  /*
  * Create an jForms control object based on a qgis edit widget.
  * @return object Jforms control object
  */
  public function setControlMainProperties(){
    
    // Label
    if($this->fieldAlias)
      $this->ctrl->label = $this->fieldAlias;
    else
      $this->ctrl->label = $this->fieldName;
      
    // Data type
    if(property_exists($this->ctrl, 'datatype')){
      switch($this->fieldDataType){

        case 'text':
          $datatype = new jDatatypeString();
          break;
      
        case 'integer':
          $datatype = new jDatatypeInteger();
          break;
      
        case 'float':
          $datatype = new jDatatypeDecimal();
          break;
      
        case 'date':
          $datatype = new jDatatypeDate();
          break;
      
        case 'datetime':
          $datatype = new jDatatypeDateTime();
          break;
          
        default:
          $datatype = new jDatatypeString();        
      }
      $this->ctrl->datatype = $datatype;  
    }
    
    // Read-only
    if($this->fieldDataType != 'geometry')
      if(array_key_exists('readonly', $this->qgisEdittypeMap[$this->fieldEditType]['jform'] ))
        $this->isReadOnly = True;
        
    // Required
    if( $this->required )
      $this->ctrl->required = True;

  }
  
  
  /*
  * Define checked and unchecked values for a jForms control checkbox, based on Qgis edittype
  * @return object Modified jForms control.
  */
  public function fillCheckboxValues(){
    $checked = (string)$this->edittype[0]->attributes()->checked;
    $unchecked = (string)$this->edittype[0]->attributes()->unchecked;  
    $this->ctrl->valueOnCheck = $checked;
    $this->ctrl->valueOnUncheck = $unchecked;
    $this->required = False; // As there is only a value, even if the checkbox is unchecked
  }
  
  /*
  * Create and populate a datasource for a jForms control based on Qgis edittype
  * @return object Modified jForms control.
  */
  public function fillControlDatasource(){
     
    // Create a datasource for some types : menulist
    $dataSource = new jFormsStaticDatasource();
    
    // Create an array of data specific for the qgis edittype
    $data = array();
    switch($this->fieldEditType){
    
      // Enumeration
      case -1:
        $data[0] = '--qgis edit type not supported yet--';
        break;
    
      // Value map
      case 3:
        foreach($this->edittype[0]->xpath('valuepair') as $valuepair){
          $k = (string)$valuepair->attributes()->key;
          $v = (string)$valuepair->attributes()->value;
          $data[$v] = $k;
        }
        break;
        
      // Classification
      case 4:
        foreach($this->rendererCategories as $category){
          $k = (string)$category->attributes()->label;
          $v = (string)$category->attributes()->value;
          $data[$v] = $k;
        }
        
        break;
        
      // Range
      case 5:
        // Get range of data
        if($this->fieldDataType == 'float'){
          $min = (float)$this->edittype[0]->attributes()->min;
          $max = (float)$this->edittype[0]->attributes()->max;
          $step = (float)$this->edittype[0]->attributes()->step;
        }else{
          $min = (integer)$this->edittype[0]->attributes()->min;
          $max = (integer)$this->edittype[0]->attributes()->max;
          $step = (integer)$this->edittype[0]->attributes()->step;
        }
        $data[(string)$min] = $min;
        for($i = $min; $i <= $max; $i+=$step){
          $data[(string)$i] = $i;
        }
        $data[(string)$max] = $max;
        break;
        
      // Value relation
      case 15:
        $allowNull = (string)$this->edittype[0]->attributes()->allowNull;
        $orderByValue = (string)$this->edittype[0]->attributes()->orderByValue;
        $layer = (string)$this->edittype[0]->attributes()->layer;
        $key = (string)$this->edittype[0]->attributes()->key;
        $value = (string)$this->edittype[0]->attributes()->value;
        $allowMulti = (string)$this->edittype[0]->attributes()->allowMulti;
        $filterExpression = (string)$this->edittype[0]->attributes()->filterExpression;
        $this->valueRelationData = array(
          "allowNull" => $allowNull,
          "orderByValue" => $orderByValue,
          "layer" => $layer,
          "key" => $key,
          "value" => $value,
          "allowMulti" => $allowMulti,
          "filterExpression" => $filterExpression
        );
                
        break;

    }
    
    asort($data);
    $dataSource->data = $data;
    $this->ctrl->datasource = $dataSource;
  }    
  
}
