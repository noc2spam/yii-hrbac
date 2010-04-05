<?php
$this->pageTitle = Yii::t('auth', 'Auth Item Assignment');
$this->breadcrumbs = array (
	Yii::t ( "auth", "Access Control" )=>array('hrbacitem/index'), 
	Yii::t ( "auth", "Auth Item Assignment" ), 
	);

$this->menu = array(
	array('label'=>Yii::t('auth', 'Edit This Assignment'), 'url'=>array('edititem','userid'=>$data['user_id'], 'itemid'=>$data['auth_id']) ),
	array('label'=>Yii::t('auth', 'Remove This Assignment'), 'url'=>'#', 
		'linkOptions'=>array('submit'=>array('removeitem','userid'=>$data['user_id'], 'itemid'=>$data['auth_id']),'confirm'=>'Are you sure to remove this assignment?')),
	array('label'=>' '),
	array('label'=>' '),
	array('label'=>Yii::t('auth', 'Create Auth Item'), 'url'=>array('create')),
	array('label'=>Yii::t('auth', 'Assign Auth Item'), 'url'=>array('assign')),
	array('label'=>'List Auth Items', 'url'=>array('hrbacitem/index')),
	array('label'=>'User Permissions', 'url'=>array('hrbacuser/list')),
);

$name = $data['alt_name'] ? $data['alt_name'] : $data['authname'];

echo "<h2>Assignment of '{$name}' to '{$data['username']}'</h2>";

$type = array('Operation', 'Op Group', 'Role');

$syntaxHighlighter = new CTextHighlighter();
$syntaxHighlighter->language = 'PHP';

$attributes = array(
	array('label'=>'User', 'value'=>$data['username']),
	array('label'=>'Auth', 'type'=>'raw', 'value'=>CHtml::link(CHtml::encode($name), array('hrbacitem/view', 'id'=>$data['auth_id']))), 
	array('label'=>'Type', 'value'=>$type[$data['type']]),
	array('name'=>'description', 'label'=>'Description'),
	array('name'=>'cond', 'label'=>'Condition'),
	array('name'=>'bizrule', 'label'=>'PHP Rule', 'type'=>'raw', 'value'=>$syntaxHighlighter->highlight($data['bizrule']))
	);
	
	// 
	
	
$model = $data['model'];
function listAuthAttr($items, $label, $type)
{
	$attr = array(); 
	foreach( $items as $item )
	{
		$name = $item['alt_name'] ? $item['alt_name'] : $item['name'];
		$attr[] = '('.$type[$item->type].') ' 
			. CHtml::link(CHtml::encode($name), array('viewitem', 'id'=>$item['auth_id']));
	}
	if(count($attr)) return array('label'=>$label,'type'=>'raw', 'value'=>implode(', ', $attr)); 
	else return array();
}

if( ($attr = listAuthAttr($model->authChildren, 'Directly Includes', $type)) !== array() )
	$attributes[] = $attr;
if( ($attr = listAuthAttr($model->getAuthDescendants(2), 'Indirectly Includes', $type)) !== array() )
	$attributes[] = $attr;

$this->widget('zii.widgets.CDetailView', array(
	'data'=>$data,
	'attributes'=>$attributes
));

?>