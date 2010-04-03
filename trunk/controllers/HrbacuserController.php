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
			$names = Yii::app()->db->createCommand("SELECT username FROM users WHERE username LIKE :partial")
				->queryColumn(array(':partial'=>$_GET['q'] . '%'));
			$ret = implode("\n", $names);
			echo $ret;
			return;
		}
		elseif(Yii::app()->request->isPostRequest)
		{
			//authid, username, auth_cond, auth_bizrule
			$error = HrbacUserModel::assignAuthItem($_POST['username'], $_POST['authid'], $_POST['auth_cond'], $_POST['auth_bizrule']);
			if($error)
			{
				$data['error'] = $error;
				$data['cond'] = $_POST['auth_cond'];
				$data['bizrule'] = $_POST['auth_bizrule'];
			}
			else $this->redirect(array('list')); 
		}
		elseif( isset($_GET['userid']) )
		{
			$data['userid'] = $_GET['userid']; 
			$data['username'] = Yii::app()->db->createCommand("SELECT username FROM users WHERE id=:userid")
				->queryScalar(array(':userid'=>$_GET['userid']));
			$data['auths'] = HrbacUserModel::getAssignableAuthsByUserId($_GET['userid']);
		}
		if( !isset($data['auths']) )
		{
			$auths = HrbacItemModel::model()->findAll();
			$data['auths'] = $auths;
		}
		$this->render('assign', array('data'=>$data));
	}
}
