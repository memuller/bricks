jQuery(document).ready(function ($) {
	$('.upload.media.button').on('click', function(event){
		console.log('=D');
		var $this = $(this); event.preventDefault();
		var file_frame = wp.media.frames.file_frame = wp.media({
			multiple: false
		});

		file_frame.on('select', function(){
			var attachment = file_frame.state().get('selection').first().toJSON();
			var $field = $('#'+$this.attr('id').replace('-button', ''));
			
			$field.val(attachment.url) ;
			$this.val(attachment.filename);
		});
		return file_frame.open();
	});
});