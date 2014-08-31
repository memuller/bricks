<?php $field = $object->$field; $filename = explode('/', $field); $filename = $filename[sizeof($filename)-1] ; ?>
<input type="hidden" <?php html_attributes(array('name' => $name, 'id' => $id, 'value' => $field)) ?>>
<input type="button" class="upload button media" value='<?php echo $field ? $filename : 'select...' ?>' id="<?php echo $id.'-button' ?>" style="">
<?php if(isset($options['preview']) && $options['preview']): $display = $field? 'display:block;' : 'display:none;'; ?>
	<img <?php html_attributes(array('id' => $id.'-preview', 'class' => '', 
		'src' => $field,
		'style' => "max-width: 85%; margin-top: 1em; $display"  
	));?>>
<?php endif; ?>