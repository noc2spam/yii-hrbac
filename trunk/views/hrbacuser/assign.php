<?php
$this->pageTitle = Yii::t('auth', 'Assign Auth');
$this->breadcrumbs = array (
	Yii::t ( "auth", "Access Control" )=>array('hrbacitem/index'), 
	Yii::t ( "auth", "Assign Role" ), 
	);

$this->menu = array(
	array('label'=>Yii::t('auth', 'User Permissions'), 'url'=>array('list')),
	array('label'=>' '),
	array('label'=>' '),
	array('label'=>'Auth Items', 'url'=>array('hrbacitem/index')),
);

echo "<h2>{$this->pageTitle}";
if(isset($data['username'])) echo " to '" . $data['username'] . "'";
echo "</h2>";
?>
<hr />
<style type="text/css">
div.evenrow {background-color: #E5F1F4; overflow: auto; padding: 10px; margin-right:10px;}
div.oddrow {background-color: #F8F8F8; overflow: auto; padding: 10px; margin-right:10px;}
div.titlerow {background-color: #B7D6E7; overflow: auto; padding: 10px; margin-right:10px;}
span.authname {font-weight: bold;}
</style> 

<form method="post">
<div class="form">
<span class="error"><?php echo $data['error']; ?></span>

<div class="row">
	<br/>
	<label for="_user">User</label>
	<?php
	if( isset($data['username']) )
	{
		echo '<input type="hidden" id="_user" name="username" value="' . $data['username'] . '" />';
		echo '<b>' . $data['username'] . '</b><br><br>';
	} 
	else 
	{
		$this->widget('CAutoComplete', array(
			'name'=>'username',
			'multiple'=>false,
			'mustMatch'=>true,
			'matchContains'=>true,
			'autoFill'=>true,
			'id'=>'_user',
			'htmlOptions'=>array('size'=>20),
		));
	}
	?>
</div>

<div class="titlerow">
<div class="span-13">Auth Item</div><div class="span-2">Type</div><div class="span-2">Select</div>
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
<div class="span-13">
	<span class="authname"><?php echo CHtml::encode($name); ?></span><br/>
	<span class="authdesc"><?php echo CHtml::encode($data['description']); ?></span>
</div>
<div class="span-2"><?php echo $type[$data['type']]; ?></div>
<div class="span-2"><input type="radio" name="authid" value="<?php echo $data['auth_id']; ?>"  /></div>
	
<?php 
}

displayRows($data['auths'], 'displayRow');
?>
	
	<div class="row">
		<label for="_cond">Condition</label>
		<input size="60" maxlength="250" name="auth_cond" id="_cond" type="text" value="<?php echo $data['cond']; ?>">
	</div>
	
	<div class="row">
		<label for="_bizrule">Php Rule</label>
		<textarea rows="6" cols="50" name="auth_bizrule" id="_bizrule"><?php echo $data['bizrule']; ?></textarea>
	</div>
	
	<div class="row buttons">
		<input name="yt0" value="Assign" type="submit">	</div>
</div>
</form>
