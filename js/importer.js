jQuery(document).ready(function($) {  
    var has_box = false;
    var conf_class = 'submit_conf';
    var vid_count = 0;
    var vid_successes = 0;
    var vid_failures = 0;
    var idsPerPage = 20;
    var vid_amount_per_group = 5;
    var list_matches = ['channel', 'user', 'playlist'];
    var num_complete;
    var list_count;
    var vids_to_process;
    var calls_required;
    var url;
    
    var submit_id = 'the_submit';
    var video_link = 'video_link';
    
    var finished = 1;
    
    // Video id processing vars
    // var vid_id_loops_done;
    // var vid_id_loops_needed;
    
    // Filter variables
    var min_date_filter;
    var max_date_filter;
    var vid_skipped = 0;
    
    $('#' + video_link).change(function(e) {
        var url_regex = new RegExp('(?:https?:\/\/)?(?:www\.)?(?:youtube\.com\/|youtu\.be\/)(channel|user|playlist)');
        url = $('#' + video_link).val();
        var match = url_regex.test(url);
        var container = 'the_submission';
        var cb_id = 'cb_confirm';
        
        // Renable submit if complete

                
        
        // Remove old import if still around
        $('#import_info').fadeOut(500, function() { $(this).remove(); } );
        
        // Uncheck box and remove importing information every time link changes
        $('#' + cb_id).attr('checked', false);
        
        // Check what url is inside, if detected a playlist, channel, or user
        if (match && !has_box) {
            // Force confirm checkbox, gray out submit until checkbox checked
            add_confirmation_box(container, cb_id, submit_id);
        } else if (!match) {
            // if not playlist, channel, or user
            // Remove checkbox and warning text
            $('.' + conf_class).fadeOut(500, function() { $(this).remove(); });
            has_box = false;
            submit_toggle_enable(submit_id, true);
        }
    });
    
    /**
     * Adds a confirmation box to the selected container
     * 
     * @param container
     * @param cb_id
     * @param submit_id
     * @returns void
     */
    function add_confirmation_box(container, cb_id, submit_id) {
        $('#' + container).after('<div class="' + conf_class + '" style="margin-top:5px;">' +
            'Import Videos Between <input type="text" id="since_datepicker" size=10> and \n\
            <input type="text" id="to_datepicker" size=10>\n\ <b>Format</b>: YYYY-MM-DD' + 
            '<br>\t* Just leave one or both fields blank if you do not want a beginning and/or ending date' +
            '<p><input type="checkbox" id="' + cb_id + '" >' +
            'Link will be importing a playlist potentially with many \n\ ' +
            ' videos. It may take a while and slow down your website while\n\
             all the data is processing. Are you still ready to import all the posts?\n\
             Check the box to confirm.</p></div>');
                
        // Datepicker
        $('#since_datepicker').datepicker({dateFormat: 'yy-mm-dd'});
        $('#to_datepicker').datepicker({dateFormat: 'yy-mm-dd'});
        
        // Unblock submit button if checked
        require_checkbox(cb_id, submit_id);
        has_box = true;
    }
    
    /**
     * Locks the submit button submit_id so that
     * it cannot be submited unless cb_id is checked.
     * And tells it to check whenever cb_id's value changes
     * 
     * @param cb_id
     * @param submit_id
     * @returns void
     */
    function require_checkbox(cb_id, submit_id) {
        // Requests that $cb_id be checked in order to allow submit
        // replacing the default submit function
        $('#' + cb_id).change(function (e) {
            var val = $('#' + cb_id).prop('checked');
            submit_toggle_enable(submit_id, val);
        });
        
        submit_toggle_enable(submit_id, false);
    }
    
    /**
     * Toggles whether or not the submit button is 
     * disabled or not
     * 
     * @param submit_id - string
     * @param is_on - boolean
     * @returns void
     */
    function submit_toggle_enable(submit_id, is_on) {
        if (is_on) {
            $('#' + submit_id).fadeTo(500, 1);
         
            //if (displayed_html === 0) {  
               $('#' + submit_id).click(function(e) {
                   if (finished === 1) {
                       import_videos();
                       
                       // Remove confirmation box
                       $('.' + conf_class).fadeOut(500, function() { $(this).remove(); });
                       has_box = false;
                       
                       // Disable
                       $('#' + submit_id).fadeTo(500, 0.2).unbind("click");
                       finished = 0;
                       // displayed_html = 1;
                   }
               });
         //  }
        } else {
            $('#' + submit_id).fadeTo(500, 0.2).unbind("click");
        }
    }  
    
    
    /**
     * Toggle input disable
     * 
     * @param string input_id - minus the id #
     * @param boolean enabled - set the field to enabled or disabled?
     */
    function toggle_input_disable(input_id, enabled) {
        $('#' + input_id).prop('disabled', enabled);
    }
 
    function import_videos() {
        reset_counters();
        importer_html_append();
        toggle_input_disable(video_link, true);

        // Step 1: Acquire Parse URL if necessary
        $.post(ajaxurl,
            {'action' : 'fetch_url_details',
             'url'    : url,
             'security' : ytvi_data.nonce },
            function(parse_data) {
                var json_data = JSON.parse(parse_data);
                
                min_date_filter = $('#since_datepicker').val();
                max_date_filter = $('#to_datepicker').val();
                
                if (error_check(json_data['errors'])) return;
                
                // Update Progress Bar
                update_id_bar(25);
               
                // Determine query information
                if (json_data[2] !== null){
                    var type = json_data[1];
                    var id = json_data[2];
                    
                    // Proceed - One ID or Playlist
                    if (list_matches.indexOf(type) > -1){
                        // Get Playlist / Channel Playlist
                        fetch_playlist_details(json_data);
                    } else {
                        // Single Video
                        update_id_bar(100);
                        fetch_video(id);
                    }
                } else {
                    // Error
                    $('#ytpi-debuglist').append('Was not able to properly parse URL string');
                }
            });
    }
    
    /**
     * For one video ID, Import video into WP Database as post
     * and return success/failure
     * 
     * @param id - Single video id
     * @returns {undefined}
     */
    function fetch_video(id) {
       num_complete = 0;
       
       $.post(ajaxurl,
            {'action' : 'store_videos_by_id',
             'id' :  id,
             'security' : ytvi_data.nonce},
            function (raw_json) {
                var json_data = JSON.parse(raw_json);
                
                // Get results - number of videos -> posts
                vid_successes += json_data['successes'];
                vid_skipped += json_data['skipped'];
                vid_failures += json_data['failures'];
                vid_count += vid_successes + vid_failures;
                
                if (error_check(json_data['errors'])) return;
                
                // Update debug and progress bar
                num_complete++;
                update_debug_info();
                update_import_bar(100);
                
                // Reenable form for another submit
                toggle_input_disable(video_link, false);
            }             
        );
    }
    
    /**
     * Using AJAX get video snippets 10 at a time while
     * updating the video import progress bar respectively
     * 
     */
    function fetch_videos(playlist_id) {
        num_complete = 0;
        
        // Break video_ids into 10 video components, process each group until done
        fetch_video_groupings(playlist_id);
    }
   
    /**
     * Makes AJAX calls to the youtube API to get and store Youtube videos
     * as posts until the list is finished.
     * 
     * @param playlist_id - Id of playlist to import videos from
     * @param next_page_token - Optional page token of next batch of videos,
     * should start null or ''https://www.youtube.com/playlist?list=PLQwBtRZtzFk-p0Diy1uENIQQ2T0NvMKnP
     */
   function fetch_video_groupings(playlist_id, next_page_token) {
        if (next_page_token === '' || typeof next_page_token === 'undefined'){
            var args = {'action' : 'store_videos_by_playlist_id',
             'playlist_id' :  playlist_id,
             'min_date' : min_date_filter,
             'max_date' : max_date_filter,
             'per_page' : vid_amount_per_group};
        } else {
            var args = {'action' : 'store_videos_by_playlist_id',
             'playlist_id' :  playlist_id,
             'min_date' : min_date_filter,
             'max_date' : max_date_filter,
             'per_page' : vid_amount_per_group,
             'next_page_token' : next_page_token};
        }
        
        $.post(ajaxurl,
            $.extend(args, { 'security' : ytvi_data.nonce }), // Up to
            function (raw_json) {
                var json_data = JSON.parse(raw_json);
                
                // Get results - number of videos -> posts
                var num_loaded  = json_data['successes'];
                vid_successes += num_loaded;
                
                var num_failed = json_data['failures'];
                vid_failures += num_failed;
                
                var num_skipped = json_data['skipped'];
                vid_skipped += num_skipped;
                
                vid_count = vid_successes + vid_failures + vid_skipped;
                
                if (error_check(json_data['errors'])) return;
                
                // Update debug and progress bar
                update_debug_info(); 
                update_import_bar(Math.floor(vid_count / list_count * 100));
                var next_page_token = json_data['next_page_token'];

                if (next_page_token.length > 0) {
                    // Recursively call until all groups of videos are done
                    fetch_video_groupings(playlist_id, next_page_token);
                } else {
                     // TODO: Progress bar 100%, display finished message!
                    update_import_bar(100);
                    
                    // Reenable input for another submit
                    toggle_input_disable(video_link, false);
                }
            }             
        );
    }
    
    function fetch_playlist_details(start_data) {
        $.post(ajaxurl,
        {'action' : 'fetch_playlist_details',
         'type' : start_data[1],
         'id' : start_data[2],
         'security' : ytvi_data.nonce},
         function(raw_data) {
             var json_data = JSON.parse(raw_data);
             if (error_check(json_data['errors'])) return;
              
             // Use count to determine progress bar
             list_count = json_data['count'];
             update_id_bar(100);
             
             fetch_videos(json_data['playlist_id']);
         });
    }
   
        
    /**
     * Adds all the importer details after a user
     * presses the submit button. Includes
     * progress bars and debug information.
     */
    function importer_html_append() {
        $('#importer').after().append(
            '<div id="import_info" style="display:none;"><p>Please be patient while videos import from Youtube and do not navigate away from this page. Thank you!</p>'
            + '<h4>Video ID PrepWork</h4>'
            + '<div id="video_ids_progress_bar" style="position:relative;height:25px;margin-right:25px">'
            + '<div id="video_ids_progress_bar_percent" style="position:absolute;left:50%;top:50%;width:300px;margin-left:-150px;height:25px;margin-top:-9px;font-weight:bold;text-align:center;" /></div>'
            + '<h4>Post Import Progress</h4>'
            + '<div id="import_progress_bar" style="position:relative;height:25px;margin-right:25px">'
            + '<div id="import_progress_bar_percent" style="position:absolute;left:50%;top:50%;width:300px;margin-left:-150px;height:25px;margin-top:-9px;font-weight:bold;text-align:center;" /></div>'
            + '<h3 class="title">Debugging Information</h3>'
            + '<ul>'
            +   '<li>Total Youtube Videos: <span id="span_vid_count">' + vid_count + '</span><li>'
            +   '<li>Videos Imported: <span id="span_vid_successes">' + vid_successes + '</span><li>'
            +   '<li>Import Failures: <span id="span_vid_failures">'+ vid_failures + '</span><li>'
            +   '<li>Videos Skipped: <span id="span_vid_skipped">' + vid_skipped + '</span><li>'
            + '</ul>'
            + '<ol id="ytpi-debuglist">'
            + '<li style="display:none"></li>'
            + '</ol></div>');
            
            $('#import_info').fadeIn(500);

            // Create the progress bars
            $("#video_ids_progress_bar").progressbar();
            $("#import_progress_bar").progressbar();
            
            update_id_bar(0);
            update_import_bar(0);
    }
    
    /**
     * Check for error messages or undefined results.
     * Break out of function 
     * 
     * @param errors - errors returned from the function
     * @returns boolean true if error, false if none found
     */
    function error_check(errors) {
        if (typeof errors === 'undefined' || errors.length <= 0) {
            return false;
        } else {
            $.each(errors, function (i, v) {
               // Output the error
                $('#ytpi-debuglist').append("<li><u><b>Error</b></u> " + ' ');
                if (v.reason)    $('#ytpi-debuglist').append('<b>Reason</b>: ' + v.reason + '<br>'); 
                if (v.message)   $('#ytpi-debuglist').append('<b>Message</b>: ' + v.message + '<br>'); 
                if (v.location)  $('#ytpi-debuglist').append('<b>Location</b>: ' + v.location + '<br>'); 
                $('#ytpi-debuglist').append("</li>");
                vid_failures++;
            });
            
            update_debug_info();
            alert('Errors returned. Stopping script.');
            return true; 
        }
    }
    
    /**
    * Reset the javascript counter variables to 0
    */
    function reset_counters() {
        vid_count = 0;
        vid_successes = 0;
        vid_failures = 0;
        vid_skipped = 0;
        num_complete = 0;
        list_count = 0;
        vids_to_process = 0;
        calls_required = 0;
    }
    
    function update_debug_info() {
        // Update debug
        $('#span_vid_count').text(vid_count);
        $('#span_vid_successes').text(vid_successes);
        $('#span_vid_failures').text(vid_failures);
        $('#span_vid_skipped').text(vid_skipped);
    }
        
    function update_id_bar(percent) {
        $("#video_ids_progress_bar").progressbar("option", "value", percent);
        $("#video_ids_progress_bar_percent").html(percent + "%");
    }

    function update_import_bar(percent) {
        $("#import_progress_bar").progressbar("option", "value", percent);
        $("#import_progress_bar_percent").html(percent + "%");
        
        if (percent >= 100 && finished !== 1) {
            // Finished successfully message
            $('#ytpi-debuglist').append('<li>Import Finished!</li>');
            finished = 1;
        }
    }
 });