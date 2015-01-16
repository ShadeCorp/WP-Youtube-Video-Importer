<?php
    defined('ABSPATH') or exit;

    class Video_Importer_Page {
        function __construct() {
            wp_enqueue_script(
                'importer',
                plugins_url('../js/importer.js', __FILE__),
                array('jquery', 'jquery-ui-core', 'jquery-ui-progressbar', 'jquery-ui-datepicker')
            );
            
            $url = "http://ajax.googleapis.com/ajax/libs/jqueryui/1.11.2/themes/redmond/jquery-ui.css";
            wp_enqueue_style('jquery-ui-redmond', $url, false, null);
        }

        function display_importer() { ?>
            <div id="wrap">
                <?php settings_errors(); ?>
                <div class="icon32" id="icon-tools"> <br /> </div>
                <h2>Import Youtube Videos</h2>
                <p>Provide a link to the Youtube video or channel you would like to import to Wordpress.</p>
                <?php $this->display_importer_form() ?>
            </div><!-- /#wrap -->
        <?php }
               
        private function display_importer_form() { ?>
            <form method="" id="importer" action="" enctype="multipart/form-data">
                <?php settings_fields('ytvi_options'); ?>
                <?php do_settings_sections(__FILE__); ?>
                Link to video or channel
                <input type="text" id="video_link" name="video_link">
                <p id="the_submission" class="submit">
                    <input id="the_submit" name="Import" type="button"
                               class="button-primary" style="opacity: 0.2;" value="<?php esc_attr_e('Import Video(s)'); ?>" />
                </p>
            </form>
           <?php
        }
}

