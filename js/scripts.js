jQuery(document).ready(function() {

	jQuery('#aus_news_grabber-add-form').submit(function() {
		return false;
	});

	jQuery('#aus_news_grabber-add-channel').on('click', function(e) {

		e.preventDefault();
		var form = jQuery('#aus_news_grabber-add-form');
		var data = form.serialize() + "&action=aus_news_grabber_channel_add";
		var del_nonce = jQuery('#channel_del_nonce').text();

		jQuery.ajax({
			url: ajaxurl,
			type: 'POST',
			data: data,
			cache: false,
			dataType: 'json',
			success: function(data)
			{
				if( data.error == 1 ) {
					console.log(data);
				} else {
					form[0].reset();
					jQuery('.aus-channels-table > tbody:last').append('<tr><td class="aus-channels-table-col1">1</td><td class="aus-channels-table-col2">' + data.channel.grabber + '</td><td class="aus-channels-table-col3">' + data.channel.grabber_cat + '</td><td class="aus-channels-table-col3">' + data.channel.grabber_author + '</td><td class="aus-channels-table-col4">' + data.channel.rss_url + '</td><td class="aus-channels-table-col5"><a class="aus_news_grabber-del-channel" data-nonce="' + del_nonce + '" data-rand_id="' + data.channel.rand_id + '" id="" href="#' + data.channel.rand_id + '">Delete</a></td></tr>');
					console.log(data.channel);
				}
				console.log(data);
			},
			error: function(data)
			{
				console.log(data);
			}
		});

	});

	jQuery('.aus-channels-table').on('click', '.aus_news_grabber-del-channel', function(e) {

		e.preventDefault();

		var data = 'nonce=' + jQuery(this).data('nonce') + '&rand_id=' + jQuery(this).data('rand_id') + '&action=aus_news_grabber_channel_del';
		var tr = jQuery(this).parent().parent();

		jQuery.ajax({
			url: ajaxurl,
			type: 'POST',
			data: data,
			cache: false,
			dataType: 'json',
			success: function(data)
			{
				if( data.error == 1 ) {
					console.log(data);
				} else {
					tr.remove();
					console.log('success');
					console.log(data);
				}
			},
			error: function(data)
			{
				console.log(data);
			}
		});

	});

	jQuery('.image-upload').click(function(e) {
		var image_field = jQuery(this).data('field');
		var custom_uploader;
		e.preventDefault();
		//If the uploader object has already been created, reopen the dialog
		// if (custom_uploader) {
		//     custom_uploader.open();
		//     return;
		// }
		//Extend the wp.media object
		custom_uploader = wp.media.frames.file_frame = wp.media({
			title: 'Choose Image',
			button: {
				text: 'Choose Image'
			},
			multiple: false
		});
		//When a file is selected, grab the URL and set it as the text field's value
		custom_uploader.on('select', function() {
			attachment = custom_uploader.state().get('selection').first().toJSON();
			jQuery(image_field).val(attachment.id);
		});
		//Open the uploader dialog
		custom_uploader.open();
	});

});