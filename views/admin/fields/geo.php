<?php $field = $object->$field;?>
<input type="text" <?php html_attributes(array('id' => $id, 'name' => $name."[address]", 'value' => $field['address'], 'class' => 'geo', 'size' => 60)) ?> <?php echo $validations ?> <?php echo $html; ?>>
<div <?php html_attributes(array('id' => $id.'_map', 'class' => 'geo_map', 
	'style' => sprintf("width: %s; height: %s; margin-top: 5px; margin-bottom: 5px;", $options['width'], $options['height']))) ?>></div>
<input class="geo_lat" type='hidden'<?php html_attributes(array('name' => $name."[lat]", 'value' => $field['lat'])) ?>></input>
<input class="geo_lng" type='hidden'<?php html_attributes(array('name' => $name."[lng]", 'value' => $field['lng'])) ?>></input>