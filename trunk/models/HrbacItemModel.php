<?php

class HrbacItemModel extends CActiveRecord
{
	/**
	 * The followings are the available columns in table 'AuthItem':
	 * @var integer $auth_id
	 * @var string $name
	 * @var string $alt_name
	 * @var integer $type
	 * @var string $description
	 * @var string $cond
	 * @var string $bizrule
	 * @var string $data
	 */
	private $_authPath;
	private $_authItem;
	private $_authUser;
	private $_authChild;

	public function __construct($scenario='insert')
	{
		$this->_authPath = Yii::app()->authManager->pathTable;
		$this->_authItem = Yii::app()->authManager->itemTable;
		$this->_authUser = Yii::app()->authManager->assignmentTable;
		$this->_authChild = Yii::app()->authManager->itemChildTable;
		parent::__construct($scenario);
	}
	/**
	 * Returns the static model of the specified AR class.
	 * @return CActiveRecord the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}

	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return Yii::app()->authManager->itemTable;
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('name, type', 'required'),
			array('type', 'numerical', 'integerOnly'=>true),
			array('name, alt_name', 'length', 'max'=>64),
			array('cond', 'length', 'max'=>250),
			array('description, bizrule, data', 'safe'),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('auth_id, name, alt_name, type, description, cond, bizrule, data', 'safe', 'on'=>'search'),
		);
	}

	/**
	 * @return array relational rules.
	 */
	public function relations()
	{
		// NOTE: you may need to adjust the relation name and the related
		// class name for the relations automatically generated below.
		return array(
			'authChildren' => array(self::MANY_MANY, 'HrbacItemModel', $this->_authChild . '(auth_id, child_id)'
				, 'alias'=>$this->_authItem),
			'authUsers' => array(self::HAS_MANY, $this->_authUser, 'auth_id'),
		);
//		return array(
//			'authChildren' => array(self::MANY_MANY, 'HrbacItemModel', 'AuthChild(auth_id, child_id)', 'alias'=>'AuthItem'),
//			'authUsers' => array(self::HAS_MANY, $this->_authUser, 'auth_id'),
//		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'auth_id' => 'Auth',
			'name' => 'Name or Route',
			'alt_name' => 'Alternate Name',
			'type' => 'Type',
			'description' => 'Description',
			'cond' => 'Condition',
			'bizrule' => 'Php Rule',
			'data' => 'Php Data',
		);
	}

	public function getAuthDescendants($startGen = 1)
	{
		return self::model()->findAllBySql(
			"SELECT DISTINCT i.* 
			FROM {$this->_authItem} i, {$this->_authPath} p 
			WHERE senior_id={$this['auth_id']} AND auth_id=junior_id AND distance >= {$startGen}");
	}
	
	public function getAuthAncestors($startGen = 1)
	{
		return self::model()->findAllBySql(
			"SELECT DISTINCT i.* 
			FROM {$this->_authItem} i, {$this->_authPath} p 
			WHERE junior_id={$this['auth_id']} AND auth_id=senior_id AND distance >= {$startGen}");
	}
	
	public function getAuthParents()
	{
		return self::model()->findAllBySql(
			"SELECT i.* 
			FROM {$this->_authItem} i, {$this->_authChild} c 
			WHERE child_id={$this['auth_id']} AND i.auth_id=c.auth_id");
	}
	
	public function getPotentialDescendants()
	{
		if($this['type'] == 0) return array();
		
		$sql = "
			SELECT auth_id, name, alt_name, type, description, 
				(SELECT cond FROM {$this->_authChild} WHERE auth_id={$this['auth_id']} and child_id=i.auth_id) AS cond,
				auth_id in (SELECT child_id FROM {$this->_authChild} WHERE auth_id={$this['auth_id']}) as ischild, 
				auth_id in (SELECT junior_id FROM {$this->_authPath} WHERE senior_id={$this['auth_id']} AND distance > 1) as isdescendant
			FROM {$this->_authItem} AS i
			WHERE type <= {$this['type']}
				AND auth_id NOT IN ( SELECT senior_id FROM {$this->_authPath} WHERE junior_id={$this['auth_id']} )
			ORDER BY type DESC";
		return self::$db->createCommand($sql)->queryAll();
	}
	
	/**
	 * Replace all children of the current auth item with the given list.
	 * @param array $idArray
	 */
	public function replaceChildren($idArray, $conditions)
	{
		if($idArray === array() ) 
		{
			self::$db->createCommand("DELETE FROM {$this->_authChild} WHERE auth_id = " . $this->auth_id)->execute();
			return;
		}
		
		// First check for loop
		$sql = "SELECT senior_id FROM {$this->_authPath} WHERE junior_id = " . $this->auth_id;
		$ineligible = self::$db->createCommand($sql)->queryAll();
		foreach($idArray as $id)
		{
			if( array_search($id, $ineligible) === true )
				throw new CException(Yii::t('auth', "Adding item:$id to item:{$this->auth_id} would cause a loop"));
		}
		
		// Now check for existence
		$sql = "SELECT count(*) FROM {$this->_authItem} WHERE auth_id IN (" . implode(",", $idArray) . ")";
		if( self::$db->createCommand($sql)->queryScalar() != count($idArray) )
			throw new CException(Yii::t('auth', "One of the following Auth Items is missing : ") . implode(", ", $idArray));
		
		self::$db->createCommand("DELETE FROM {$this->_authChild} WHERE auth_id = " . $this->auth_id)->execute();
		
		$pairs = array();
		foreach($idArray as $id) $values[] = "(" . $this->auth_id . ", " . $id . ", " . self::$db->quotevalue($conditions[$id]) . ")";
		$sql = "INSERT INTO {$this->_authChild} (auth_id, child_id, cond) VALUES " . implode(", ", $values);
		self::$db->createCommand($sql)->execute();
		Yii::app()->authManager->recreateAuthPathTable();
	}
	
	/**
	 * Retrieves a list of models based on the current search/filter conditions.
	 * @return CActiveDataProvider the data provider that can return the models based on the search/filter conditions.
	 */
	public function search()
	{
		// Warning: Please modify the following code to remove attributes that
		// should not be searched.

		$criteria=new CDbCriteria;

		$criteria->compare('name',$this->name,true);

		$criteria->compare('alt_name',$this->alt_name,true);

		$criteria->compare('type',$this->type);

		$criteria->compare('description',$this->description,true);

		$criteria->compare('cond',$this->cond,true);

		$criteria->compare('bizrule',$this->bizrule,true);

		$criteria->compare('data',$this->data,true);

		return new CActiveDataProvider('HrbacItemModel', array(
			'criteria'=>$criteria,
		));
	}
	
	public function delete()
	{
		$retval = parent::delete();
		Yii::app()->authManager->recreateAuthPathTable();
		return $retval;
	}
	
	public function save($runValidation=true,$attributes=null)
	{
		$retval = parent::save($runValidation, $attributes);
		Yii::app()->authManager->recreateAuthPathTable();
		return $retval;
	}
}