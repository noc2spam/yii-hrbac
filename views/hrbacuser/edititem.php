<?php
$name = $data['alt_name'] ? $data['alt_name'] : $data['authname'];

$this->pageTitle = Yii::t('auth', 'Update Auth Assignment');
$this->breadcrumbs = array (
	Yii::t ( "auth", "Access Control" )=>array('hrbacitem/index'), 
	Yii::t ( "auth", "Update Auth Assignment" ), 
	);
	
$this->menu = array(
	array('label'=>Yii::t('auth', 'View This Assignment'), 'url'=>array('viewitem','userid'=>$data['user_id'], 'itemid'=>$data['auth_id']) ),
	array('label'=>Yii::t('auth', 'Remove This Assignment'), 'url'=>'#', 
		'linkOptions'=>array('submit'=>array('removeitem','userid'=>$data['user_id'], 'itemid'=>$data['auth_id']),'confirm'=>'Are you sure to remove this assignment?')),
	array('label'=>' '),
	array('label'=>' '),
	array('label'=>Yii::t('auth', 'Create Auth Item'), 'url'=>array('hrbacitem/create')),
	array('label'=>Yii::t('auth', 'Assign Auth Item'), 'url'=>array('assign')),
	array('label'=>'List Auth Items', 'url'=>array('hrbacitem/index')),
	array('label'=>'User Permissions', 'url'=>array('authuser/list')),
);

echo "<h2>Update assignment of '{$name}' to '{$data['username']}'</h2>";
echo "Specify the condition or the PHP rule that will determine if this permission applies to the user.
	Leaving it blank means that the permission always applies. <br/><br/>";

$type = array('Operation', 'Op Group', 'Role');

$syntaxHighlighter = new CTextHighlighter();
$syntaxHighlighter->language = 'PHP';

$attributes = array(
	array('label'=>'User', 'value'=>$data['username']),
	array('label'=>'Auth', 'value'=>$name), 
	array('label'=>'Type', 'value'=>$type[$data['type']]),
	array('name'=>'description', 'label'=>'Description'),
	);
	
$model = $data['model'];
function listAuthAttr($items, $label, $type)
{
	$attr = array(); 
	foreach( $items as $item )
	{
		$attr[] = '('.$type[$item->type].') ' 
			. CHtml::link(CHtml::encode($item['name']), array('viewitem', 'id'=>$item['auth_id']));
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

<br/><br/>
<form method="post">
<div class="form">
	<div class="row">
		<label for="_cond">Condition</label>
		<input size="60" maxlength="250" name="auth_cond" id="_cond" value="<?php echo $data['cond']; ?>" type="text">
	</div>
	
	<div class="row">
		<label for="_bizrule">Php Rule</label>
		<textarea rows="6" cols="50" name="auth_bizrule" id="_bizrule"><?php echo $data['bizrule']; ?></textarea>
	</div>
	
	<div class="row buttons">
		<input name="yt0" value="Update" type="submit">	</div>
</div>
</form>