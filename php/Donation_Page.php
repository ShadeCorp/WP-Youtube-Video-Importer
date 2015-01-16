<?php

defined('ABSPATH') or exit;

/**
 * A donation page object allows you to display
 * the donation page using the donate.css for styles
 *
 * @author Christopher Navarre
 */
class Donation_Page {
    function __construct(){
        wp_enqueue_style('DonateStyles', plugins_url('../css/donate.css', __FILE__));
    }
    
    function display_donate_page() {
        ?>
        <div id='donate_container'>
            <?php echo '<img src="' . plugins_url('../img/snapshot-180.jpg', __FILE__) . '" class="ytvi_profile_image" />'; ?>
            
            <h1>Did I save you time and trouble?</h1>
            <p>If you found my plugin to be a useful time saver then it would be great if you could return the favor. 
            Thanks for using my work and checkout <a href=http://chrisnavarre.com>http://chrisnavarre.com</a> for future projects and updates.</p>
            <form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_top">
            <input type="hidden" name="cmd" value="_s-xclick">
            <input type="hidden" name="encrypted" value="-----BEGIN PKCS7-----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-----END PKCS7-----
            ">
            <input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donateCC_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
            <img alt="" border="0" src="https://www.paypalobjects.com/en_US/i/scr/pixel.gif" width="1" height="1">
            </form>
         </div>  
        <?php
    }
}
