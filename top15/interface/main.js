$(function() {
	$('body').on('click', '.selectTop a', function(e) {
		var el_with_primary = $('.selectTop .ipsButton_primary');
		var el_this = $(this);
		var el = $('.nextTop');
		var i;
		var index = $('.nextTop').index(this);

		el_with_primary.removeClass('ipsButton_primary').addClass('ipsButton_light');
		el_this.addClass('ipsButton_primary').removeClass('ipsButton_light');

		for(i = 0; i < el.length; ++i) {
			$('#divTop'+i).hide();
		}

		$('#divTop'+index).fadeIn();
		$('#divTop'+index).show();
	});
});