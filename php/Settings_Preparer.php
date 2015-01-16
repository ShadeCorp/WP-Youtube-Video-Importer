<?php
	defined('ABSPATH') or exit;
	
	class Settings_Preparer {
		const DEF_VIDEO_WIDTH = 560;
		const DEF_VIDEO_HEIGHT = 315;
                const DEF_THUMB_FEAT_IMAGE = 'off';
                const DEF_INC_DESCRIPTION = 'below';
                const DEF_PUBLISH_STATUS = 'draft';
                const DEF_EMBED_ALIGN = 'none';
                
                private $defaults = array(
                                'video_width'              =>   self::DEF_VIDEO_WIDTH,
                                'video_height'             =>	self::DEF_VIDEO_HEIGHT,
                                'thumbnail_featured_image' =>   self::DEF_THUMB_FEAT_IMAGE,
                                'inc_description'          =>   self::DEF_INC_DESCRIPTION,
                                'publish_status'           =>   self::DEF_PUBLISH_STATUS,
                                'embed_align'              =>   self::DEF_EMBED_ALIGN,    
			);
		
		function __construct() {
			add_action('admin_init', array($this, 'settings_init'));
		}
		
		/**
		 * Registers YTPI settings with wordpress
		 */
		function settings_init() {
                        // Add options if they dont exist
                        $options = get_option('ytpi_options');
                        
			if (false == $options || empty($options)) {
				add_option('ytpi_options', apply_filters('ytpi_options', $this->default_options()));
			} else {
                            // Individual Option checking
                            $this->options_checking($options);
                        }
			
			register_setting('ytpi_options', 'ytpi_options');
		
			add_settings_section('ytpi_options_section',		  # id
                            'WP Youtube Post Importer Settings',		  # title
                            array($this, 'options_form_header_callback'),  	  # callback
                            'ytpi_options');					  # page
                        
                        add_settings_field(
                                'inc_description',
                                'Include video descriptions with each post?',
                                array($this, 'inc_description_callback'),
                                'ytpi_options',
                                'ytpi_options_section');
                        
                        add_settings_field(
                            'publish_status',
                            'Default publish status?',
                            array($this, 'publish_status_callback'),
                            'ytpi_options',
                            'ytpi_options_section');
                        
                        add_settings_field(
                            'embed_align',
                            'Default embeded video alignment?',
                            array($this, 'embed_align_callback'),
                            'ytpi_options',
                            'ytpi_options_section');
                        
                        
                        add_settings_field(
                            'thumbnail_featured_image',
                            'Include video thumbnails as post featured image? <br><i>(Increases Processing Time)</i>',
                            array($this, 'thumbnail_featured_image_callback'),
                            'ytpi_options',
                            'ytpi_options_section');
		
			add_settings_field(
                            'video_width',
                            'Default Video Width',
                            array($this, 'custom_video_width_callback'),
                            'ytpi_options',
                            'ytpi_options_section');
		
			add_settings_field(
                            'video_height',
                            'Default Video Height',
                            array($this, 'custom_video_height_callback'),
                            'ytpi_options',
                            'ytpi_options_section');

		}
		
		/*
		 * Callback output of form fields for YTPI options
		 */
		function options_form_header_callback() {
			$html = '<h4>Update the settings for your importer.</h4>';
			echo $html;
		}         
                
                function thumbnail_featured_image_callback() {
                    $val = get_option('ytpi_options');
                    
                    $html = "<fieldset id='thumbnail_featured_image'>
			<input type='radio'  name='ytpi_options[thumbnail_featured_image]' value='off' " 
				. checked($val['thumbnail_featured_image'], 'off', false) . ">No"
                        . "<input type='radio' name='ytpi_options[thumbnail_featured_image]' value='on' " 
				. checked($val['thumbnail_featured_image'], 'on', false) . ">Yes</fieldset>";
                    
                    echo $html;
                }
                
                function inc_description_callback() {
                   $val = get_option('ytpi_options');
                    
                   $html = "<fieldset id='inc_description' >
			<input type='radio' name='ytpi_options[inc_description]' value='off' " 
				. checked($val['inc_description'], 'off', false) . ">No
			<input type='radio' name='ytpi_options[inc_description]' value='below' " 
				. checked($val['inc_description'], 'below', false) . ">Yes, Below Video
                        <input type='radio' name='ytpi_options[inc_description]' value='above' " 
				. checked($val['inc_description'], 'above', false) . ">Yes, Above Video
			</fieldset>";
                    
                    echo $html;
                }
                
                function publish_status_callback() {
                   $val = get_option('ytpi_options');
                   
                   $html = "<fieldset id='publish_status' >
			<input type='radio'name='ytpi_options[publish_status]' value='publish' " 
				. checked($val['publish_status'], 'publish', false) . ">Publish
			<input type='radio' name='ytpi_options[publish_status]' value='draft' " 
				. checked($val['publish_status'], 'draft', false) . ">Draft
                        <input type='radio' name='ytpi_options[publish_status]' value='private' " 
				. checked($val['publish_status'], 'private', false) . ">Private
			</fieldset>";
                    
                    echo $html;
                }
                
                function embed_align_callback() {
                    $val = get_option('ytpi_options');
                    
                    $html = "<fieldset id='embed_align' >
                            <input type='radio' name='ytpi_options[embed_align]' value='none' " 
                                    . checked($val['embed_align'], 'none', false) . ">None
                            <input type='radio'name='ytpi_options[embed_align]' value='left' " 
                                    . checked($val['embed_align'], 'left', false) . ">Left
                            <input type='radio' name='ytpi_options[embed_align]' value='center' " 
                                    . checked($val['embed_align'], 'center', false) . ">Center
                            <input type='radio' name='ytpi_options[embed_align]' value='right' " 
                                    . checked($val['embed_align'], 'right', false) . ">Right
			</fieldset>";
                    
                    echo $html;
                }
		
		function custom_video_width_callback() {
			$val = get_option('ytpi_options');
		
			$html = "<p><input type='number' name='ytpi_options[video_width]' min='0' max='5000'"
                                . "id='video_width' size='4' type='text' value='" 
                                . $val['video_width'] 
                                . "'/>px</p>";
			echo $html;
		}
		
		function custom_video_height_callback() {
			$val = get_option('ytpi_options');
		
			$html = "<input type='number' name='ytpi_options[video_height]' min='0' max='5000'"
                                . "id='video_height' type='text' value='" 
                                . $val['video_height']
                                . "'/>px</p>";
			echo $html;
		}
		
		/**
		 * Provides default values for the importer options.
		 */
		function default_options() {
			return apply_filters( 'default_options', $this->defaults );
		}
                
                /**
                 * Makes sure that every option
                 * in the ytpi_optiosn array is
                 * set, and sets each option
                 * to the default if it does
                 * not exist.
                 * 
                 * @param array $options - YTPI options as set in the WP Database
                 * @return array - containing the original options plus any
                 *     missing options
                 */
                function options_checking(&$options) {
                    $keys = array_keys($this->defaults);
                    
                    foreach($keys as $key) {
                        if (!in_array($key, $options)) {
                            // Not set, make default
                            $options[$key] = $this->defaults[$key];
                        }
                    }
                }
		
		function options_validation($input) {
			foreach( $input as $key => $value ) {
		        $input[$key] = apply_filters( 'my_validation_' . $key, $value );
		    }
		
		    return $input;
		}
	}