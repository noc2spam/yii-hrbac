<?php
$this->breadcrumbs=array(
	'Access Control'=>array('hrbacuser/list'),
	'View:' . $model->name,
);

$this->menu=array(
	array('label'=>'Edit This Item', 'url'=>array('update', 'id'=>$model->auth_id)),
	array('label'=>'Include Other Items', 'url'=>array('include', 'id'=>$model->auth_id), 'visible'=>($model->type > 0) ),
	array('label'=>'Delete This Item', 'url'=>'#', 'linkOptions'=>array('submit'=>array('delete','id'=>$model->auth_id),'confirm'=>'Are you sure to delete this item?')),
	array('label'=>'Users Having This', 'url'=>array('hrbacuser/list', 'authid'=>$model->auth_id)),
	array('label'=>' '),
	array('label'=>' '),
	array('label'=>'List Auth Items', 'url'=>array('index')),
	array('label'=>'Create Auth Item', 'url'=>array('create')),
	array('label'=>'Manage Auth Items', 'url'=>array('admin')),
	array('label'=>'User Permissions', 'url'=>array('hrbacuser/list')),
);
?>

<h1>View: <?php echo $model->alt_name ? $model->alt_name : $model->name; ?></h1>

<?php 
$type = array('Operation', 'Op Group', 'Role');

$syntaxHighlighter = new CTextHighlighter();
$syntaxHighlighter->language = 'PHP';

$attributes = array(
	'name',
	'alt_name',
	array('label'=>'Type', 'value'=>$type[$model->type]),
	'description',
	'cond',
	array('name'=>'bizrule', 'type'=>'raw', 'value'=>$syntaxHighlighter->highlight($model->bizrule))
	);
	
function listAuthAttr($items, $label, $type)
{
	$attr = array(); 
	foreach( $items as $item )
	{
		$name = $item['alt_name'] ? $item['alt_name'] : $item['name'];
		$attr[] = '('.$type[$item->type].') ' 
			. CHtml::link(CHtml::encode($name), array('view', 'id'=>$item['auth_id']));
	}
	if(count($attr)) return array('label'=>$label,'type'=>'raw', 'value'=>implode(', ', $attr)); 
	else return array();
}

if( ($attr = listAuthAttr($model->authChildren, 'Directly Includes', $type)) !== array() )
	$attributes[] = $attr;
if( ($attr = listAuthAttr($model->getAuthDescendants(2), 'Indirectly Includes', $type)) !== array() )
	$attributes[] = $attr;
if( ($attr = listAuthAttr($model->authParents, 'Directly Included In', $type)) !== array() )
	$attributes[] = $attr;
if( ($attr = listAuthAttr($model->getAuthAncestors(2), 'Indirectly Included In', $type)) !== array() )
	$attributes[] = $attr;
	
$this->widget('zii.widgets.CDetailView', array(
	'data'=>$model,
	'attributes'=>$attributes
));

?>
