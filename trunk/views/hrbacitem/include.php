<?php
$this->pageTitle = "Include auth items";
$this->breadcrumbs=array(
	'Access Control'=>array('index'),
	'Include Auth Items',
);

$this->menu=array(
	array('label'=>'View Item Details', 'url'=>array('view', 'id'=>$model->auth_id)),
	array('label'=>'Create Auth Item', 'url'=>array('create')),
	array('label'=>'Manage Auth Items', 'url'=>array('admin')),
	array('label'=>' '),
	array('label'=>' '),
	array('label'=>'List Auth Items', 'url'=>array('index')),
	array('label'=>'User Permissions', 'url'=>array('hrbacuser/list')),
);
?>

<h1><?php echo CHtml::encode($model['alt_name'] ? $model['alt_name'] : $model['name']); ?></h1>
Add or remove auth items to this role or op group.
<br/><br/>

<style type="text/css">
div.evenrow {background-color: #E5F1F4; overflow: auto; padding: 10px; margin-right:10px;}
div.oddrow {background-color: #F8F8F8; overflow: auto; padding: 10px; margin-right:10px;}
div.titlerow {background-color: #B7D6E7; overflow: auto; padding: 10px; margin-right:10px;}
span.authname {font-weight: bold;}
</style> 
<form id="include-auth-form" method="post">	
<?php


function displayRows($data, $function)
{
	if(!count($data)) return;
	
	$titlerow = '
	<div class="titlerow">
		<div class="span-8">Auth Item</div><div class="span-2">Currently Included</div><div class="span-2">Indirectly Included</div><div class="span-2">Include</div>
	</div>
	'; 
	$evenodd = array('evenrow','oddrow');
	$type = array('Operations', 'Op Groups (Tasks)', 'Roles');
	
	$curtype = -1; $rownum = 0; $panels = array();
	foreach($data as $datum)
	{
		if( $curtype != $datum['type'])
		{
//			if($curtype != -1) $panels[$panel] .= '</div>';
			$curtype = $datum['type'];
			$panel = $type[$curtype];
			$panels[$panel] = $titlerow;
		}
		$panels[$panel] .= '<div class="' . $evenodd[$rownum++%2] . '">'; 
		$panels[$panel] .= $function($datum);
		$panels[$panel] .= '</div>';
	}
//	$panels[$panel] .= '</div>';
	return $panels;
}

function getRow($data)
{
	$type = array('Op', 'Group', 'Role');
	$name = $data['alt_name'] ? $data['alt_name'] : $data['name'];
	$retval = '
	<div class="span-8">
		<span class="authname">' . CHtml::encode($name) .'</span><br/>
		<span class="authdesc">' . CHtml::encode($data['description']).'</span>
	</div>
	<div class="span-2"><input type="checkbox" disabled '. ($data['ischild'] ? 'checked' : '') . ' /></div>
	<div class="span-2"><input type="checkbox" disabled '. ($data['isdescendant'] ? 'checked' : '') . ' /></div>
	<div class="span-2"><input type="checkbox" name="include_ids[]" value="'.$data['auth_id'].'" '. ($data['ischild'] ? 'checked' : '') . ' /></div>
	<div class="span-15 auth_condition '. ($data['ischild']? 'auth_existing' : '') .'">
		<br>Condition: <input type="text" value="'.$data['cond'].'" name="cond['.$data['auth_id'].']" maxlength="250" size="70" />
	</div>
	';
	return $retval;
}

$panels = displayRows($model->getPotentialDescendants(), 'getRow');
$this->widget('zii.widgets.jui.CJuiAccordion', array(
	'panels'=>$panels,
    'options'=>array(
		'autoHeight'=> false,
//		'event'=>'mouseover', 
    ),
));
//<h3><a href="#">panel 1</a></h3>
//<div>content for panel 1</div>
//<h3><a href="#">panel 2</a></h3>
//<div>content for panel 2</div>

//displayRows($model->getPotentialDescendants(), 'getRow');
?>
<br/>
<input type="submit" value="Submit" />
</form>
<script type="text/javascript">
/*<![CDATA[*/
//jQuery(document).ready(function() {
jQuery('.auth_condition').not('.auth_existing').hide();
jQuery(':checkbox').change(function(){
	//alert( this.checked );
	jQuery(this).parent().next().toggle(this.checked);
});
//});
/*]]>*/
</script>
