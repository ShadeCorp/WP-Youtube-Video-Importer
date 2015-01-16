<?php
	defined('ABSPATH') or exit;
        
       class Youtube_API_Client {
		protected $developer_key = "AIzaSyBzttArbg4Dp4dDTehV7fBGBq8MJQkYOM0";
		protected $google_client;
		private $youtube;
                protected $video_ids;

		function __construct() {
				include 'google/src/Google/Client.php';	
			
				$this->google_client = new Google_Client();
				$this->google_client->setApplicationName("WP Youtube Video Importer");
				$this->google_client->setDeveloperKey($this->developer_key);
				$this->youtube = new Google_Service_Youtube($this->google_client);
		}
		
		/**
		 * Parse a url to find the ID and whether we are dealing with
		 * a video, a user, or a channel from youtube
		 * 
		 * @param string $url - URL to break up, should contain video, user, or channel
		 * @return array - link type, id
		 */
		public function parse_url($url) {
			// ^(?:https?:\/\/)?(?:www\.)?(?:youtube\.com\/|youtu\.be\/)(channel|user|v|playlist|watch)(?:\?|\/)?(?:list=|v=|video=)?([\w-]{1,})^
			
			$pattern = '^(?:https?://)?'                          # Optional Scheme http or https
					. '(?:www\.)?'                        # Optional www subdomain
					. '(?:youtube\.com\/'                 # youtube or
					. '| youtu\.be\/)'                    # youtu.be
					. '(channel|user|v|playlist|watch)'   # One of either Channel, user, v, watch
					. '(?:\?|\/)?'                         # Optional ? or /
                                        . '(?:list=|v=|video=)?'               # Optional key labels
					. '([\w-]{1,})^';                     # Video/user/channel ID;	

			$result = preg_match($pattern, $url, $matches);
			
			if ($result == 1) {
				return $matches;
			} else {
				return array("message" => 'Could not match url to a type');
			}
		}
                
                /**
                 * Get the number of items in a given user/channel's upload list,
                 * or in the supplied playlist.
                 * 
                 * @param type $type
                 * @param type $id
                 */
                public function get_playlist_details($type, $id) {
                    // Get playlist ID if needed
                    try {
                        $playlist_id = '';
                        
                        // Get uploads playlist id if needed
                        if ($type === 'channel') {
                            $content_details = $this->youtube->channels->listChannels('contentDetails', array('id' => $id));    
                            $playlist_id = $content_details['modelData']['items']['0']['contentDetails']['relatedPlaylists']['uploads'];
                        } else if ($type === 'user') {
                            $content_details = $this->youtube->channels->listChannels('contentDetails', array('id' => $id));
                            $playlist_id = $content_details['modelData']['items']['0']['contentDetails']['relatedPlaylists']['uploads'];
                        } else if ($type === 'playlist') {
                            $playlist_id = $id;
                        }

                        // Get count of items on playlist
                        if ($playlist_id !== '') {
                            $content_details = $this->youtube->playlists->listPlaylists('contentDetails', array('id' => $playlist_id));
                            $count = $content_details['modelData']['items']['0']['contentDetails']['itemCount'];
                        } else {
                            return ['errors' => ['error' => ['location' => 'count items', 'message' => 'no playlist id', 'reason' => 'bad url']]];
                        }
                        
                        return ["playlist_id" => $playlist_id, "count" => $count]   ;
                    } catch (Google_Service_Exception $ex) {
                        return $ex->getErrors();
                    }
                    
                }
		
		/*
		 * Get proper api link
		 * 
		 * @return array - Key-value pair for id/channel/user
		 */
		public function get_targets_from_url($url) {
			// https://www.youtube.com/watch?v=_n8d_KCUtrs
			// https://www.youtube.com/channel/UCwdKySs6j48yl1ae8csAurQ
			$parsed_data = $this->parse_url($url);
			
			if (empty($parsed_data)) {
				return;
			}
			
			$targets = $this->get_video_targets($parsed_data);
			return $targets;
		}
		
                /**
                 * Uses parsed data to find video ID targets
                 * and return a 'id' => array key
                 * value pair
                 * 
                 * @param  array $parsed_data
                 * @return array id => array of ids key value pair
                 */
		private function get_video_targets($parsed_data) {
                        $upload_ids = '';
                    
			if ($parsed_data[1] == 'channel') {
				// Channel channel
                                $upload_ids = $this->get_upload_ids_from_channel_id($parsed_data[2]);
                                return $upload_ids;
			} else if ($parsed_data[1] == 'user') {
                                $upload_ids = $this->get_upload_ids_from_user($parsed_data[2]);
				return $upload_ids;
			} else if ($parsed_data[1] == 'v' || $parsed_data[1] == 'watch'){
				$upload_ids = $parsed_data[2];
                                return $upload_ids;
			}
		}
                
                /**
                 * Query API to find the uploads playlist of the user,
                 * then query to get the ids of every video, and return
                 * all ids as a comma seperated string
                 * 
                 * @param string channel_id
                 * @return string upload's playlist video ids
                 */
                private function get_upload_ids_from_user($user) {
                    try {
                        $content_details = $this->youtube->channels->listChannels('contentDetails', array('forUsername' => $user));
                    } catch (Google_Service_Exception $ex) {
                               return ['errors' => $ex->getErrors()];
                    }
                    
                    $page_token = '';
                    $upload_ids = array();
                    
                     do {
                        try {
                            $uploads = $this->youtube->playlistItems->listPlaylistItems('contentDetails',
                            array('playlistId' => $content_details['modelData']['items']['0']['contentDetails']['relatedPlaylists']['uploads'],
                                  'maxResults' => 50));
                        } catch (Google_Service_Exception $ex) {
                               return ['errors' => $ex->getErrors()];
                        }
                        
                        $page_token = $uploads['nextPageToken'];
                        $count = count($uploads['modelData']['items']);

                        for ($pos = 0; $pos < $count; $pos++) {
                            // Add each ID to $upload_ids array
                            array_push($upload_ids, $uploads['modelData']['items'][$pos]['contentDetails']['videoId']);
                        }
                    } while ($page_token != '');
                    
                    return $upload_ids;
                }
                
                /**
                 * Query API to find the uploads playlist of the channel,
                 * then query to get the ids of every video, and return
                 * all ids as a comma seperated string.
                 * 
                 * @param string $channel_id
                 * @return string upload's playlist video ids
                 */
                private function get_upload_ids_from_channel_id($channel_id) {
                    $content_details = $this->youtube->channels->listChannels('contentDetails', array('id' => $channel_id));
                    $page_token = '';
                    $upload_ids = array();
                    
                    do {
                        try{
                            $uploads = $this->youtube->playlistItems->listPlaylistItems('contentDetails',
                            array('playlistId' => $content_details['modelData']['items']['0']['contentDetails']['relatedPlaylists']['uploads'],
                            'maxResults' => 50, 'pageToken' => $page_token));
                        } catch (Google_Service_Exception $ex) {
                            return ['errors' => $ex->getErrors()];
                        }
                        
                        $page_token = $uploads['nextPageToken'];
                        $count = count($uploads['modelData']['items']);

                        for ($pos = 0; $pos < $count; $pos++) {
                            // Add each ID to $upload_ids array
                            array_push($upload_ids, $uploads['modelData']['items'][$pos]['contentDetails']['videoId']);
                        }
                    } while ($page_token != '');
                    
                    return $upload_ids;
                }
                
                
                /**
                 * Given a play list, grabs a list of video snippets and returns them
                 * in a json encoded format
                 * 
                 * @param string $playlist_id
                 * @param string $nextPageToken
                 * @param int $perPage
                 * @return array - Array possibly containing 'raw_videos', 'error', and/or 'next_page_token'
                 */
                function query_video_snippets_by_playlist_id($playlist_id, $nextPageToken, $perPage){
                    try {
                        if (strlen($nextPageToken) > 0){
                            $response = $this->youtube->playlistItems->listPlaylistItems('snippet',
                                array('playlistId' => $playlist_id,
                                'maxResults' => $perPage, 'pageToken' => $nextPageToken));
                        } else {
                            $response = $this->youtube->playlistItems->listPlaylistItems('snippet',
                                array('playlistId' => $playlist_id,
                                'maxResults' => $perPage));
                        }
                        
                        /*
                        if (isset($nextPageToken) && $nextPageToken.length() > 0 && 
                                $response['nextPageToken'] === $nextPageToken) {
                            return ['errors' => ["error" => ['message' => 'Identical page token recieved', 
                                'location' => 'snippets_by_playlist_id', 'reason' => "unknown"]]];
                        }
                         * 
                         */
                        
                        if ($response === null) {
                            return ["errors" => ["error" => ['message' => 'No results returned - is Video/Playlist private?', 
                                'location' => '$response', 'reason' => 'Possibly Private?']]];
                        } else if (get_class($response) === 'Google_Service_Exception') {
                            return ['errors' => $response->getErrors()];
                        }
                            
                        $raw_vids = $this->get_raw_vids_from_query_response($response);
                        
                        return ["raw_videos" => $raw_vids, "next_page_token" => $response['nextPageToken']];
                    } catch (Google_Service_Exception $ex) {
                        return ['errors' => $ex->getErrors()];
                    }
                }
                
		/**
		 * Uses the Youtube API object to query for a list of videos
		 * 
		 * @param $parts - Pieces of data to return
		 * @param $selection_args - How to filter videos (id/channel)
		 * @return An array containing the API response
		 */
		function list_videos($parts, $selection_args) {
                        try {
                           $response = $this->youtube->videos->listVideos($parts, $selection_args);
                        } catch (Google_Service_Exception $ex) {
                           return $ex;
                        }
                       
                       return $response;
		}
                
                /**
                 * Take a url and get rhe desired channel/playlist uploads, or individual
                 * video uploads then load them up into the wordpress database.
                 * 
                 * @param type $url
                 */
                function load_videos_from_url($url) {
                    $video_ids = $this->get_targets_from_url($url);
                    
                    if ($video_ids !== false) {
                        $raw_vids = $this->get_all_video_snippets($video_ids);
                    } else {
                        return;
                    }
                    
                    if ($raw_vids !== false) {
                        include 'Video.php';

                        // Get data from post
                        foreach($raw_vids as $videoResult) {
                                $video = new Video($videoResult);

                                // Store to DB
                                $video->store_post();
                        }

                        echo '<p>Successfully loaded ' . count($raw_vids) . ' Youtube videos as posts!</p>';
                    }
                }
                
                /**
                 * Queries the youtube API to get snippets
                 * for an array of video ids.
                 * 
                 * @param $video_ids - list of ids to query snippets for
                 * @return $raw_vids - array of snippets
                 */
                function get_all_video_snippets($video_ids) {
                    $num_targets = count($video_ids);
                    $start = 0;
                    $raw_vids = array();
                    
                    do {
                        $video_ids_slice = implode(array_slice($video_ids, $start, 50), ',');
                        $next_raw_vids = $this->query_video_snippets_by_ids($video_ids_slice);
                        
                        array_push($raw_vids, $next_raw_vids); // Combine arrays?
                        
                        $start += 50;
                    } while ($start < $num_targets);
                    
                    return $raw_vids;
                }
                
                /**
                 * Queries the youtube API to get snippets
                 * for an array of video ids. Only supports
                 * maximum of 50 at once. Call get_all_video_snippets
                 * to loop through this function.
                 * 
                 * @param $video_ids - list of ids to query snippets for
                 * @return $raw_vids - array of snippets
                 */
                public function query_video_snippets_by_ids($video_ids) {
                        try {
                            $response = $this->list_videos("snippet", array('id' => $video_ids,
                                'maxResults' => 50));
                            
                            if (is_a($response, 'Google_Service_Exception')) {
                                return ['errors' => $response->getErrors()];
                            }
                            
                            $raw_vids = $this->get_raw_vids_from_query_response($response);
                            
                            return ["raw_videos" => $raw_vids];
                        } catch (Google_Service_Exception $ex) {
                           return ['errors' => $ex->getErrors()];
                        }
                }
                
                /**
                 * Get the snippet data from each video
                 * returned by a Youtube Data V3 api query
                 * response
                 * 
                 * @param Youtube Data v3 API Response containing an array of items
                 * @return array - of raw vid snippet data
                 */
                private function get_raw_vids_from_query_response($response) {
                    $unprocessed_vids = $response['modelData']['items'];
                    $raw_vids = array();
                    $pos = 0;
                                 
                    if($unprocessed_vids === false) { return false; }
                    else { 
                        foreach($unprocessed_vids as $snippet) {
                            $raw_vids[$pos] = $snippet;
                            $pos++;
                        }
                    }
                    
                    return $raw_vids;
                }
}