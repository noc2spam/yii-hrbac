<?php
class HrbacWebApplication extends CWebApplication
{
	public $openAccessRoutes = array();
//	private $_controller;
	
	/**
	 * Creates the controller and performs the specified action.
	 * @param string the route of the current request. See {@link createController} for more details.
	 * @throws CHttpException if the controller could not be created.
	 */
	public function runController($route)
	{
		if(($ca=$this->createController($route))!==null)
		{
			list($controller,$actionID)=$ca;
			$oldController=parent::getController();
			parent::setController($controller);
			$controller->init();
			// Check Access Control after controller is created so computed roles are available.
			if($route && !$this->checkOpenAccess($route))
			{
				$authRoute = $route[0] === '/' ? $route : '/' . $route;
				if( ! $this->user->checkAccess($authRoute) )
					throw new CHttpException(401,Yii::t('auth','You do not have permission to perform this action.'));
			}
			$controller->run($actionID);
			parent::setController($oldController);
		}
		else
			throw new CHttpException(404,Yii::t('yii','Unable to resolve the request "{route}".',
				array('{route}'=>$route===''?$this->defaultController:$route)));
	}
	
	public function checkOpenAccess($route)
	{
		if( !count($this->openAccessRoutes)  )
			return false;
		$authRoute = $route[0] === '/' ? $route : '/' . $route;
		foreach($this->openAccessRoutes as $openRoute)
		{
			if( strpos($authRoute, $openRoute) === 0 )
				return true;
		}
		return false;
	}
}