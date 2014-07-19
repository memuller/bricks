<?php wp_editor($object->$field, $id, array('name' => $name, 'textarea_rows' => 15, 'drag_drop_upload' => true, 
	'media_buttons' => isset($options['media_buttons']) && !$options['media_buttons'] ? false : true,
	'teeny' => isset($options['teeny']) && $options['teeny'] ? true : false
)) ;?>