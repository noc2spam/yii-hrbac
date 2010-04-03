<?php
$this->pageTitle = Yii::t('auth', 'Users with Role Assignments');
$this->breadcrumbs = array (
	Yii::t ( "auth", "Access Control" )=>array('hrbacitem/index'), 
	Yii::t ( "auth", "Authorized Users" ), 
	);

$this->menu = array(
	array('label'=>Yii::t('auth', 'Assign Auth To User'), 'url'=>array('assign')),
	array('label'=>' '),
	array('label'=>' '),
	array('label'=>'List Auth Items', 'url'=>array('hrbacitem/index')),
	array('label'=>'Manage Auth Items', 'url'=>array('hrbacitem/admin')),
	array('label'=>'Create Auth Item', 'url'=>array('hrbacitem/create')),
	);

echo "<h2>{$this->pageTitle}</h2>";

//$userCount = HrbacUserModel::getUserCount();
//$pageSize = 1;		//TODO: Make this configurable or provide UI
//$widget = $this->createWidget('CLinkPager', array('itemCount'=>$userCount, 'pageSize'=>$pageSize));
//$currentPage = $widget->getCurrentPage();
//$users = HrbacUserModel::getUsers($pageSize, $pageSize * $currentPage);
?><!-- php -->

<p>
All users have at least one default role, 'Anonymous User' or 'Authenticated User', 
depending on whether they have logged in or not. Other roles, that are not only 
dependent on who the user is but also on what the object of the action is, can also
be specified (see documentation).
</p>

<p>
This page only lists permissions that are directly assigned to a user, not those that are inherited through the Roles and Op Groups. 
</p>

<?php
if($data['authid'])
{
	$name = $data['authitem']['alt_name'] ? $data['authitem']['alt_name'] : $data['authitem']['name'];
	echo "<h3>Users with permission for '" . $name . "'</h3>";
}
?>

<style type="text/css">
table, td, th {border-style: solid;}
table {border-top-width: 1px; border-left-width: 1px;}
td, th {border-bottom-width: thin; border-right-width: thin;}
th { background-color: #6faccf; }
</style>

<table class="user_roles" >
<tr>
<th class="user_name">User</th>
<th class="auth_type">Auth Type</th>
<th class="auth">Auth Item (Roles, Op Groups, Operations)</th>
</tr>

<tbody>
<?php 
$type = array('Operation', 'Op Group', 'Role');
$rownum = 0;
$evenodd = array('even', 'odd');
foreach($data['users'] as $user)
{
	$auths = $user['roles'];
	$rowClass = $evenodd[$rownum++ % 2] . 'user';
	$authCount = count($auths);
	foreach($auths as $index=>$auth)
	{
		echo "<tr class='{$rowClass}'>";
		if($index == 0) 
			echo "<td rowspan='{$authCount}'>" 
			. CHtml::link(CHtml::encode($user['username']),array("viewuser","userid"=>$user['userid'])) 
			. "</td>";
		echo "<td>" . $type[$auth['type']] . "</td>";
		echo "<td>";
//		echo CHtml::link(CHtml::encode($auth['name']),array("authuser/edituser","user"=>$auth['name'])) ;
		echo CHtml::link(CHtml::encode($auth['alt_name'] ? $auth['alt_name'] : $auth['name']), array('viewitem', 'userid'=>$user['userid'], 'itemid'=>$auth['auth_id']));
		if( $auth['description'] )
			echo "<br/>{$auth['description']}";
		if( isset($auth['cond']) && $auth['cond'] )
			echo "<br/><br/>Condition: {$auth['cond']}";
		if( isset($auth['bizrule']) && $auth['bizrule'] )
		{
			echo "<br/><br/>PHP Rule: ";
			$syntaxHighlighter = new CTextHighlighter();
			$syntaxHighlighter->language = 'PHP';
			$syntaxHighlighter->processOutput($auth['bizrule']);
			if( isset($auth['data']) && $auth['data'] )
			{
				echo "<br/><br/>PHP Data: ";
				$syntaxHighlighter->processOutput($auth['data']);
			}
		}
		echo "</td>";
	}
	
}
?>
</tbody>
</table>
<br/>
<?php $data['widget']->run(); ?>
