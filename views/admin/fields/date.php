<input type="text" <?php html_attributes( array( 'name' => $name, 'id' => $id, 'value' => $object->$field, 
	'size' => 10, 'class' => 'date' )) ?> <?php echo $html ?> <?php echo $validations ?> >