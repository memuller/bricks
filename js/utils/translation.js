jQuery(document).ready( function($) {
	$('#normal-sortables').hide();
	$('.nav-tabwrapper').on('click', 'a', function(event){
		$this = $(this); event.preventDefault();
		if(!$this.hasClass('nav-tab-active')){
			$($this.attr('href')).show().siblings().hide();
			$this.parent().children().removeClass('nav-tab-active');
			$this.addClass('nav-tab-active');
		}
	});
	$('#tabs :first').siblings().hide();
});