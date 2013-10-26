<?php 
	$params = array('number' => 0); 
	if(isset($options['role']) && $options['role'] != 'all')
		$params['role'] = $options['role'] ;
	$users = get_users($params);

?>
<select <?php html_attributes(array('name' => $name, 'id' => $id, 'class' => 'text')) ?>  <?php echo $html ?> <?php echo $validations ?> >
	<?php if(!isset($options['required']) || !$options['required']) echo "<option value='0'>--</option>" ; ?>
<?php foreach ($users as $user) {?>
	<option value="<?php echo $user->ID ?>" <?php echo $object->$field == $user->ID ? ' selected' : ''?> >
		<?php echo $user->user_nicename ?>
	</option>
<?php } ?>
</select> 
<?php description($options['description']) ?>