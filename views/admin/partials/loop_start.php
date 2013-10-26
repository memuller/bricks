<?php foreach ($fields as $field => $options) {
	if($options['hidden'] == true) continue;
	$id = $type.'_'.$field; $name =  $type.'['.$field.']' ; $html = '';
	if(!isset($options['size'])){
		$size = $options['type'] == 'integer' ? 5 : 20;
	} else {
		$size = $options['size'] ;
	};
	if(isset($options['html'])){
		foreach ($options['html'] as $k => $v) {
			$html .= "$k=\"$v\" ";
		}
	}
?>