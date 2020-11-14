jQuery(function($) {
    
    // on upload button click
	$('body').on( 'click', '.wpjb-config-image-upload', function(e){
 
		e.preventDefault();
 
		var button = $(this),
		custom_uploader = wp.media({
			title: 'Choose image',
			library : {
				// uploadedTo : wp.media.view.settings.post.id, // attach to the current post?
				type : 'image'
			},
			button: {
				text: 'Use this image' // button label text
			},
			multiple: false
        }).on('select', function() { // it also has "open" and "close" events

            var field_name = button.data( "name" );

            var attachment = custom_uploader.state().get('selection').first().toJSON();
            $("#" + field_name + "_image_holder").attr( "src", attachment.url).show();
            $("#" + field_name ).val( attachment.id );
            $("#" + field_name + "_remove_btn" ).show();
            $("#" + field_name + "_upload_btn" ).hide();
            

			//button.html('<img src="' + attachment.url + '">').next().val(attachment.id).next().show();
		}).open();
 
	});
 
	// on remove button click
	$('body').on('click', '.wpjb-config-image-remove', function(e){
 
		e.preventDefault();
 
        var button = $( this );
        var field_name = button.data( "name" );

        $("#" + field_name ).val( '' );
        $("#" + field_name + "_remove_btn" ).hide();
        $("#" + field_name + "_upload_btn" ).show();
        $("#" + field_name + "_image_holder").hide();
	});

	jQuery('.wpjb-color-picker').each(function(o) {
		var colorPicker = jQuery(this);
		var colorPickerInput = jQuery(this).find('input');
		var colorPickerPreview = jQuery(this).find('.wpjb-colorpicker-preview')
		jQuery(this).ColorPicker({
				livePreview: true,
				color: '#0000ff',
				onShow: function (colpkr) {
					jQuery(colpkr).fadeIn(500);
					return false;
				},
				onHide: function (colpkr) {
					jQuery(colpkr).fadeOut(500);
					return false;
				},
				onChange: function (hsb, hex, rgb) {
					jQuery(colorPickerInput).val('#' + hex);
					jQuery(colorPickerPreview).css("background-color", "#" + hex);
				}
			});
	});
});