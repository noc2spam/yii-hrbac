<?php
$this->pageTitle = "List Access Control Items";
$this->breadcrumbs=array(
	'Access Control'=>array('hrbacuser/list'),
	'List Auth Items',
);

$this->menu=array(
	array('label'=>'Create Auth Item', 'url'=>array('create')),
	array('label'=>'Manage Auth Items', 'url'=>array('admin')),
	array('label'=>' '),
	array('label'=>' '),
	array('label'=>'User Permissions', 'url'=>array('hrbacuser/list')),
);
?>

<h1><?php $this->pageTitle ?></h1>

<?php $this->widget('zii.widgets.CListView', array(
	'dataProvider'=>$dataProvider,
	'itemView'=>'_view',
)); ?>
