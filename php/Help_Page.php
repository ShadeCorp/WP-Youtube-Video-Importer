<?php
/**
 * Help Page displays tutorial and FAQ information to the screen for the user when created and 
 * display_help_page() is called
 */
defined('ABSPATH') or exit;

class Help_Page {
    function __construct() {
        wp_enqueue_style('HelpStyles', plugins_url('../css/help.css', __FILE__));
    }
    
    function display_help_page() {
    ?> 
    <div class="help_header help_block"><h1>User Guide</h1></div>
    <div class="help_main_area help_block">
    <p>Plugin is used entirely through the wp-admin interface. You can find
    the links within the Wordpress menu, WP YouTube Video Importer should
    be below the core Wordpress menu items.</p>
    
    <li>Settings Page: Let's you choose the settings for future imports, I recommend you check this before importing</li>
    <li>Import Page: Post a full YouTube video/watch/playlist/channel URL and hit import. Playlists/Channels require checking the confirmation box</li>
    <li>Donate Page: If my plugin helped you save you time and effort, it would be greatly appreciated.</li>
    <li>Help Page: You're looking at it ;). This information is also contained in the readme file.</li>
        
    <p>YouTube Video Importer allows you to import a single video, a playlist, or a channel (which actually grabs the uploaded videos playlist)</p>
    
    <h4>Here are some examples of valid links that you can post into the importer field</h4>
    <ul>
        <li><a href='https://www.youtube.com/watch?v=i6eNvfQ8fTw'>https://www.youtube.com/watch?v=i6eNvfQ8fTw</a></li>
        <li><a href="https://www.youtube.com/channel/UCwdKySs6j48yl1ae8csAurQ">https://www.youtube.com/channel/UCwdKySs6j48yl1ae8csAurQ</a></li>
        <li><a href="https://www.youtube.com/playlist?list=PLyH-qXFkNSxn8iiN2M1rnkpQRDJIGDe6g">https://www.youtube.com/playlist?list=PLyH-qXFkNSxn8iiN2M1rnkpQRDJIGDe6g</a></li>
    </ul>
    <p>If the link is to a playlist/user/channel instead of just a single video, you will be prompted to check a checkbox in order to verify
        that you understand that the plugin will be importing all the videos on the associated playlist, that it may take a while and slowdown your website.
        In addition, there are optional date selection fields if you wish to filter the import data</p>
    <p><b><i>[Minimum Date (Inclusive) on the Left] -- [Maximum Date (Inclusive) on the Right]</b></i></p>
    
    <table>
        <tr><td class='r_align'><b>Successes:</b></td><td>Videos that were successfully imported into individual Wordpress posts with the settings supplied by the options page</td></tr>
        <tr><td class='r_align'><b>Failures:</b></td><td>Videos which could not successfully import into the database.</td></tr>
        <tr><td class='r_align'><b>Skipped:</b></td><td>Videos that were ignored during the import because of a date filter that shows up for playlist imports.</td></tr>
    </table>
    
    <p class='red'>Using just the video/channel id and not the full URL won't work <b>Ex.</b> "i6eNvfQ8fTw"</p>
    
    <p>Before actually attempting to import videos into Wordpress, I recommend backing up your database and checking the plugin settings page to see if there are any 
        modifications you wish to make to the data going into Wordpress.</p></div>
    
    
    <div class="help_header help_block"><h1>FAQ</h1></div>
    <div class="help_main_area help_block">
    <ul>
        <li><h4>Why are some videos from my channel missing after an import?</h4>
            <p>Videos from a channel or user will pull from the uploads playlist which
            may not have every video that your channel hosts. Unfortunately this problem
            lies with YouTube. Double check for any videos missing
            from that playlist that you want uploaded to Wordpress. 
            Private/Draft videos will not be brought in.</p>
        </li>
        <li><h4>Can I bring my video tags into each post?</h4>
            <p>Apparently YouTube thought it would be a good idea to remove
               tags from the unauthenticated API. In a future version, I may
               make it so that you can get the tags only for videos that you
               own by authenticating. <a href="mailto:chris@chrisnavarre.com">Let me know</a> if this is a feature you'd
               really like to see.</p>
        </li>
    </ul>
    </div>
    <?php
    }
}



