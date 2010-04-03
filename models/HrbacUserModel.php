<?php

class HrbacUserModel 		//extends CModel
{
	protected $_userid;
	protected $_user;
	protected $_roles;
	
	public function __construct($userid = 0, $user=0, $roles=array())
	{
		$this->_userid = $userid;
		$this->_user = $user;
		$this->_roles = $roles;
		
	}
	
	public function setRoles($roles)
	{
		$this->_roles = $roles;
	}
	
	public function getRoles()
	{
		return $this->_roles;
	}
	
	public function getUserId()
	{
		return $this->_userid;
	}
	
	public function getUserName()
	{
		return $this->_user;
	}
	
	// Get an array of Users (that are objects of HrbacUserModel)
	static function getUsers($count=0, $startFrom=0)
	{
		$limit = '';
		if( $count == 0 && $startFrom > 0 )
			$count = 999999; 
		if( $count > 0 )
			$limit = "LIMIT " . (int)$startFrom . ", " . (int)$count;
		$users = array();
		
		$sql = "SELECT DISTINCT user_id, username
			FROM " . Yii::app()->authManager->assignmentTable . " AS au, " . HrbacModule::$usersTable . " AS u 
			WHERE au.user_id = u.id    
			ORDER BY username 
			{$limit} 
			";
		$userRows = Yii::app()->authManager->db->createCommand($sql)->queryAll();
		foreach($userRows as $userRow)
		{
			$user['userid'] = $userRow['user_id'];
			$user['username'] = $userRow['username'];
			$sql = "SELECT u.auth_id, i.name, i.alt_name, i.description, i.type, u.cond, u.bizrule, u.data 
				FROM " . Yii::app()->authManager->itemTable . " AS i, " . Yii::app()->authManager->assignmentTable . " AS u 
				WHERE i.auth_id = u.auth_id AND u.user_id = {$userRow['user_id']}
				ORDER BY type DESC
				";
			$authRows = Yii::app()->authManager->db->createCommand($sql)->queryAll();
			
			$user['roles'] = $authRows;
			$users[] = $user;
		}
		return $users;
	}
	
	// return all users that have the given auth - directly or indirectly (inherited).
	static function getUsersByAuthId($authid, $count=0, $startFrom=0)
	{
		$limit = '';
		if( $count == 0 && $startFrom > 0 )
			$count = 999999; 
		if( $count > 0 )
			$limit = "LIMIT " . (int)$startFrom . ", " . (int)$count;
		$users = array();
		
		$sql = "SELECT DISTINCT user_id, username
			FROM " . Yii::app()->authManager->assignmentTable . " AS au, " . HrbacModule::$usersTable . " AS u 
			WHERE au.user_id = u.id AND au.auth_id IN (SELECT senior_id FROM " . Yii::app()->authManager->pathTable . " WHERE junior_id=:authid) 
			ORDER BY username 
			{$limit} 
			";
		$userRows = Yii::app()->authManager->db->createCommand($sql)->queryAll(true, array(':authid'=>$authid));
		foreach($userRows as $userRow)
		{
			$user['userid'] = $userRow['user_id'];
			$user['username'] = $userRow['username'];
			$sql = "SELECT u.auth_id, i.name, i.alt_name, i.description, i.type, u.cond, u.bizrule, u.data 
				FROM " . Yii::app()->authManager->itemTable . " AS i, " . Yii::app()->authManager->assignmentTable . " AS u 
				WHERE i.auth_id = u.auth_id AND u.user_id = {$userRow['user_id']}
				ORDER BY type DESC
				";
			$authRows = Yii::app()->authManager->db->createCommand($sql)->queryAll();
			
			$user['roles'] = $authRows;
			$users[] = $user;
		}
		return $users;
	}
	
	static function getAuthsByUserId($userid)
	{
		$sql = "
			SELECT u.auth_id, i.name, i.alt_name, i.description, i.type, u.cond, u.bizrule, u.data 
			FROM " . Yii::app()->authManager->itemTable . " AS i, " . Yii::app()->authManager->assignmentTable . " AS u 
			WHERE i.auth_id = u.auth_id AND u.user_id=:userid
			ORDER BY type DESC
			";
		$authRows = Yii::app()->authManager->db->createCommand($sql)->queryAll(true, array(':userid'=>$userid));
		return $authRows;
	}
	
	// returns direct AND indirect auths.
	static function getAllAuthsByUserId($userid)
	{
		$sql = "
			SELECT DISTINCT i.auth_id, i.name, i.alt_name, i.type 
			FROM " . Yii::app()->authManager->itemTable . " AS i, " 
				.  Yii::app()->authManager->pathTable . " AS p 
			WHERE p.senior_id IN (SELECT auth_id FROM " . Yii::app()->authManager->assignmentTable . " WHERE user_id=:userid)
				AND p.junior_id = i.auth_id
			ORDER BY type DESC";
		$authRows = Yii::app()->authManager->db->createCommand($sql)->queryAll(true, array(':userid'=>$userid));
		return $authRows;
	}
	
	static function getAssignableAuthsByUserId($userid)
	{
		$sql = "
			SELECT auth_id, name, alt_name, type, description  
			FROM " . Yii::app()->authManager->itemTable . "  
			WHERE auth_id NOT IN (SELECT auth_id FROM " . Yii::app()->authManager->assignmentTable . " WHERE user_id=:userid)
			ORDER BY type DESC"; 
		$authRows = Yii::app()->authManager->db->createCommand($sql)->queryAll(true, array(':userid'=>$userid));
		return $authRows;
	}
	
	// Get the count of users with authentication assignments
	static function getUserCount($authid=0)
	{
		if($authid)
		{
			$sql = "SELECT COUNT(DISTINCT user_id)
				FROM " . Yii::app()->authManager->assignmentTable . " AS au 
				WHERE au.auth_id IN (SELECT senior_id FROM " . Yii::app()->authManager->pathTable . " WHERE junior_id=:authid) 
				";
			return Yii::app()->authManager->db->createCommand($sql)->queryScalar(array(':authid'=>$authid));
		}
		else 
		{
			$sql = 'SELECT COUNT(DISTINCT user_id) FROM ' . Yii::app()->authManager->assignmentTable ;
			return Yii::app()->authManager->db->createCommand($sql)->queryScalar();
		}
	}
	
	static function getAuthItem($userid, $auth_id)
	{
		$sql = "
			SELECT au.*, u.username, i.name as authname, i.alt_name, i.type, i.description 
			FROM " . Yii::app()->authManager->assignmentTable . " AS au, ". HrbacModule::$usersTable ." AS u, " . Yii::app()->authManager->itemTable . " AS i 
			WHERE user_id = id AND au.auth_id=i.auth_id AND user_id=:userid AND au.auth_id=:itemid";
		$data = Yii::app()->authManager->db->createCommand($sql)->queryRow(true, array(':userid'=>$userid, ':itemid'=>$auth_id));
		$data['model'] = HrbacItemModel::model()->findByPk($auth_id);
		return $data;
	}
	
	static function updateAuthItem($userid, $auth_id, $cond, $bizrule)
	{
		$sql = "UPDATE " . Yii::app()->authManager->assignmentTable . " SET cond=:cond, bizrule=:bizrule WHERE user_id=:userid AND auth_id=:itemid";
		Yii::app()->authManager->db->createCommand($sql)->execute(array(
			':cond'=>$cond, ':bizrule'=>$bizrule, 
			':userid'=>$userid, ':itemid'=>$auth_id ));
	}
	
	static function removeAuthItem($userid, $auth_id)
	{
			$sql = "DELETE FROM " . Yii::app()->authManager->assignmentTable . " WHERE user_id=:userid AND auth_id=:itemid";
			Yii::app()->authManager->db->createCommand($sql)->execute(array(
				':userid'=>$userid, ':itemid'=>$itemid ));
	}
	
	
	static function assignAuthItem($username, $auth_id, $cond, $bizrule)
	{
		$db = Yii::app()->authManager->db;
		$userid = $db->createCommand("SELECT id FROM ". HrbacModule::$usersTable ." WHERE username=:username")
			->queryScalar(array(':username'=>$username));
		if( !$userid ) 
			return "User '$username' not found";
		
		$authExists = $db->createCommand("SELECT COUNT(*) FROM " . Yii::app()->authManager->itemTable . " WHERE auth_id=:auth_id")
			->queryScalar(array(':auth_id'=>$auth_id)); 
		if( !$authExists )
			return "Auth Item does not exist";
			
		$assignExists = $db->createCommand("SELECT COUNT(*) FROM " . Yii::app()->authManager->assignmentTable . " WHERE user_id=:userid AND auth_id=:auth_id")
			->queryScalar(array(':userid'=>$userid, ':auth_id'=>$auth_id)); 
		if( $assignExists )
			return "Auth Assignment already exists. Please edit existing assignment";
			
		$sql = "INSERT INTO " . Yii::app()->authManager->assignmentTable . " VALUES ( :userid, :auth_id, :cond, :bizrule, NULL)";
		$db->createCommand($sql)->execute(array(
			':cond'=>$cond, ':bizrule'=>$bizrule, 
			':userid'=>$userid, ':auth_id'=>$auth_id ));
		return null;
	}
}
?>