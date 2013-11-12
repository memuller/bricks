<input type="text" <?php html_attributes( array( 
	'name' => $name, 'id' => $id, 'value' => $object->$field, 'class' => 'text', 'size' => $size, 'style' => $GLOBALS['metabox_placing'] == 'side' ? '' : 'width: 100%;'
	)) ?> <?php echo $html ?> <?php echo $validations ?> >