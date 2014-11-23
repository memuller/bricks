<?php $filename = explode('/', $value); $filename = $filename[sizeof($filename)-1] ; ?>
<input type="hidden" <?php html_attributes(array('name' => $name, 'id' => $id, 'value' => $value)) ?>>
<input type="button" class="upload button media" value='<?php echo $value ? $filename : 'select...' ?>' id="<?php echo $id.'-button' ?>" style="margin-left: 20px;">