<div class="view">

	<b><?php echo CHtml::encode($data->getAttributeLabel('name')); ?>:</b>
	<?php echo CHtml::link(CHtml::encode($data->name), array('view', 'id'=>$data->auth_id)); ?>
	<br />

	<?php if(!empty($data->alt_name)){?>
	<b><?php echo CHtml::encode($data->getAttributeLabel('alt_name')); ?>:</b>
	<?php echo CHtml::encode($data->alt_name); ?>
	<br />
	<?php }?>

	<b><?php echo CHtml::encode($data->getAttributeLabel('type')); ?>:</b>
	<?php
		$type = array('Operation', 'Op Group', 'Role'); 
		echo CHtml::encode($type[$data->type]); 
	?>
	<br />

	<b><?php echo CHtml::encode($data->getAttributeLabel('description')); ?>:</b>
	<?php echo CHtml::encode($data->description); ?>
	<br />

	<?php if(!empty($data->cond)){?>
	<b><?php echo CHtml::encode($data->getAttributeLabel('cond')); ?>:</b>
	<?php echo CHtml::encode($data->cond); ?>
	<br />
	<?php }?>

	<?php if(!empty($data->bizrule)){?>
	<b><?php echo CHtml::encode($data->getAttributeLabel('bizrule')); ?>:</b>
	<?php 
	//echo CHtml::encode($data->bizrule);
	$syntaxHighlighter = new CTextHighlighter();
	$syntaxHighlighter->language = 'PHP';
	$syntaxHighlighter->processOutput($data->bizrule);	 
	?>
	<br />
	<?php }?>

	<?php
	$authChildren = $data->authChildren;
	if(count($authChildren))
	{
		echo '<b>Includes: </b>';
		foreach($authChildren as $authItem)
		{
			$name = $authItem->alt_name ? $authItem->alt_name : $authItem->name;
			echo '('.$type[$authItem->type].') ' 
				. CHtml::link(CHtml::encode($name), array('view', 'id'=>$authItem->auth_id)) . '. ';
		}
	}
	?>

</div>