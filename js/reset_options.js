jQuery(document).ready(function($) {  
        $('#reset_button').click(function() {
            // Change form options to defaults
            $('#save_notify').remove(); 
            
            // Radio Groups
            $('#inc_description').find('input').filter('[value=below]').prop('checked', true);
            $('#thumbnail_featured_image').find('input').filter('[value=off]').prop('checked', true);
            $('#publish_status').find('input').filter('[value=draft]').prop('checked', true);
            $('#embed_align').find('input').filter('[value=none]').prop('checked', true);

            // Text Inputs
            $('#video_width').val(560);
            $('#video_height').val(315);
            
            $('#reset_button').after('<p id="save_notify">Save changes must still be hit before any changes are finalized</p>');
        }      
    );
});



