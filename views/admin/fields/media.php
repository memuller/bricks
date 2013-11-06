<?php $field = $object->$field;?>
<input type="hidden" <?php html_attributes(array('name' => $name, 'id' => $id, 'value' => $field)) ?>>
<input type="button" class="upload button media" value='<?php echo $field ? explode('/', $field)[sizeof(explode('/', $field))-1] : 'select...' ?>' id="<?php echo $id.'-button' ?>" style="margin-left: 20px;">