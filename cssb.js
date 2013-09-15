jQuery(function($){
	$.ajax({
		url: cssb_vars.ajax_url,
		type: 'get',
		dataType: 'json',
		data: {
			action: 'cssb_get_share_counters',
			options: cssb_share_options
		},
		success: function(response){
			$('.cssb-share-buttons').html(response.html);
			$('.cssb-share-buttons a, .cssb-share-popup').click(function(){
				var url = $(this).attr('href');
				var title = $(this).attr('title') ? $(this).attr('title') : $(this).text();
				var dims = $(this).attr('data-dims');
				if(dims) dims = dims.split('x');
				var width = dims ? dims[0] : 600;
				var height = dims ? dims[1] : 400;
				var win = cssb_popupwindow( url, 'cssb-social-share', width, height );
				return false;
			});
		}
	});
});

function cssb_popupwindow(url, title, w, h) {
	
    var dualScreenLeft = window.screenLeft != undefined ? window.screenLeft : screen.left;
    var dualScreenTop = window.screenTop != undefined ? window.screenTop : screen.top;

    var left = ((screen.width / 2) - (w / 2)) + dualScreenLeft;
    var top = ((screen.height / 2) - (h / 2)) + dualScreenTop;
    var newWindow = window.open(url, title, 'toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=no, resizable=no, copyhistory=no, width=' + w + ', height=' + h + ', top=' + top + ', left=' + left);

    // Puts focus on the newWindow
    if (window.focus) {
        newWindow.focus();
    }
    
    return newWindow;
} 