<?php wp_editor($object->$field, $id, array('textarea_name' => $name, 'drag_drop_upload' => true, 
	'media_buttons' => isset($options['media_buttons']) && !$options['media_buttons'] ? false : true,
	'teeny' => isset($options['teeny']) && $options['teeny'] ? true : false,
	'wpautop' => !isset($options['wpautop']) || !$options['wpautop'] ? false : true,
	'tinymce' => array('height' => !isset($options['height']) ? 300 : $options['height'])
)) ;?>