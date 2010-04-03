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
		return 'AuthItem';
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
			'authChildren' => array(self::MANY_MANY, 'HrbacItemModel', 'AuthChild(auth_id, child_id)', 'alias'=>'AuthItem'),
			'authUsers' => array(self::HAS_MANY, 'AuthUser', 'auth_id'),
		);
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
			"SELECT DISTINCT AuthItem.* 
			FROM AuthItem, AuthPath 
			WHERE senior_id={$this['auth_id']} AND auth_id=junior_id AND distance >= {$startGen}");
	}
	
	public function getAuthAncestors($startGen = 1)
	{
		return self::model()->findAllBySql(
			"SELECT DISTINCT AuthItem.* 
			FROM AuthItem, AuthPath 
			WHERE junior_id={$this['auth_id']} AND auth_id=senior_id AND distance >= {$startGen}");
	}
	
	public function getAuthParents()
	{
		return self::model()->findAllBySql(
			"SELECT i.* 
			FROM AuthItem AS i, AuthChild AS c 
			WHERE child_id={$this['auth_id']} AND i.auth_id=c.auth_id");
	}
	
	public function getPotentialDescendants()
	{
		if($this['type'] == 0) return array();
		
		$sql = "
			SELECT auth_id, name, alt_name, type, description, 
				auth_id in (SELECT child_id FROM AuthChild WHERE auth_id={$this['auth_id']}) as ischild, 
				auth_id in (SELECT junior_id FROM AuthPath WHERE senior_id={$this['auth_id']} AND distance > 1) as isdescendant
			FROM AuthItem
			WHERE type <= {$this['type']}
				AND auth_id NOT IN ( SELECT senior_id FROM AuthPath WHERE junior_id={$this['auth_id']} )
			ORDER BY type DESC";
		return self::$db->createCommand($sql)->queryAll();
	}
	
	/**
	 * Replace all children of the current auth item with the given list.
	 * @param array $idArray
	 */
	public function replaceChildren($idArray)
	{
		if($idArray === array() ) 
		{
			self::$db->createCommand("DELETE FROM AuthChild WHERE auth_id = " . $this->auth_id)->execute();
			return;
		}
		
		// First check for loop
		$sql = "SELECT senior_id FROM AuthPath WHERE junior_id = " . $this->auth_id;
		$ineligible = self::$db->createCommand($sql)->queryAll();
		foreach($idArray as $id)
		{
			if( array_search($id, $ineligible) === true )
				throw new CException(Yii::t('auth', "Adding item:$id to item:{$this->auth_id} would cause a loop"));
		}
		
		// Now check for existence
		$sql = "SELECT count(*) FROM AuthItem WHERE auth_id IN (" . implode(",", $idArray) . ")";
		if( self::$db->createCommand($sql)->queryScalar() != count($idArray) )
			throw new CException(Yii::t('auth', "One of the following Auth Item is missing : ") . implode(", ", $idArray));
		
		self::$db->createCommand("DELETE FROM AuthChild WHERE auth_id = " . $this->auth_id)->execute();
		
		$pairs = array();
		foreach($idArray as $id) $pairs[] = "(" . $this->auth_id . ", " . $id . ")";
		$sql = "INSERT INTO AuthChild (auth_id, child_id) VALUES " . implode(", ", $pairs);
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