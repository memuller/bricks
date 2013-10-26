<input type="text" <?php html_attributes( array( 
	'name' => $name, 'id' => $id, 'value' => $object->$field, 'class' => 'text', 'size' => $size, 'style' => 'width: 100%;'
	)) ?> <?php echo $html ?> <?php echo $validations ?> >