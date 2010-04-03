<?php
//TODO: Add a hash for the bizrule to make sure that it's written by this class. This should somewhat reduce the risk of damage by SQL injection.
/**
 * 
 * @author Zeph
 *
 * Mostly a performance enhancement for CDbAuthManager.
 * Modifies the tables to use numeric index id and adds one new table.
 * Uses 'materialized paths'
 *  
 * Assumptions: 
 * 1) Related to authorization, the db will be queried way more often than written to.
 *    That is, we will not be making a lot of changes to roles etc as compared to the
 *    number of times we read those values.
 * 2) Role id is assumed to be 1 but isn't used.
 * 
 * Requirement:
 * 1) Role 2 must be role of 'anonymous user', Role 3 must be logged it user.
 */

class HrbacManager extends CDbAuthManager
{
	/**
	 * @var bool. Whether to use bizrules or not. Can be turned on any time without loss 
	 * of functionality. Turning it back off will not delete the bizrules but will stop
	 * using them. If false, default roles are ignored.
	 */
	public $usePhpRules=true;
	/**
	 * @var string the name of the table storing authorization items. Defaults to 'AuthItem'.
	 */
	public $itemTable='AuthItem';
	/**
	 * @var string the name of the table storing authorization item hierarchy. Defaults to 'AuthItemChild'.
	 */
	public $itemChildTable='AuthChild';
	/**
	 * @var string the name of the table storing authorization item assignments. Defaults to 'AuthAssignment'.
	 */
	public $assignmentTable='AuthUser';
	/**
	 * @var string the name of the table storing authorization item assignments. Defaults to 'AuthAssignment'.
	 */
	public $pathTable='AuthPath';

	// TODO: Retrieve all permissions for user (if $preFetch == true).
	// If prefetched permissions does not have data on a computed role, 
	// then fetch (from DB) and add data of that computed role to prefetched permissions
	// checkAccess() will have to be changed accordingly.
	// This would make checking for menu items faster (or anytime the app has to do multiple checks
//	public $allowCaching = true;
//	public $preFetch = false;
	
	/**
	 * Returns roles of the current user based on programatic criteria
	 * @return array of integers identifying the roles.
	 */
//	public function getComputedRoleIds()
//	{
//		$roles = array();
//		if(Yii::app()->user->isGuest)
//			return array(2);	//anonymous user
//		else 
//			$roles[] = 3;		//authenticated user
//
//		return $roles;
//	}
	
	public function getComputedRoles()
	{
		$roles = array();
		if( method_exists(Yii::app()->controller, 'getComputedRoles' ) )
			$roles = Yii::app()->controller->getComputedRoles();
		if(Yii::app()->user->isGuest)
			$roles[] = 'Anonymous User';
		else 
			$roles[] = 'Authenticated User';
		return $roles;
	}
	
	/**
	 * Performs access check for the specified user.
	 * @param string the name of the operation that need access check
	 * @param mixed the user ID. This should can be either an integer and a string representing
	 * the unique identifier of a user. See {@link IWebUser::getId}.
	 * @param array name-value pairs that would be passed to biz rules associated
	 * with the tasks and roles assigned to the user.
	 * @return boolean whether the operations can be performed by the user.
	 */
	public function checkAccess($itemName,$userId,$params=array(), $paramsCond=null, $computedRoles=null)
	{
		//WHERE junior_id = (SELECT auth_id FROM {$this->itemTable} WHERE name = " . $this->db->quoteValue($itemName) . ")
		//AND junior_id = (SELECT auth_id FROM {$this->itemTable} where name = :itemname)
		if( $itemName[0] == '/' )
		{
			// In route check
			$juniorCond = "junior_id IN (SELECT auth_id FROM {$this->itemTable} 
				WHERE INSTR(" . $this->db->quoteValue($itemName) . ", name) = 1
					 AND RIGHT(name,1) = '/')";
			$routeCheck = true;
		} 
		else 
		{
			$juniorCond = "junior_id = (SELECT auth_id FROM {$this->itemTable} where name = " . $this->db->quoteValue($itemName) . ")";
			$routeCheck = false;
		}
				
		// Handle default roles
		$roles = array_merge($this->getComputedRoles(), $this->defaultRoles);
		if(is_array($computedRoles)) $roles = array_merge($roles, $computedRoles);
		$names=array();
		foreach($roles as $role)
		{
			if(is_string($role))
				$names[]=$this->db->quoteValue($role);
			else
				$names[]=$role;
		}
		if(count($names)<4)
			$condition='name='.implode(' OR name=',$names);
		else
			$condition='name IN ('.implode(', ',$names).')';
			
		if( !$this->usePhpRules )
		{
			
			$sql = "SELECT 1, auth_id, cond FROM {$this->assignmentTable} WHERE user_id = :userid 
				UNION ALL SELECT 0, senior_id, concat(senior_cond, chained_cond, junior_cond) 
					FROM {$this->pathTable} AS p, {$this->itemTable} AS i 
					WHERE p.senior_id = i.auth_id  
					AND {$juniorCond} 
					AND ( senior_id IN ( SELECT auth_id FROM {$this->assignmentTable} WHERE user_id = :userid) 
						OR $condition) ";
			$command = $this->db->createCommand($sql);
			$rows = $command->queryAll(false,array(':itemname'=>$itemName, ':userid'=>$userId)) ;
			if( count($rows) == 0)
				return false;
			$validRoles = array();
			foreach($rows as $row)
			{
				list($userCheck, $auth, $cond) = $row;
				if($userCheck && self::checkConditions($cond,$paramsCond))
				{
					$validRoles[] = $auth;
				}
				elseif ( in_array($auth, $validRoles) ) 
				{
					if( self::checkConditions($cond,$paramsCond) )
						return true;
				}
			}
			return false;
		}
		

		$sql="SELECT t1.auth_id, t1.name, t1.bizrule, t1.data, t1.cond, t2.bizrule AS bizrule2, t2.data AS data2, t2.cond as cond2 
				FROM {$this->itemTable} t1, {$this->assignmentTable} t2 
				WHERE t1.auth_id=t2.auth_id AND user_id=:userid
			 	UNION ALL 
			 	SELECT auth_id, name, bizrule, data, cond, null, null, '' 
			 	FROM {$this->itemTable} 
			 	WHERE $condition";
		$command=$this->db->createCommand($sql);
		$command->bindValue(':userid',$userId);

		// check directly assigned items
		$auth_ids=array();
		foreach($command->queryAll() as $row)
		{
			Yii::trace('Checking permission "'.$row['name'].'"','system.web.auth.CDbAuthManager');			
			if($this->executeBizRule($row['bizrule'],$params,unserialize($row['data']))
				&& $this->executeBizRule($row['bizrule2'],$params,unserialize($row['data2']))
				&& self::checkConditions($row['cond'], $paramsCond)
				&& self::checkConditions($row['cond2'], $paramsCond) )
			{
				if($row['name']===$itemName)
					return true;
				if($routeCheck && strpos($itemName, $row['name']) === 0)
					return true;
				$auth_ids[]=$row['auth_id'];
			}
		}

		if(!count($auth_ids))
			return false;
		
		// check all descendant items
		// Get the paths first
		$paths = array();
		$pathAuths = array();
		$sql = "SELECT senior_id, junior_id, path FROM {$this->pathTable} 
				WHERE $juniorCond  
					AND senior_id IN (".implode(', ',$auth_ids).") 
					AND distance > 0 
				ORDER BY distance ASC";
		$command=$this->db->createCommand($sql);
		foreach($command->queryAll() as $row)
		{
			$a =  array($row['senior_id']);
			if( ($b = trim($row['path'], ',')) != '' )
				foreach(explode(',', $b) as $c)
					$a[] = $b;
			$a[] = $row['junior_id'];
			$paths[] = $a;
			$pathAuths = array_merge($pathAuths, $a);
		}
		asort($pathAuths);
		$auths = array_unique($pathAuths, SORT_NUMERIC);
		// Now $auths has the IDs of all distinct auth items that may need to be checked to determine access
		// $paths has the different paths that may allow access.
		// We now need to get the bizrules and conditions and see if any 
		// of the paths allows access. 

		if(!count($auths))
			return false;
		
		
		$sql = "SELECT auth_id, bizrule, data, cond FROM {$this->itemTable} 
			WHERE auth_id IN (" . implode(',', $auths) . ") 
			ORDER BY auth_id ASC";
		$ruleData = $this->db->createCommand($sql)->queryAll();
		if( count($auths) != count($ruleData))
		{
			$this->recreateAuthPathTable();
			throw new CException("Table: {$this->authPath} was corrupt. It has now been rebuilt.");
		}
		for($i = 0, $n=count($paths), $pathSkip=array_fill(0, $n, false); $i<$n; $i++)
		{
			if($pathSkip[$i]) continue;
			$pathFailed = false;
			foreach($paths[$i] as $auth_id)
			{
				$rule_index = array_search($auth_id, $auths);
				if(!$this->executeBizRule($ruleData[$rule_index]['bizrule'], $params, $ruleData[$rule_index]['data'])
					&& !self::checkConditions($ruleData[$rule_index]['cond'], $paramsCond))
				{
					$pathFailed = true;
					// Mark other paths that have the same bizrule
					for($j=$i+1; $j<$n; $j++)
					{
						if(in_array($auth_id, $paths[$j]))
							$pathSkip[$j] = true;
					}
					break;
				}
			}
			if(!$pathFailed) return true;
		}
		
		return false;
	}


	/**
	 * Checks the access based on the default roles as declared in {@link defaultRoles}.
	 * @param string the name of the operation that need access check
	 * @param array name-value pairs that would be passed to biz rules associated
	 * with the tasks and roles assigned to the user.
	 * @return boolean whether the operations can be performed by the user according to the default roles.
	 * @since 1.0.3
	 */
	protected function checkDefaultRoles($itemName,$params)
	{
		$names=array();
		foreach($this->defaultRoles as $role)
		{
			if(is_string($role))
				$names[]=$this->db->quoteValue($role);
			else
				$names[]=$role;
		}
		if(count($names)<4)
			$condition='name='.implode(' OR name=',$names);
		else
			$condition='name IN ('.implode(', ',$names).')';
		$sql="SELECT name, type, description, bizrule, data, cond FROM {$this->itemTable} WHERE $condition";
		$command=$this->db->createCommand($sql);
		$rows=$command->queryAll();

		foreach($rows as $row)
		{
			Yii::trace('Checking default role "'.$row['name'].'"','system.web.auth.CDbAuthManager');			
			$item=new CAuthItem($this,$row['name'],$row['type'],$row['description'],$row['bizrule'],unserialize($row['data']),$row['cond']);
			if($item->checkAccess($itemName,$params))
				return true;
		}
		return false;
	}

	/**
	 * Adds an item as a child of another item.
	 * @param string the parent item name
	 * @param string the child item name
	 * @throws CException if either parent or child doesn't exist or if a loop has been detected.
	 */
	public function addItemChild($itemName,$childName)
	{
		if($itemName===$childName)
			throw new CException(Yii::t('yii','Cannot add "{name}" as a child of itself.',
					array('{name}'=>$itemName)));
		$sql="SELECT * FROM {$this->itemTable} WHERE name=:name1 OR name=:name2";
		$command=$this->db->createCommand($sql);
		$command->bindValue(':name1',$itemName);
		$command->bindValue(':name2',$childName);
		$rows=$command->queryAll();
		if(count($rows)==2)
		{
			static $types=array('operation','task','role');
			if($rows[0]['name']===$itemName)
				$parentIndex = 0;
			else 
				$parentIndex = 1;
			$this->checkItemChildType($rows[$parentIndex]['type'],$rows[1-$parentIndex]['type']);
			if($this->detectIdLoop($rows[$parentIndex]['auth_id'],$rows[1-$parentIndex]['auth_id']))
				throw new CException(Yii::t('yii','Cannot add "{child}" as a child of "{name}". A loop has been detected.',
					array('{child}'=>$childName,'{name}'=>$itemName)));

			$sql="INSERT INTO {$this->itemChildTable} VALUES (:parent,:child)";
			$command=$this->db->createCommand($sql);
			$command->bindValue(':parent',$itemName);
			$command->bindValue(':child',$childName);
			$command->execute();
			$this->recreateAuthPathTable();
		}
		else
			throw new CException(Yii::t('yii','Either "{parent}" or "{child}" does not exist.',array('{child}'=>$childName,'{parent}'=>$itemName)));
	}

	/**
	 * Removes a child from its parent.
	 * Note, the child item is not deleted. Only the parent-child relationship is removed.
	 * @param string the parent item name
	 * @param string the child item name
	 * @return boolean whether the removal is successful
	 */
	public function removeItemChild($itemName,$childName)
	{
		$sql="DELETE FROM {$this->itemChildTable} 
			WHERE parent=(SELECT auth_id FROM {$this->itemChildTable} WHERE name=:parent) 
			AND child=(SELECT auth_id FROM {$this->itemChildTable} WHERE name=:child)";
		$command=$this->db->createCommand($sql);
		$command->bindValue(':parent',$itemName);
		$command->bindValue(':child',$childName);
		$retval = $command->execute()>0;
		if($retval) $this->recreateAuthPathTable();
		return $retval;
	}

	/**
	 * Returns a value indicating whether a child exists within a parent.
	 * @param string the parent item name
	 * @param string the child item name
	 * @return boolean whether the child exists
	 */
	public function hasItemChild($itemName,$childName)
	{
		// TODO: Performance: Test if join is faster.
		$sql="SELECT auth_id FROM {$this->itemChildTable} 
			WHERE parent=(SELECT auth_id FROM {$this->itemChildTable} WHERE name=:parent) 
			AND child=(SELECT auth_id FROM {$this->itemChildTable} WHERE name=:child) ";
		$command=$this->db->createCommand($sql);
		$command->bindValue(':parent',$itemName);
		$command->bindValue(':child',$childName);
		return $command->queryScalar()!==false;
	}

	/**
	 * Returns the children of the specified item.
	 * @param mixed the parent item name. This can be either a string or an array.
	 * The latter represents a list of item names (available since version 1.0.5).
	 * @return array all child items of the parent
	 */
	public function getItemChildren($names)
	{
		if(is_string($names))
			$condition='parent='.$this->db->quoteValue($names);
		else if(is_array($names) && $names!==array())
		{
			foreach($names as &$name)
				$name=$this->db->quoteValue($name);
			$condition='p.name IN ('.implode(', ',$names).')';
		}
		$sql="SELECT c.name, c.type, c.description, c.bizrule, c.data c.cond 
			FROM {$this->itemTable} as p, {$this->itemTable} as c, {$this->itemChildTable} as pc 
			WHERE p.auth_id=pc.auth_id AND $condition AND c.auth_id=pc.child_id ";
		$children=array();
		foreach($this->db->createCommand($sql)->queryAll() as $row)
			$children[$row['name']]=new CAuthItem($this,$row['name'],$row['type'],$row['description'],$row['bizrule'],unserialize($row['data']), $row['cond']);
		return $children;
	}

	/**
	 * Assigns an authorization item to a user.
	 * @param string the item name
	 * @param mixed the user ID (see {@link IWebUser::getId})
	 * @param string the business rule to be executed when {@link checkAccess} is called
	 * for this particular authorization item.
	 * @param mixed additional data associated with this assignment
	 * @return CAuthAssignment the authorization assignment information.
	 * @throws CException if the item does not exist or if the item has already been assigned to the user
	 */
	public function assign($itemName,$userId,$bizRule=null,$data=null,$condition=null)
	{
		$auth_id = $this->db->createCommand("SELECT auth_id FROM {$this->itemTable} WHERE name=:itemname")
			->queryScalar(array(':itemname'=>$itemName));
		if(!$auth_id)
			throw new CException(Yii::t('yii','The item "{name}" does not exist.',array('{name}'=>$itemName)));
		$sql="INSERT INTO {$this->assignmentTable} (auth_id,user_id,bizrule,data,cond) VALUES (:auth_id,:userid,:bizrule,:data,:condition) ";
		$command=$this->db->createCommand($sql);
		$command->bindValue(':auth_id',$auth_id);
		$command->bindValue(':userid',$userId);
		$command->bindValue(':bizrule',$bizRule);
		$command->bindValue(':data',serialize($data));
		$command->bindValue(':condition',$condition);
		$command->execute();
		return new CAuthAssignment($this,$itemName,$userId,$bizRule,$data,$condition);
	}

	/**
	 * Revokes an authorization assignment from a user.
	 * @param string the item name
	 * @param mixed the user ID (see {@link IWebUser::getId})
	 * @return boolean whether removal is successful
	 */
	public function revoke($itemName,$userId)
	{
		$sql="DELETE FROM {$this->assignmentTable} WHERE auth_id=(SELECT auth_id FROM {$this->itemTable} WHERE name=:itemname) AND user_id=:userid ";
		$command=$this->db->createCommand($sql);
		$command->bindValue(':itemname',$itemName);
		$command->bindValue(':userid',$userId);
		return $command->execute()>0;
	}

	/**
	 * Returns a value indicating whether the item has been assigned to the user.
	 * @param string the item name
	 * @param mixed the user ID (see {@link IWebUser::getId})
	 * @return boolean whether the item has been assigned to the user.
	 */
	public function isAssigned($itemName,$userId)
	{
		$sql="SELECT COUNT(*) FROM {$this->assignmentTable} AS a, {$this->itemTable} AS b 
			WHERE a.auth_id = b.auth_id AND name=:itemname AND user_id=:userid ";
		$command=$this->db->createCommand($sql);
		$command->bindValue(':itemname',$itemName);
		$command->bindValue(':userid',$userId);
		return $command->queryScalar()!==false;
	}

	/**
	 * Returns the item assignment information.
	 * @param string the item name
	 * @param mixed the user ID (see {@link IWebUser::getId})
	 * @return CAuthAssignment the item assignment information. Null is returned if
	 * the item is not assigned to the user.
	 */
	public function getAuthAssignment($itemName,$userId)
	{
		$sql="SELECT b.name, a.user_id, a.bizrule, a.data, a.cond FROM {$this->assignmentTable} AS a, {$this->itemTable} AS b 
			WHERE a.auth_id = b.auth_id AND name=:itemname AND user_id=:userid ";
		$command=$this->db->createCommand($sql);
		$command->bindValue(':itemname',$itemName);
		$command->bindValue(':userid',$userId);
		if(($row=$command->queryRow($sql))!==false)
			return new CAuthAssignment($this,$row['name'],$row['user_id'],$row['bizrule'],unserialize($row['data']),$row['cond']);
		else
			return null;
	}

	/**
	 * Returns the item assignments for the specified user.
	 * @param mixed the user ID (see {@link IWebUser::getId})
	 * @return array the item assignment information for the user. An empty array will be
	 * returned if there is no item assigned to the user.
	 */
	public function getAuthAssignments($userId)
	{
		$sql="SELECT b.name, a.user_id, a.bizrule, a.data, a.cond FROM {$this->assignmentTable} AS a, {$this->itemTable} AS b 
			WHERE a.auth_id = b.auth_id AND user_id=:userid ";
		$command=$this->db->createCommand($sql);
		$command->bindValue(':userid',$userId);
		$assignments=array();
		foreach($command->queryAll($sql) as $row)
			$assignments[$row['itemname']]=new CAuthAssignment($this,$row['name'],$row['user_id'],$row['bizrule'],unserialize($row['data']),$row['cond']);
		return $assignments;
	}

	/**
	 * Saves the changes to an authorization assignment.
	 * @param CAuthAssignment the assignment that has been changed.
	 */
	public function saveAuthAssignment($assignment)
	{
		$sql="UPDATE {$this->assignmentTable} SET bizrule=:bizrule, data=:data, cond=:condition 
			WHERE itemname=(SELECT auth_id FROM {$this->itemTable} WHERE name=:itemname) AND user_id=:userid ";
		$command=$this->db->createCommand($sql);
		$data = $assignment->getData();
		$command->bindValue(':bizrule',$assignment->getBizRule());
		$command->bindValue(':data',empty($data) ? 'NULL' : serialize($assignment->getData()));
		$command->bindValue(':itemname',$assignment->getItemName());
		$command->bindValue(':userid',$assignment->getUserId());
		$command->bindValue(':condition',$assignment->getCondition());
		$command->execute();
	}

	/**
	 * Returns the authorization items of the specific type and user.
	 * @param integer the item type (0: operation, 1: task, 2: role). Defaults to null,
	 * meaning returning all items regardless of their type.
	 * @param mixed the user ID. Defaults to null, meaning returning all items even if
	 * they are not assigned to a user.
	 * @return array the authorization items of the specific type.
	 */
	public function getAuthItems($type=null,$userId=null)
	{
		if($type===null && $userId===null)
		{
			$sql="SELECT * FROM {$this->itemTable} ";
			$command=$this->db->createCommand($sql);
		}
		else if($userId===null)
		{
			$sql="SELECT * FROM {$this->itemTable} WHERE type=:type ";
			$command=$this->db->createCommand($sql);
			$command->bindValue(':type',$type);
		}
		else if($type===null)
		{
			$sql="SELECT name,type,description,u.bizrule,u.data,u.cond
				FROM {$this->itemTable} i, {$this->assignmentTable} u
				WHERE i.auth_id=u.auth_id AND user_id=:userid";
			$command=$this->db->createCommand($sql);
			$command->bindValue(':userid',$userId);
		}
		else
		{
			$sql="SELECT name,type,description,i.bizrule,i.data,i.cond
				FROM {$this->itemTable} i, {$this->assignmentTable} u
				WHERE i.auth_id=u.auth_id AND type=:type AND user_id=:userid";
			$command=$this->db->createCommand($sql);
			$command->bindValue(':type',$type);
			$command->bindValue(':userid',$userId);
		}
		$items=array();
		foreach($command->queryAll() as $row)
			$items[$row['name']]=new CAuthItem($this,$row['name'],$row['type'],$row['description'],$row['bizrule'],unserialize($row['data']),$row['cond']);
		return $items;
	}

	/**
	 * Creates an authorization item.
	 * An authorization item represents an action permission (e.g. creating a post).
	 * It has three types: operation, task and role.
	 * Authorization items form a hierarchy. Higher level items inheirt permissions representing
	 * by lower level items.
	 * @param string the item name. This must be a unique identifier.
	 * @param integer the item type (0: operation, 1: task, 2: role).
	 * @param string description of the item
	 * @param string business rule associated with the item. This is a piece of
	 * PHP code that will be executed when {@link checkAccess} is called for the item.
	 * @param mixed additional data associated with the item.
	 * @param string condition that must be satisfied. See @link checkCondition()
	 * @return CAuthItem the authorization item
	 * @throws CException if an item with the same name already exists
	 */
	public function createAuthItem($name,$type,$description='',$bizRule=null,$data=null,$condition=null)
	{
		$sql="INSERT INTO {$this->itemTable} (name,type,description,bizrule,data,cond) VALUES (:name,:type,:description,:bizrule,:data,:condition)";
		$command=$this->db->createCommand($sql);
		$command->bindValue(':type',$type);
		$command->bindValue(':name',$name);
		$command->bindValue(':description',$description);
		$command->bindValue(':bizrule',$bizRule);
		$command->bindValue(':data',serialize($data));
		$command->bindValue(':condition',$condition);
		$command->execute();
		$this->recreateAuthPathTable();
		return new CAuthItem($this,$name,$type,$description,$bizRule,$data,$condition);
	}

	/**
	 * Removes the specified authorization item.
	 * @param string the name of the item to be removed
	 * @return boolean whether the item exists in the storage and has been removed
	 */
	public function removeAuthItem($name)
	{
		if($this->usingSqlite())
		{
			$auth_id = $this->db
				->createCommand("SELECT auth_id FROM {$this->itemTable} WHERE name=:name")
				->queryScalar(array(':name'=>$name));
			$sql="DELETE FROM {$this->itemChildTable} WHERE auth_id=:name1 OR child_id=:name2";
			$command=$this->db->createCommand($sql);
			$command->bindValue(':name1',$auth_id);
			$command->bindValue(':name2',$auth_id);
			$command->execute();

			$sql="DELETE FROM {$this->assignmentTable} WHERE name=:name";
			$command=$this->db->createCommand($sql);
			$command->bindValue(':name',$name);
			$command->execute();
		}

		$sql="DELETE FROM {$this->itemTable} WHERE name=:name";
		$command=$this->db->createCommand($sql);
		$command->bindValue(':name',$name);

		$retval = $command->execute()>0;
		$this->recreateAuthPathTable();			//TODO: Delete only relevant rows instead.
		return $retval;
	}

	/**
	 * Returns the authorization item with the specified name.
	 * @param string the name of the item
	 * @return CAuthItem the authorization item. Null if the item cannot be found.
	 */
	public function getAuthItem($name)
	{
		$sql="SELECT * FROM {$this->itemTable} WHERE name=:name";
		$command=$this->db->createCommand($sql);
		$command->bindValue(':name',$name);
		if(($row=$command->queryRow())!==false)
			return new CAuthItem($this,$row['name'],$row['type'],$row['description'],$row['bizrule'],unserialize($row['data']),$row['cond']);
		else
			return null;
	}

	/**
	 * Saves an authorization item to persistent storage.
	 * @param CAuthItem the item to be saved.
	 * @param string the old item name. If null, it means the item name is not changed.
	 */
	public function saveAuthItem($item,$oldName=null)
	{
		$sql="UPDATE {$this->itemTable} SET name=:newName, type=:type, description=:description, bizrule=:bizrule, data=:data, cond=:condition WHERE name=:name";
		$command=$this->db->createCommand($sql);
		$command->bindValue(':type',$item->getType());
		$command->bindValue(':name',$oldName===null?$item->getName():$oldName);
		$command->bindValue(':newName',$item->getName());
		$command->bindValue(':description',$item->getDescription());
		$command->bindValue(':bizrule',$item->getBizRule());
		$command->bindValue(':data',serialize($item->getData()));
		$command->bindValue(':condition',$item->getCondition());
		$command->execute();
	}

	/**
	 * Removes all authorization data.
	 */
	public function clearAll()
	{
		parent::clearAll();
		$this->db->createCommand("DELETE FROM {$this->pathTable}")->execute();
	}

	/**
	 * Checks whether there is a loop in the authorization item hierarchy.
	 * @param parent item id
	 * @param child item id that is to be added to the hierarchy
	 * @return boolean whether a loop exists
	 */
	protected function detectIdLoop($itemId,$childId)
	{
		if($childId===$itemId)
			return true;
			
		$sql = "SELECT COUNT(*) FROM {$this->pathTable} WHERE auth_id = $childId AND child_id = $itemId";
		if( $this->db->createCommand($sql)->queryScalar() > 0 ) return true;
		return false;
	}

	public function recreateAuthPathTable()
	{
		// Clear table - truncate
		$sql = "TRUNCATE TABLE `". $this->pathTable ."`";
		$this->db->createCommand($sql)->execute();

		// Distance = 0 ; junior & senior are same entity
		$sql = "INSERT INTO {$this->pathTable} 
			SELECT auth_id, auth_id, 0, '', cond, cond, '' 
			FROM {$this->itemTable}";
		$this->db->createCommand($sql)->execute();
		
		// Distance = 1 ; e.i. junior is direct child of senior
		$sql = "INSERT INTO {$this->pathTable} 
					SELECT pc.auth_id, child_id, 1, ',', p.cond, c.cond, concat(';', pc.cond, ';')  
					FROM {$this->itemChildTable} AS pc, {$this->itemTable} AS p, {$this->itemTable} AS c 
					WHERE pc.auth_id = p.auth_id AND pc.child_id = c.auth_id" ;
		$this->db->createCommand($sql)->execute();
		
		// For DB2, Oracle, PostgreSQL use || to concat
		// For SQL Server use +
		// ToDo for other database servers.
		$dbserver = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);
		switch( $dbserver )
		{
			case 'mysql':
				$concat1 = 'concat(a.path, a.junior_id, b.path)';
				$concat2 = 'concat(a.chained_cond, a.junior_cond, b.chained_cond)';
				break;
			case 'DB2':
			case 'OCI':
			case 'mssql':
			default:
				throw new CException('HrbacManagerUtil::updateAuthPath has only been implemented for mysql so far');
		}

		// Distance > 1; junior in not direct child but a descendent 
		$sql = "INSERT INTO {$this->pathTable} 
			SELECT a.senior_id, b.junior_id, a.distance+1, $concat1, a.senior_cond, b.junior_cond, $concat2 
			FROM {$this->pathTable} as a, {$this->pathTable} as b 
			WHERE a.junior_id = b.senior_id and a.distance = :distance and b.distance > 0";
		$dbcommand = $this->db->createCommand($sql);
		for($distance=1, $dbcommand->bindValue(':distance',$distance)
			; $dbcommand->execute()
			; $dbcommand->bindValue(':distance',++$distance) ) 
		{}
	}
	
	/**
	 * Check conditions specified in an authItem
	 * 
	 * Conditions are separated by a semi-colon (;). 
	 * Each condition begins with an identifier followed by an operator which may or maynot have space(s) 
	 * adjoining it, followed by a string which may contain spaces or it might be a numeric value.
	 * 
	 * The identifier represents a key in the $_REQUEST array or a key in the supplied array $condParams.
	 * 
	 * Operators: ( value in cond [op] value in _REQUEST or $condParams. )
	 * ==	Check for equality. Uses PHP == operator.
	 * !=	Inequality
	 * <	Less than
	 * >	Greater than
	 * <=	Less than or equal
	 * >=	Greater than or equal
	 * >>	condition value contained in
	 * <<	condition value contains
	 * ~>>	Like >> but uses regex
	 * ~<<	Like << but uses regex
	 * !	does not exist
	 * .	exists
	 * &==	both sides are keys (in the same array) and their values should be equal
	 * &!=	both sides are keys and their values should be inequal
	 * &<, &>, &<=, &>=, &>>, &<<, &~>>, &~>>		Like previous two
	 * 
	 * 
	 * For example: If $_REQUEST['ID'] == 3 and the conditions is 'ID < 10'
	 * then the condition matches and the function returns true.
	 * 
	 * @param string $cond
	 * @param array $condParams
	 * 
	 * @return boolean True if the condition matches.
	 */
	//TODO: Remove this once new version working properly
	public static function checkConditions_v1($condStr, $paramsCond)
	{
		// Return true if no condition specified.
		if( trim($condStr, '; ') === "") 
			return true;
		if(!is_null($paramsCond) && !is_array($paramsCond))
			throw new CException('Second arg to checkConditions should be null or an Array');
		$checkAgainst = $paramsCond !== null ? $paramsCond : $_REQUEST;
		$conditions = split(';', $condStr);
		foreach ($conditions as $cond)
		{
			$components = preg_split("/([=!><& ]+)/", $cond, 2, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
			if( count($components) == 2 )
			{
				$op = trim($components[1]);
				if( $op != '!' && $op != '.')
					throw new CException('Condition not specified properly');
			}
			elseif( count($components) == 3 )
			{
				$op = trim($components[1]);
				if( $op[0] === '&' )
				{
					$op = substr($op, 1);
					if(!isset($checkAgainst[$components[2]]))
						return false;
					$condVal = $checkAgainst[$components[2]];
				}
				else 
					$condVal = $components[2];
			}
			else
			{
				throw new CException("Condition '$cond' not formatted properly");
			}
			
			$key = $components[0]; 
			
			// if key does not exist in $checkAgainst, return false;
			if(!isset($checkAgainst[$key]))
				return false;
			$argVal = $checkAgainst[$key];
			
			switch($op)
			{
				case '==':
					if( $argVal != $condVal ) return false;
					break;
				case '!=':
					if( $argVal == $condVal ) return false;
					break;
				case '<':
					if( $argVal >= $condVal ) return false;
					break;
				case '>':
					if( $argVal <= $condVal ) return false;
					break;
				case '<=':
					if( $argVal > $condVal ) return false;
					break;
				case '>=':
					if( $argVal < $condVal ) return false;
					break;
				case '>>':
					if( strpos($argVal, $condVal) === false ) return false;
					break;
				case '<<':
					if( strpos($condVal, $argVal) === false ) return false;
					break;
				case '~>>':
					if( preg_match($condVal, $argVal) === 0 ) return false;
					break;
				case '~<<':
					if( preg_match($argVal, $condVal) === 0 ) return false;
						break;
				case '!':
					if( array_key_exists($condVal, $checkAgainst ) ) return false;
					break;
				case '.':
					if( !array_key_exists($condVal, $checkAgainst ) ) return false;
					break;
					
				default:
					throw new CException('Operator specified in the condition not proper.');
			}
		}
		return true;
	}
	/**
	 * Check conditions specified in an authItem
	 * 
	 * Conditions are separated by && or ||
	 * Conditions are evaluated left to right and && has lower precedence than || 
	 * unless the very first condition begins with ||
	 * && -> and
	 * || -> or
	 * 
	 * For example:
	 * cond1 && cond2 || cond3 && cond4    is evaluated like:  cond1 && ( cond2 || cond3 ) && cond4
	 * ||cond1 && cond2 || cond3 && cond4    is evaluated like:  (cond1 && cond2) || (cond3 && cond4)
	 * 
	 * Each condition begins with an identifier followed by an operator which may or maynot have space(s) 
	 * adjoining it, followed by a string which may contain spaces or it might be a numeric value.
	 * 
	 * Conditions may not contain the semi-colon (;)
	 * 
	 * So a condition is one of the following forms
	 * key op value		// equiv to expr: $_REQUEST[key] op value
	 * key &op key2		// equir to expr: $_REQUEST[key] op $_REQUEST[key2]
	 * 
	 * Where key is an associative index in the $_REQUEST array or in the supplied array $condParams.
	 * 
	 * Operators: ( value in cond [op] value in _REQUEST or $condParams. )
	 * ==	Check for equality. Uses PHP == operator.
	 * !=	Inequality
	 * <	Less than
	 * >	Greater than
	 * <=	Less than or equal
	 * >=	Greater than or equal
	 * >>	condition value contained in
	 * <<	condition value contains
	 * ~>>	Like >> but uses regex
	 * ~<<	Like << but uses regex
	 * !	does not exist
	 * .	exists
	 * &==	both sides are keys (in the same array) and their values should be equal
	 * &!=	both sides are keys and their values should be inequal
	 * &<, &>, &<=, &>=, &>>, &<<, &~>>, &~>>		Like previous two
	 * 
	 * 
	 * For example: If $_REQUEST['ID'] == 3 and the conditions is 'ID < 10'
	 * then the condition matches and the function returns true.
	 * 
	 * @param string $cond
	 * @param array $condParams
	 * 
	 * @return boolean True if the condition matches.
	 */
	
	public static function checkConditions($condStr, $paramsCond)
	{
		// Return true if no condition specified.
		if( trim($condStr, '; ') === "") 
			return true;
		if(!is_null($paramsCond) && !is_array($paramsCond))
			throw new CException('Second arg to checkConditions should be null or an Array');
		$refArray = $paramsCond !== null ? $paramsCond : $_REQUEST;
		$condGroups = explode(';', $condStr);
		foreach($condGroups as $group)
		{
			if( !self::_checkGroup($group, $refArray) ) return false;
		}
		return true;
	}
	
	private static function _checkGroup($group, $refArray)
	{
		if($group == '') return true;
		if( strpos($group, '||') == 0 )
		{
			$group = substr($group, 2);
			$conditions = explode('||', $group);
			$all = false;
		}
		else 
		{
			$conditions = explode('&&', $group);
			$all = true;
		}

		$retval = true;
		foreach( $conditions as $condition )
		{
			$ret = self::_checkSubGroup($condition, $refArray, !$all);
			if($all && !$ret) return false;
			if(!$all && $ret) return true;
			if(!$all && !$ret) $retval = false;
		}
		return $retval;
	}
	
	private static function _checkSubGroup($group, $refArray, $all)
	{
		$conditions = explode($all ? '&&' : '||', $group);
		
		$retval = true;
		foreach( $conditions as $condition )
		{
			$ret = self::_checkSingleCondition($condition, $refArray);
			if($all && !$ret) return false;
			if(!$all && $ret) return true;
			if(!$all && !$ret) $retval = false;
		}
		return $retval;
	}

	private static function _checkSingleCondition($cond, $refArray)
	{
		if($cond == '') return true;
		$components = preg_split("/([=!><& ]+)/", $cond, 2, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
		if( count($components) == 2 )
		{
			$op = trim($components[1]);
			if( $op != '!' && $op != '.')
				throw new CException('Condition not specified properly');
		}
		elseif( count($components) == 3 )
		{
			$op = trim($components[1]);
			if( $op[0] === '&' )
			{
				$op = substr($op, 1);
				if(!isset($refArray[$components[2]]))
					return false;
				$condVal = $refArray[$components[2]];
			}
			else 
				$condVal = $components[2];
		}
		else
		{
			throw new CException("Condition '$cond' not formatted properly");
		}
		
		$key = $components[0]; 
		
		// if key does not exist in $refArray, return false;
		if(!isset($refArray[$key]))
			return false;
		$argVal = $refArray[$key];
		
		switch($op)
		{
			case '==':
				if( $argVal != $condVal ) return false;
				break;
			case '!=':
				if( $argVal == $condVal ) return false;
				break;
			case '<':
				if( $argVal >= $condVal ) return false;
				break;
			case '>':
				if( $argVal <= $condVal ) return false;
				break;
			case '<=':
				if( $argVal > $condVal ) return false;
				break;
			case '>=':
				if( $argVal < $condVal ) return false;
				break;
			case '>>':
				if( strpos($argVal, $condVal) === false ) return false;
				break;
			case '<<':
				if( strpos($condVal, $argVal) === false ) return false;
				break;
			case '~>>':
				if( preg_match($condVal, $argVal) === 0 ) return false;
				break;
			case '~<<':
				if( preg_match($argVal, $condVal) === 0 ) return false;
					break;
			case '!':
				if( array_key_exists($condVal, $refArray ) ) return false;
				break;
			case '.':
				if( !array_key_exists($condVal, $refArray ) ) return false;
				break;
				
			default:
				throw new CException('Operator specified in the condition not proper.');
		}
		return true;
	}
	
}
