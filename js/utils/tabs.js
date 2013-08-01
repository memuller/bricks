jQuery(function($) {
	$('.nav-tabwrapper').on('click', 'a', function(event){
		$this = $(this); event.preventDefault();
		if(!$this.hasClass('nav-tab-active')){
			$($this.attr('href')).show().siblings().hide();
			$this.parent().children().removeClass('nav-tab-active');
			$this.addClass('nav-tab-active');
			if($('#_revision_post_format').is('*')){
				$('#_revision_post_format').val($(this).html().toLowerCase());
			}
		}
	});
	$('#tabs :first').siblings().hide();
	if($('#_revision_post_format').is('*')){
		target = '.nav-tabwrapper a.'+$('#_revision_post_format').val() ;
		console.log(target);
		$(target).trigger('click');
	}
});