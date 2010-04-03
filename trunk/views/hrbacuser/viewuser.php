<?php
$this->pageTitle = Yii::t('auth', 'Users Auth Assignment');
$this->breadcrumbs = array (
	Yii::t ( "auth", "Access Control" )=>array('hrbacitem/index'), 
	Yii::t ( "auth", "Users Auth Assignment" ), 
	);

$this->menu = array(
	array('label'=>Yii::t('auth', 'Assign Auth'), 'url'=>array('assign', 'userid'=>$data['userid'])),
	array('label'=>' '),
	array('label'=>' '),
	array('label'=>Yii::t('auth', 'Create Auth Item'), 'url'=>array('create')),
	array('label'=>Yii::t('auth', 'Assign Auth Item'), 'url'=>array('assign')),
	array('label'=>'List Auth Items', 'url'=>array('hrbacitem/index')),
	array('label'=>'User Permissions', 'url'=>array('authuser/list')),
);


echo "<h2>Auth Assignments to '{$data['username']}'</h2>";

$type = array('Operation', 'Op Group', 'Role');


$syntaxHighlighter = new CTextHighlighter();
$syntaxHighlighter->language = 'PHP';

foreach($data['auths'] as $auth)
{
	$name = $auth['alt_name'] ? $auth['alt_name'] : $auth['name'];
	echo '<div class="view">';
	echo '<b>Auth:</b> ' . CHtml::link(CHtml::encode($name), array('viewitem', 'userid'=>$data['userid'], 'itemid'=>$auth['auth_id']));
	echo '<br><b>Type:</b> ' . $type[$auth['type']];
	if( $auth['description'] ) echo '<br><b>Description:</b> ' . $auth['description'];
	if($auth['cond']) echo '<br><b>Condition:</b> ' . $auth['cond'];
	if($auth['bizrule']) echo '<br><b>PHP Rule:</b> ' . $syntaxHighlighter->highlight($auth['bizrule']);
	echo '</div>';
}
?>

<div class="view">
<b>All Roles, Op Groups and Ops directly or indirectly assigned to this user:</b>
<br/>
<?php
$list = array();
foreach($data['allAuths'] as $auth)
{
	$name = $auth['alt_name'] ? $auth['alt_name'] : $auth['name']; 
	$list[] = '('.$type[$auth['type']].') ' 
			. CHtml::link(CHtml::encode($name), array('viewitem', 'userid'=>$data['userid'], 'itemid'=>$auth['auth_id']));
}
echo implode(", ", $list);
?>
</div>