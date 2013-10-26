<textarea <?php html_attributes( array( 
	'name' => $name, 'id' => $id, 'class' => 'text', 'cols' => 50, 'rows' => 3, 'style' => 'width: 100%;' 
	)) ?><?php echo $html ?> <?php echo $validations ?> ><?php echo $object->$field ?>
</textarea>