<?php
$name = $model->alt_name ? $model->alt_name : $model->name;
$this->breadcrumbs=array(
	'Access Control'=>array('hrbacuser/list'),
	$name=>array('view','id'=>$model->auth_id),
	'Update',
);

$this->menu=array(
	array('label'=>'View Auth Item', 'url'=>array('view', 'id'=>$model->auth_id)),
	array('label'=>'Delete This Item', 'url'=>'#', 'linkOptions'=>array('submit'=>array('delete','id'=>$model->auth_id),'confirm'=>'Are you sure to delete this item?')),
	array('label'=>' '),
	array('label'=>' '),
	array('label'=>'List Auth Items', 'url'=>array('index')),
	array('label'=>'Create Auth Item', 'url'=>array('create')),
	array('label'=>'Manage Auth Items', 'url'=>array('admin')),
	array('label'=>'User Permissions', 'url'=>array('hrbacuser/list')),
);
?>

<h1>Update Auth Item : <?php echo $name; ?></h1>

<?php echo $this->renderPartial('_form', array('model'=>$model)); ?>