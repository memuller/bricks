
<select <?php html_attributes(array('name' => $name, 'id' => $id, 'class' => 'text')) ?>  <?php echo $html ?> <?php echo $validations ?> >
<?php foreach ($options['values'] as $value) {?>
	<option value="<?php echo $value ?>" <?php echo $object->$field == $value ? ' selected' : ''?> >
		<?php echo $value ?>
	</option>
<?php } ?>
</select> 
<?php description($options['description']) ?>