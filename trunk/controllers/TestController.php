<?php

class TestController extends Controller
{
	public function actionIndex()
	{
    	$db = Yii::app()->authManager->db;
    	$authManager = new HrbacManager();
    	$authManager->db = $db;
    	if($asfd)
    	{
    		
    	}

//    	echo "test";
//    	echo Yii::t('AuthModule.auth', 'Manage Auth');
//    	$authManager->assign('Edit Post', 1);
//    	echo $authManager->isAssigned('Edit Post', 1);
//    	$authManager->recreateAuthPathTable();
//		echo $authManager->checkAccess('Delete Post', 2);

//    	$a = new HrbacItemModel();
//    	$b = new HrbacItem('asdf', 'qwer', 1);
//    	$c = new BehaviorInterfaceClass();
//    	$b->attachBehavior('adfs', $c);
//    	$a->attachBehavior('qer', $b);
		echo 'this is it';
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
	
	// Maybe : operator might end with |. In this case use OR for next condition. Can be a chain.
	// As soon as any of them satisfies condition, skip remaining OR conditions and keep going.
	
	public static function checkConditions($condStr, $paramsCond)
	{
		function checkGroup($group, $refArray)
		{
			function checkSubGroup($group, $refArray, $all)
			{
				function checkSingleCondition($cond, $refArray)
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
				$conditions = explode($all ? '&&' : '||', $group);
				
				$retval = true;
				foreach( $conditions as $condition )
				{
					$ret = checkSingleCondition($condition, $refArray);
					if($all && !$ret) return false;
					if(!$all && $ret) return true;
					if(!$all && !$ret) $retval = false;
				}
				return $retval;
			}
			
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
				$ret = checkSubGroup($condition, $refArray, !$all);
				if($all && !$ret) return false;
				if(!$all && $ret) return true;
				if(!$all && !$ret) $retval = false;
			}
			return $retval;
		}
		
		// Return true if no condition specified.
		if( trim($condStr, '; ') === "") 
			return true;
		if(!is_null($paramsCond) && !is_array($paramsCond))
			throw new CException('Second arg to checkConditions should be null or an Array');
		$refArray = $paramsCond !== null ? $paramsCond : $_REQUEST;
		$condGroups = explode(';', $condStr);
		foreach($condGroups as $group)
		{
			if( !checkGroup($group, $refArray) ) return false;
		}
		return true;
	}
}
