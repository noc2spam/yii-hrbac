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
	array('label'=>'User Permissions', 'url'=>array('authuser/list')),
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
<div class="titlerow">
<div class="span-9">Auth Item</div><div class="span-2">Type</div><div class="span-2">Currently Included</div><div class="span-2">Indirectly Included</div><div class="span-2">Include</div>
</div>
<?php 

function displayRows($data, $function)
{
	$evenodd = array('evenrow','oddrow');
	$rownum = 0;
	
	foreach($data as $datum)
	{
		echo '<div class="' . $evenodd[$rownum++%2] . '">'; 
		$function($datum);
		echo '</div>';
	}
}

function displayRow($data)
{
	$type = array('Op', 'Group', 'Role');
	$name = $data['alt_name'] ? $data['alt_name'] : $data['name'];
?>
<div class="span-9">
	<span class="authname"><?php echo CHtml::encode($name); ?></span><br/>
	<span class="authdesc"><?php echo CHtml::encode($data['description']); ?></span>
</div>
<div class="span-2"><?php echo $type[$data['type']]; ?></div>
<div class="span-2"><input type="checkbox" disabled <?php if($data['ischild']) echo 'checked'; ?> /></div>
<div class="span-2"><input type="checkbox" disabled <?php if($data['isdescendant']) echo 'checked'; ?> /></div>
<div class="span-2"><input type="checkbox" name="include_ids[]" value="<?php echo $data['auth_id']; ?>" <?php if($data['ischild']) echo 'checked'; ?> /></div>
	
<?php 
}

displayRows($model->getPotentialDescendants(), 'displayRow');
?>
<br/>
<input type="submit" value="Submit" />
</form>