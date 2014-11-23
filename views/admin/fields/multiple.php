<?php $options['type'] = $options['of'];
	$values = $object->$field; $i = 0;
	$base_name = $name ; $base_id = $id; 
	while($i < sizeof($values)){
		$name = $base_name.'[]';
		$id = $base_id."_$i";
		$value = $values[$i];
		echo "<div>";
		require $options['type'].'.php';
		echo "</div>";
		$i++;
	}
	?>
<input type="button" class='add button multiple <?php echo $options["type"]; ?>' value='+' id="" style="" 
data-target="<?php echo $id; ?>">
<script type="text/javascript">
	jQuery(document).ready( function($) {
		$('#post').on('click', '.multiple.add.<?php echo $options["type"] ?>', function(event){
			event.preventDefault();
			$original = $("#"+$(this).data('target'));
			$clone = $original.clone();
			id = $clone.attr('id'); id = id.split("_");  i = parseInt(id[id.length-1]);
			id[id.length-1] = i+1; new_id = id.join("_");
			$clone.attr('id', new_id);
			$clone.val(''); 
			$(this).data('target', new_id);
			clone = "<div>"+$clone.prop('outerHTML')+"</div>";
			$($.parseHTML(clone)).insertAfter($original);
		});
	});
</script>