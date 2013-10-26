
<select <?php html_attributes(array('name' => $name, 'id' => $id, 'class' => 'text')) ?>  <?php echo $html ?> <?php echo $validations ?> >
<?php foreach ($options['values'] as $value => $label) {?>
	<option value="<?php echo $value ?>" <?php echo $object->$field == $value ? ' selected' : ''?> >
		<?php echo $label ?>
	</option>
<?php } ?>
</select> 
<?php description($options['description']) ?>