<?php $field = $object->$field; $filename = explode('/', $field); $filename = $filename[sizeof($filename)-1] ; ?>
<input type="hidden" <?php html_attributes(array('name' => $name, 'id' => $id, 'value' => $field)) ?>>
<input type="button" class="upload button media" value='<?php echo $field ?  : 'select...' ?>' id="<?php echo $id.'-button' ?>" style="margin-left: 20px;">