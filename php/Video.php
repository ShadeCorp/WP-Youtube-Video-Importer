<?php
    defined('ABSPATH') or exit;

    class Video {
            protected $id;
            protected $title;
            protected $thumb_url;
            protected $content;
            protected $youtube_embed;
            protected $publish;
            

            function __construct($videoResult) {
				$options = get_option('ytpi_options');


				$this->id = $videoResult['id'];
				$this->title = $videoResult['snippet']['title'];
				$this->youtube_embed = '<div align="' . $options['embed_align'] . '"><iframe src="http://www.youtube.com/embed/' 
					. $this->id . '" frameborder="0" width="' . $options['video_width'] . '"height="' .
					$options['video_height'] . '"></iframe></div>';

				// Optionals
				if ($options['inc_description'] === 'below') {
					$this->content = '<p>' . $this->youtube_embed . '</p>' . $videoResult['snippet']['description'];
				} else if ($options['inc_description'] === 'above') {
					$this->content = $videoResult['snippet']['description'] . '<p>' . $this->youtube_embed . '</p>';
				} else {
					$this->content = $this->youtube_embed;
				}

				// Thumbnail Optional
				if ($options['thumbnail_featured_image'] === 'on') {
					$this->thumb_url = $this->find_best_url($videoResult['snippet']['thumbnails']);
				}

				$this->publish = $options['publish_status'];
            }

            function store_post() {
				$post = array(
					'post_title' => $this->title,
					'post_status' => $this->publish,
					'post_content' => $this->content,
				);

				$id = wp_insert_post($post);
				set_post_format($id, 'video');
				
				if ($this->thumb_url !== null) { 
					$this->load_and_attach_thumbnail($this->thumb_url, $id, $this->title);
					
					$hello = 'hello hello hello';
				}

				return $id;
            }
            
            /**
             * Marches through all the snippet
             * video thumbnails and finds
             * the url of the largest one to
             * return
             * 
             * @param array $thumbs - contains thumbnail snippet data
             * @return string $url - of largest thumbnail
             */
            function find_best_url($thumbs) {
                $url;
                $width = 0;
                
                foreach($thumbs as $thumb) {
                    if ($thumb['width'] > $width) {
                        $url = $thumb['url'];
                        $width = $thumb['width'];
                    }
                }
                
                return $url;
            }
            
            /**
             * Gets the image from the thumbnail URL, stores in 
             * in the wordpress media directory, and
             * attaches it to the post referred to by
             * $post_id
             * 
             * @param string $thumb_url - url containing image thumbnail should be loaded from
             * @param int $post_id - id of post to attach to
             * @param string $base_title - optional base of attachment file name
             */
            function load_and_attach_thumbnail($thumb_url, $post_id) {
                $thumb_info = pathinfo($thumb_url);
                
                // Upload image from url
                $upload_dir = wp_upload_dir();
                $image_data = file_get_contents($thumb_url);
                $filename = 'ytpi-' . $post_id . '-thumb.' . $thumb_info['extension'];
                
                if(wp_mkdir_p($upload_dir['path'])) {
                    $file = $upload_dir['path'] . '/' . $filename;
                } else {
                    $file = $upload_dir['basedir'] . '/' . $filename;
                }
                
                file_put_contents($file, $image_data);

                // Create attachment
                $wp_filetype = wp_check_filetype($filename, null );
                
                $attachment = array(
                    'post_mime_type' => $wp_filetype['type'],
                    'post_title' => sanitize_file_name($filename),
                    'post_content' => 'Uploaded by Youtube Post Importer',
                    'post_status' => 'inherit'
                );
                
                // Attach to Post ID
                $attach_id = wp_insert_attachment( $attachment, $file, $post_id );
                require_once(ABSPATH . 'wp-admin/includes/image.php');
                $attach_data = wp_generate_attachment_metadata( $attach_id, $file );
                wp_update_attachment_metadata( $attach_id, $attach_data );

                set_post_thumbnail( $post_id, $attach_id );
            }
    }