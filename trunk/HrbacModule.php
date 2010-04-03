<?php

//TODO: Localization.

class HrbacModule extends CWebModule
{
	public $layout = 'application.views.layouts.column2';
	public static $usersTable = 'users';		// Table name containing user ids
	
	public function init()
	{
		$this->setImport(array(
			'hrbac.models.*',
			'hrbac.components.*',
		));
	}
	
	/**
	 * Configures the module with the specified configuration.
	 * Override base class implementation to allow static variables.
	 * @param array the configuration array
	 */
	public function configure($config)
	{
		if(is_array($config))
		{
			foreach($config as $key=>$value)
			{
				if(isset(HrbacModule::${$key}))
				{
					HrbacModule::${$key} = $value;
				}
				else 
					$this->$key=$value;
			}
		}
	}
	
}
