<?php $options['type'] = $options['of'];
	$values = $object->$field; $i = 0;
	$base_name = $name ; $base_id = $id; 
	while($i < (sizeof($values) > 0 ? sizeof($values) : 1 )){
		$name = $base_name.'[]';
		$id = $base_id."_$i";
		$value = $values[$i];
		echo "<div>";
		require($options['type'].'.php');
		echo "</div>";
		$i++;
	}
	?>
<input type="button" class='add button multiple <?php echo $options["type"]; ?>' value='+' id="" style="" 
data-target="<?php echo $id; ?>">