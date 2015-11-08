jQuery(document).ready( function($) {
	$('#post').on('click', '.multiple.add', function(event){
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