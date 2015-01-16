<?php
	defined('ABSPATH') or exit;

	/**
	 * Settings Page contains the form for managing all Youtube Post Importer settings.
	 * The form and all its callbacks are managed in Settings_Preparer.php
	 */
	class Settings_Page {
	
		function __construct() { 
                    wp_enqueue_script(
                        'reset_options',
                        plugins_url('../js/reset_options.js', __FILE__),
                        array('jquery')
                    );
                }
		
		/**
		 * Display Settings Form
		 */
		function display_settings_form() { ?>
			<div id="wrap">
				<?php settings_errors(); ?>
				<div class="icon32" id="icon-tools"> <br /> </div>
				<form method="post" action="options.php" enctype="multipart/form-data">
					<?php settings_fields('ytvi_options');
						do_settings_sections('ytvi_options');
						submit_button(); ?>
				</form>
								<!-- Reset Options Button - Uses reset.js -->
								<input id="reset_button" type="button" value="<?php _e('Reset Options') ?>" class="button button-primary" />
									
			</div><!-- /#wrap -->
			<?php }
                
	}