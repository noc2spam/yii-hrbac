<?php
$this->breadcrumbs=array(
	'Access Control'=>array('index'),
	'Create',
);

$this->menu=array(
	array('label'=>'List Auth Items', 'url'=>array('index')),
	array('label'=>'Manage Auth Items', 'url'=>array('admin')),
);
?>

<h1>Create Auth Item : Role, Op Group or Operation</h1>

<?php echo $this->renderPartial('_form', array('model'=>$model)); ?>