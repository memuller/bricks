<?php if(isset($custom_single)){?>
	<input type="hidden" name="custom_single" value="<?php $custom_single = explode('\\', $custom_single); echo $custom_single[sizeof($custom_single)-1] ?>">
<?php } ?>