<?php $filename = explode('/', $value); $filename = $filename[sizeof($filename)-1] ; ?>
<input type="hidden" <?php html_attributes(array('name' => $name, 'id' => $id, 'value' => $value)) ?>>
<input type="button" class="upload button media" value='<?php echo $value ? $filename :  strtolower(__('Select')).'...' ?>' id="<?php echo $id.'-button' ?>" style="">
<?php if(isset($options['preview']) && $options['preview']): $display = $value? 'display:block;' : 'display:none;'; ?>
	<img <?php html_attributes(array('id' => $id.'-preview', 'class' => '', 
		'src' => $value,
		'style' => "max-width: 85%; margin-top: 1em; $display"  
	));?>>
<?php endif; ?>
