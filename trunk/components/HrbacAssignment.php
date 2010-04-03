<?php
/**
 * HrbacAssignment class file.
 *
 * @author Zeph
 */

class HrbacAssignment extends CAuthAssignment
{
	private $_condition;

	public function __construct($auth,$itemName,$userId,$bizRule=null,$data=null,$condition=null)
	{
		parent::__construct($auth,$itemName,$userId,$bizRule,$data);
		$this->_condition=$condition;
	}

	/**
	 * @return string condition for this assignment
	 */
	public function getCondition()
	{
		return $this->_condition;
	}

	/**
	 * @param string condition for this assignment
	 */
	public function setCondition($value)
	{
		if($this->_condition!==$value)
		{
			$this->_condition=$value;
			$this->_auth->saveAuthAssignment($this);
		}
	}
}