<input type="checkbox" <?php html_attributes( array( 
	'name' => $name, 'id' => $id, 'class' => 'text', 'value' => 1 
	)) ?> <?php echo $value ? 'checked' : '' ?> <?php echo $html ?> <?php echo $validations ?> > 