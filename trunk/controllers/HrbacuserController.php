<?php
class HrbacuserController extends Controller
{
	public function beforeAction($action) 
	{
		$this->layout = Yii::app()->controller->module->layout;
		return true;
	}
	
	public function actionList()
	{
		$data['authid'] = 0;
		if( isset($_GET['authid'])) $data['authid'] = (int)$_GET['authid'];
		$userCount = HrbacUserModel::getUserCount($data['authid']);
		$pageSize = 20;		//TODO: Make this configurable or provide UI
		$data['widget'] = $this->createWidget('CLinkPager', array('itemCount'=>$userCount, 'pageSize'=>$pageSize));
		$currentPage = $data['widget']->getCurrentPage();
		if( $data['authid'] )
		{
			$data['users'] = HrbacUserModel::getUsersByAuthId($data['authid'], $pageSize, $pageSize * $currentPage);
			$data['authitem'] = HrbacItemModel::model()->findByPk($data['authid']);
		}
		else
			$data['users'] = HrbacUserModel::getUsers($pageSize, $pageSize * $currentPage);
		$this->render('list', array('data'=>$data));
	}
	
	public function actionViewitem()
	{
		$data = HrbacUserModel::getAuthItem($_GET['userid'], $_GET['itemid']);
		$this->render('viewitem',array( 'data'=>$data) );
	}
	
	public function actionViewuser()
	{
		$data = array('userid'=>$_GET['userid']);
		$data['username'] = Yii::app()->db->createCommand("SELECT username FROM users WHERE id=:userid")
			->queryScalar(array(':userid'=>$_GET['userid']));
		$data['auths'] = HrbacUserModel::getAuthsByUserId($_GET['userid']);
		$data['allAuths'] = HrbacUserModel::getAllAuthsByUserId($_GET['userid']);
		$this->render('viewuser',array( 'data'=>$data) );
	}
	
	public function actionEdititem()
	{
		if(Yii::app()->request->isPostRequest)
		{
			HrbacUserModel::updateAuthItem($_GET['userid'], $_GET['itemid'], $_POST['auth_cond'], $_POST['auth_bizrule']);
			$this->actionViewitem();
			return;
		}
		
		$data = HrbacUserModel::getAuthItem($_GET['userid'], $_GET['itemid']);
		$this->render('edititem',array( 'data'=>$data) );
	}
	
	public function actionRemoveitem()
	{
		if(Yii::app()->request->isPostRequest)
		{
			HrbacUserModel::removeAuthItem($_GET['userid'], $_GET['itemid']);
		}
//		$this->actionList();		//TODO: Show user assignment and if none show list.
		$this->redirect(array('list'));
	}
	
	public function actionAssign()
	{
		$data = array('cond'=>'', 'bizrule'=>'', 'error'=>'');
		if(Yii::app()->request->isAjaxRequest && isset($_GET['q']))
		{
			$users = Yii::app()->db->createCommand("SELECT id, username FROM " . HrbacModule::$usersTable . " WHERE username LIKE :partial")
				->queryAll(true, array(':partial'=>$_GET['q'] . '%'));
			$ret = array();
			foreach($users as $user)
			{
				$ret[] = $user['username'] . '|' . $user['id'] ; 
			}
			echo implode("\n", $ret);
			return;
		}
		if( isset($_GET['userid']) )
		{
			$data['userid'] = $_GET['userid']; 
			$data['username'] = Yii::app()->db->createCommand("SELECT username FROM users WHERE id=:userid")
				->queryScalar(array(':userid'=>$_GET['userid']));
			$data['auths'] = HrbacUserModel::getAuthAssignmentsByUserId($_GET['userid']);
		}
		
		if(Yii::app()->request->isPostRequest)
		{
			$messages = array(); $errors = array();
			foreach($data['auths'] as $auth)
			{
				if( in_array($auth['auth_id'], $_POST['include_ids']) )
				{
					if($_POST['bizrule'][$auth['auth_id']] && strpos($_POST['bizrule'][$auth['auth_id']], 'return') === false )
						$errors[$auth['auth_id']] =  "The PHP rule must have a return statement and return true or false";
					elseif($_POST['bizrule'][$auth['auth_id']] && eval( 'return true; ' . $_POST['bizrule'][$auth['auth_id']]) !== true )
						$errors[$auth['auth_id']] =  "The PHP rule has a syntax error";
					elseif($auth['ischild'])
					{
						// Update
						if( $_POST['cond'][$auth['auth_id']] != $auth['cond'] || $_POST['bizrule'][$auth['auth_id']] != $auth['bizrule'] )
						{
							HrbacUserModel::updateAuthItem($data['userid'], $auth['auth_id'], 
								$_POST['cond'][$auth['auth_id']], $_POST['bizrule'][$auth['auth_id']]);
							$messages[$auth['auth_id']] = 'Assignment Updated';
						}
					}
					else
					{
						// Add
						HrbacUserModel::assignAuthItem($data['username'], $auth['auth_id'], $_POST['cond'][$auth['auth_id']], $_POST['bizrule'][$auth['auth_id']]);
						$messages[$auth['auth_id']] = 'Assignment Added';
					}
				}
				elseif($auth['ischild'])
				{
					// Delete
					HrbacUserModel::removeAuthItem($data['userid'], $auth['auth_id']);
					$messages[$auth['auth_id']] = 'Assignment Removed';
				}
			}
			$data['auths'] = HrbacUserModel::getAuthAssignmentsByUserId($_GET['userid']);
			if($messages) $data['messages'] = $messages;
			if($errors)
			{
				$data['errors'] = $errors;
				foreach($data['auths'] as $key=>$auth)
				{
					if( array_key_exists($auth['auth_id'], $errors))
					{
						$data['auths'][$key]['bizrule'] = $_POST['bizrule'][$auth['auth_id']];
						$data['auths'][$key]['cond'] = $_POST['cond'][$auth['auth_id']];
					}
				}
			}
		}
		
		
		$this->render('assign', array('data'=>$data));
	}
}
