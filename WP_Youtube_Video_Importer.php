<?php
    /**
     * Plugin Name: WP Youtube Video Importer
     * Plugin URI: http://chrisnavarre.com/wordpress/plugins/ytvi
     * Description: Automatically generate posts from Youtube videos, channels, and playlists including optional descriptions and thumbnails
     * Version: 1.0.4
     * Author: Christopher Navarre
     * Author URI: http://chrisnavarre.com
     * License: GPL2
     */

    /*  Copyright 2015  Christopher Navarre  (email : chris@chrisnavarre.com)

		This program is free software; you can redistribute it and/or modify
		it under the terms of the GNU General Public License, version 2, as
		published by the Free Software Foundation.
		
		This program is distributed in the hope that it will be useful,
		but WITHOUT ANY WARRANTY; without even the implied warranty of
		MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
		GNU General Public License for more details.
		
		You should have received a copy of the GNU General Public License
		along with this program; if not, write to the Free Software
		Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
	*/

	defined('ABSPATH') or exit;
			
	register_uninstall_hook	 (__FILE__, array( 'WP_Youtube_Video_Importer', 'on_uninstall' ));
	
	// Initialize Plugin
	add_action( 'plugins_loaded', array( 'WP_Youtube_Video_Importer', 'init' ) );
	
	class WP_Youtube_Video_Importer
	{
		protected static $instance;
		protected $settings_preparer;
                protected $youtube_client;
	
		public static function init()
		{
			is_null( self::$instance ) AND self::$instance = new self;
			return self::$instance;
		}
		
		function __construct() {
			include 'php/Settings_Preparer.php';
			include 'php/Youtube_API_Client.php';
                        
			$this->settings_preparer = new Settings_Preparer();
                        $this->youtube_client = new Youtube_API_Client();
                        
			add_action('admin_menu', array($this, 'register_settings_page'));
			add_action('admin_menu', array($this, 'register_importer_page'));
                        add_action('admin_menu', array($this, 'register_donation_page'));
                        add_action('admin_menu', array($this, 'register_help_page'));
                        add_action('wp_ajax_fetch_video_ids', array($this,'fetch_video_ids'));
                        add_action('wp_ajax_store_videos_by_playlist_id', array($this,'store_videos_by_playlist_id'));
                        add_action('wp_ajax_fetch_url_details', array($this,'fetch_url_details'));
                        add_action('wp_ajax_fetch_playlist_details', array($this, 'fetch_playlist_details'));
                        add_action('wp_ajax_store_videos_by_id', array($this,'store_videos_by_id'));
                }
		
		function register_settings_page() {
			add_menu_page('WP Youtube Video Importer',
			'WP Youtube Video Importer',
			'administrator',
			'ytvi_options',
			array($this, 'get_settings_page')
			);
		
			add_submenu_page('ytvi_options',
			'Settings',
			'Settings',
			'administrator',
			'ytvi_options' );
		}
		
		function register_importer_page() {
                    add_submenu_page('ytvi_options',
                        'Import Youtube Videos',
                        'Import Youtube Videos',
                        'administrator',
                        'ytvi_importer',
                        array($this,'get_importer_page')
                        );
		}
                
                function register_donation_page() {
                    // TeeHee :D
                    add_submenu_page('ytvi_options',
                            'Donate',
                            'Donate',
                            'administrator',
                            'ytvi_donate',
                            array($this, 'get_donation_page')
                            );
                }
                
                function register_help_page() {
                    add_submenu_page('ytvi_options',
                            'Help',
                            'Help',
                            'administrator',
                            'ytvi_help',
                            array($this, 'get_help_page')
                            );
                }
		
		/**
		 * Create a settings page object and display it's form
		 */
		function get_settings_page() {
			include 'php/Settings_Page.php';
				
			$page = new Settings_Page();
			$page->display_settings_form();
		}
		
		/**
		 * Create an importer page and display it's form
		 */
		function get_importer_page() {
			include 'php/Video_Importer_Page.php';
				
			$page = new Video_Importer_Page();
			$page->display_importer();
		}
                
                /**
                 * Hey! - Developers need to eat too.
                 * 
                 * Creates a Donation Page object
                 * and displays the page
                 */
                function get_donation_page() {
                    include 'php/Donation_Page.php';
                    
                    $page = new Donation_Page();
                    $page->display_donate_page();
                }
                
                /**
                 * Display help/faq page
                 */
                function get_help_page() {
                    include 'php/Help_Page.php';
                    
                    $page = new Help_Page();
                    $page->display_help_page();
                }
		
		static function on_uninstall() {
			
			if ( ! current_user_can( 'activate_plugins' ) )
				return;
			
			// Important: Check if the file is the one
			// that was registered during the uninstall hook.
			if ( __FILE__ != WP_UNINSTALL_PLUGIN )
				return;
				
			if (!empty($option_name = get_option('ytvi_options'))) {
				delete_option($option_name);
			}
		}
             
            /**
             * Given a url, figure out what type of data was being provided
             * and what methodology for querying and proccessing will be needed
             * to used.
             * 
             * @return array Returns the parsed URL information json_encdoed or
             *               echo an error message.
             */
            public function fetch_url_details() {
                $url = filter_input(INPUT_POST, 'url', FILTER_SANITIZE_URL);

                if (!empty($url)) {
                    echo json_encode($this->youtube_client->parse_url($url));
                } else {
                    echo ['errors' => ['error' => ['message' => 'Empty URL, Can\'t parse nothing.', "location" => 'URL', 'reason' => 'Empty input box most likely']]];
                }

                die();
            }   
             
            /**
            * For channels/users/playlists get the number of videos in the list
            * to check progress against
            */
            public function fetch_playlist_details() {
               $type = filter_input(INPUT_POST, 'type', FILTER_SANITIZE_SPECIAL_CHARS);
               $id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_SPECIAL_CHARS); 
                
               echo json_encode($this->youtube_client->get_playlist_details($type, $id));
               
               die();
            }
                
             /**
             * AJAX Callback to get Video ids using
             * URL from the Google Youtube API. Grabs
             * url from post data
             */
            public function fetch_video_ids() {
                $playlist_id = filter_input(INPUT_POST, 'playlist_id', FILTER_SANITIZE_SPECIAL_CHARS);
                $nextPageToken = filter_input(INPUT_POST, 'nextPageToken', FILTER_SANITIZE_SPECIAL_CHARS);
                $perPage = filter_input(INPUT_POST, 'perPage', FILTER_SANITIZE_NUMBER_INT);
                    
                echo $this->youtube_client->get_next_video_ids($playlist_id, $nextPageToken, $perPage);
                
                die();
            }
            
            /**
             * Uses the Youtube API Client to query Googles Youtube APIs for
             * video data (snippets) by individual video IDs. When data is recieved, attempts
             * to filter unwanted videos out based on specified min/max dates.
             * Videos that make it through the filter(s) will store in the database
             * as posts based on the plugin settings
             * 
             * Called as AJAX
             */
            public function store_videos_by_id() {
                $video_ids = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_STRING);
                $min_date = filter_input(INPUT_POST, 'min_date', FILTER_SANITIZE_STRING);
                $max_date = filter_input(INPUT_POST, 'max_date', FILTER_SANITIZE_STRING);
                $min_filter_time = strtotime($min_date);
                $max_filter_time = strtotime($max_date);
                
                $responses = $this->youtube_client->query_video_snippets_by_ids($video_ids);
                
                $successes = 0;
                $failures = 0;
                $skipped = 0;
                
                include 'php/Video.php';
                
                // Attempt to process each video
                if (isset($responses['raw_videos'])){
                    foreach ($responses['raw_videos'] as $raw_video) {
                        $this->process_video($raw_video, $successes, $failures, $skipped,
                                $min_filter_time, $max_filter_time);
                    }
                }
                
                // Def. Errors arr
                if (!isset($responses['errors'])) {
                    $responses['errors'] = [];
                }
                
                echo json_encode(["successes" => $successes, "failures" => $failures, "skipped" => $skipped, "errors" => $responses['errors']]);
                die();
            }
            
           /**
             * Uses the Youtube API Client to query Googles Youtube APIs for
             * video data (snippets) by a playlist ID. When data is recieved, attempts
             * to filter unwanted videos out based on specified min/max dates.
             * Videos that make it through the filter(s) will store in the database
             * as posts based on the plugin settings
             * 
             * Called as AJAX
             */
             public function store_videos_by_playlist_id() {
                $playlist_id = filter_input(INPUT_POST, 'playlist_id', FILTER_SANITIZE_STRING);
                $next_page_token = filter_input(INPUT_POST, 'next_page_token', FILTER_SANITIZE_STRING);
                $per_page = filter_input(INPUT_POST, 'per_page', FILTER_SANITIZE_NUMBER_INT);
                $min_date = filter_input(INPUT_POST, 'min_date', FILTER_SANITIZE_STRING);
                $max_date = filter_input(INPUT_POST, 'max_date', FILTER_SANITIZE_STRING);
               
                $successes = 0;
                $failures = 0;
                $skipped = 0;
                
                include 'php/Video.php';
                
                $min_filter_time = strtotime($min_date);
                $max_filter_time = strtotime($max_date);
                
                 // Reverse for post time order
                $responses = array_reverse($this->youtube_client->query_video_snippets_by_playlist_id($playlist_id, $next_page_token, $per_page));
                
                // Attempt to process each video
                if (isset($responses['raw_videos'])){
                    foreach ($responses['raw_videos'] as $raw_video) {
                        $this->process_video($raw_video, $successes, $failures, $skipped,
                                $min_filter_time, $max_filter_time);
                    }
                }
                
                // Def. Errors arr
                if (!isset($responses['errors'])) {
                    $responses['errors'] = [];
                }
                
                // Def. NPT
                if(!isset($responses['next_page_token'])) {
                    $responses['next_page_token'] = '';
                }
                
                echo json_encode(["successes" => $successes, "failures" => $failures, "skipped" => $skipped, 
                    "next_page_token" => $responses['next_page_token'], "errors" => $responses['errors']]);
                die();
            }
            
            /**
             * Attempts to process videos into the database. Filters any videos
             * not within the min/max date arguements.
             * 
             * @param array $raw_vid - The array containing the video snippet details to be processed
             * @param int $successes - If a video is successfully stored into the DB, successes will increment
             * @param int $failures - If a video fails to insert into the DB, failures will increment.
             * @param int $skipped - If a video is filtered, skipped will increment.
             * @param long $min_filter_time - Starting date for videos to actually store
             * @param long $max_filter_time - Ending date for videos to actually store
             */
            function process_video($raw_vid, &$successes, &$failures, &$skipped, 
                $min_filter_time, $max_filter_time) {
                $should_import = $this->filter_videos($raw_vid, $skipped, $min_filter_time, $max_filter_time);
                
                if($should_import) {
                    $video = new Video($raw_vid);
                    $success = $video->store_post();
                    
                    if($success > 0) {
                        $successes++;
                    } else {
                        $failures++;
                    }
                }
            }
            
            /**
             * Skips videos from adding to the database based on minimum and 
             * maximum filter dates.
             * 
             * @param array $raw_vid - Part of response containing video details (i.e. snippet)
             * @param int $skipped - Track skipped variable - how many videos were skipped due to filters
             * @param long $min_filter_time - Any video with an updated date lower than this will be skipped
             * @param long $max_filter_time - Any video with an updated date higher than this will be skipped
             * @return boolean - Whether a video is to be skipped or not
             */
            function filter_videos($raw_vid, &$skipped, $min_filter_time, $max_filter_time) {
                $unconverted_time = substr($raw_vid['snippet']['publishedAt'], 0, 10);
                $published_time = strtotime($unconverted_time);
                

                if ($published_time < $min_filter_time) {
                    $skipped++;
                    return false;
                } else if ($max_filter_time !== false && $published_time > $max_filter_time) {
                    $skipped++;
                    return false;
                } else {
                    return true;
                }
            }
	}
	
		