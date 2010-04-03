<?php
/**
 * HrbacItem class file.
 *
 * @author Zeph
 */

class HrbacItem extends CAuthItem
{
	private $_condition;

	public function __construct($auth,$name,$type,$description='',$bizRule=null,$data=null, $condition=null)
	{
		parent::__construct($auth,$name,$type,$description,$bizRule,$data);
		$this->_condition = $condition;
	}

	/**
	 * Checks to see if the specified item is within the hierarchy starting from this item.
	 * This method is internally used by {@link IAuthManager::checkAccess}.
	 * @param string the name of the item to be checked
	 * @param array the parameters to be passed to business rule evaluation
	 * @return boolean whether the specified item is within the hierarchy starting from this item.
	 */
	public function checkAccess($itemName,$params=array(),$paramsCond=null)
	{
		Yii::trace('Checking permission "'.$this->_name.'"','system.web.auth.CAuthItem');
		if($this->_auth->executeBizRule($this->_bizRule,$params,$this->_data)
			&& $this->_auth->checkConditions($this->_condition, $paramsCond))
		{
			if($this->_name==$itemName)
				return true;
			foreach($this->_auth->getItemChildren($this->_name) as $item)
			{
				if($item->checkAccess($itemName,$params))
					return true;
			}
		}
		return false;
	}
	/**
	 * @return string the condition associated with this item
	 */
	public function getCondition()
	{
		return $this->_condition;
	}

	/**
	 * @param string the condition associated with this item
	 */
	public function setCondition($value)
	{
		if($this->_condition!==$value)
		{
			$this->_condition=$value;
			$this->_auth->saveAuthItem($this);
		}
	}

	/**
	 * Assigns this item to a user.
	 * @param mixed the user ID (see {@link IWebUser::getId})
	 * @param string the business rule to be executed when {@link checkAccess} is called
	 * for this particular authorization item.
	 * @param mixed additional data associated with this assignment
	 * @return CAuthAssignment the authorization assignment information.
	 * @throws CException if the item has already been assigned to the user
	 * @see IAuthManager::assign
	 */
	public function assign($userId,$bizRule=null,$data=null,$condition=null)
	{
		return $this->_auth->assign($this->_name,$userId,$bizRule,$data,$condition);
	}
}
