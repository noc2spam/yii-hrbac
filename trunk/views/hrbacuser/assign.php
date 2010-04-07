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

if( !isset($data['username']) )
{
	echo '<form method="get"><div class="form"><label for="_user">Specify User</label>';
	echo CHtml::hiddenField('r',$this->getRoute());
	$this->widget('CAutoComplete', array(
		'name'=>'username',
		'multiple'=>false,
		'mustMatch'=>true,
		'matchContains'=>true,
		'autoFill'=>true,
		'id'=>'_user',
		'htmlOptions'=>array('size'=>20),
		'methodChain'=>'.result(function(event,item){jQuery("#userid").val(item[1]);})',
	));
	echo CHtml::hiddenField('userid'); 
	echo CHtml::submitButton('Continue');
	echo '</div></form>';
	return;
}

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
<?php 
if( isset($data['errors']) ) echo '<span class="error">There was an error. See below</span>';
elseif( isset($data['messages']) ) echo '<span class="success">User Assignment was updated</span>';

function displayRows($data, $alldata)
{
	if(!count($data)) return;
	
	$titlerow = '
	<div class="titlerow">
		<div class="span-9">Auth Item</div><div class="span-2">Currently Included</div><div class="span-2">Indirectly Included</div><div class="span-2">Include</div>
	</div>
	'; 
	$evenodd = array('evenrow','oddrow');
	$types = array('Operations', 'Op Groups (Tasks)', 'Roles');
	foreach(array(2,1,0) as $type)
	{
		$panels[$types[$type]] = $titlerow;
		$rownum[$types[$type]] = 0;
	}
	
	foreach($data as $datum)
	{
		$panel = $types[$datum['type']];
		$panels[$panel] .= '<div class="' . $evenodd[$rownum[$panel]++ % 2] . '">'; 
		$panels[$panel] .= getRow($datum, $alldata);
		$panels[$panel] .= '</div>';
	}
	return $panels;
}

function getRow($data, $alldata)
{
	$type = array('Op', 'Group', 'Role');
	$name = $data['alt_name'] ? $data['alt_name'] : $data['name'];
	$retval = '
	<div class="span-9">
		<span class="authname">' . CHtml::encode($name) .'</span><br/>
		<span class="authdesc">' . CHtml::encode($data['description']).'</span>
	</div>
	<div class="span-2"><input type="checkbox" disabled '. ($data['ischild'] ? 'checked' : '') . ' /></div>
	<div class="span-2"><input type="checkbox" disabled '. ($data['isdescendant'] ? 'checked' : '') . ' /></div>
	<div class="span-2"><input type="checkbox" name="include_ids[]" value="'.$data['auth_id'].'" '. ($data['ischild'] ? 'checked' : '') . ' /></div>
	<div class="span-15 auth_condition '. ($data['ischild']? 'auth_existing' : '') .'">
		<div class="row">
			<label for="_cond">Condition</label>
			<input size="60" maxlength="250" name="cond['.$data['auth_id'].']" id="_cond" type="text" value="'.$data['cond'].'">
		</div>
		
		<div class="row">
			<label for="_bizrule">Php Rule</label>
			<textarea rows="6" cols="60" name="bizrule['.$data['auth_id'].']" id="_bizrule">'.$data['bizrule'].'</textarea>
		</div>
	</div>
	';
	if( isset($alldata['errors'][$data['auth_id']]) )
		$retval .= '<span class="error">'.$alldata['errors'][$data['auth_id']].'</span>';
	elseif( isset($alldata['messages'][$data['auth_id']]) )
		$retval .= '<span class="success">'.$alldata['messages'][$data['auth_id']].'</span>';
	return $retval;
}

$panels = displayRows($data['auths'], $data );
$this->widget('zii.widgets.jui.CJuiAccordion', array(
	'panels'=>$panels,
    'options'=>array(
		'autoHeight'=> false,
    ),
));

?>
<br/>
<input type="submit" value="Submit" />
</div>
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
