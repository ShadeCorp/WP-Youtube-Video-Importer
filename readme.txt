=== WP Youtube Video Importer ===
Contributors: ChrisNavarre
Donate link: http://goo.gl/1yHqsz
Tags: youtube, video, thumbnail, bulk, batch, import, post
Requires at least: 3.7
Tested up to: 4.1
Stable tag: trunk
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Imports videos from YouTube channels, playlists, and single videos embeded in posts within Wordpress. Optionally include
descriptions and thumbnails.

== Description ==

The WP YouTube Video Importer can take any video, playlist, or channel and import any number of videos
embeded into posts within wordpress. You may filter by date, include the video's thumbnails as featured
images for your posts, include or exclude the video description, and control the video's positioning and
embed size on the page.

== Installation ==

1. Upload the 'wp-youtube-video-importer' directory to the '/wp-content/plugins' directory
2. Activate the plugin through the 'Plugins' menu in Wordpress

== Frequently Asked Questions ==

= Why are some videos from my channel missing after an import? =

Videos from a channel or user will pull from the uploads playlist which
may not have every video that your channel hosts. Unfortunately this problem
lies with YouTube. Double check for any videos missing
from that playlist that you want uploaded to Wordpress. 
Private/Draft videos will not be brought in.

= Can I bring my video tags into each post? =

Apparently YouTube thought it would be a good idea to remove
tags from the unauthenticated API. In a future version, I may
make it so that you can get the tags only for videos that you
own by authenticating. Let me know if this is a feature you'd
really like to see.

== Changelog ==

= 1.0 =
* Initial Release - Import videos, playlists, channels
* Supports Thumbnails, Descriptions, and other various options

== Usage ==

Plugin is used entirely through the wp-admin interface. You can find
the links within the Wordpress menu, WP Youtube Post Importer should
be below the core wordpress menu items.

- Settings Page: Let's you choose the settings for future imports, I recommend you check this before importing
- Import Page: Post a full YouTube video/watch/playlist/channel URL and hit import. Playlists/Channels 
               require checking the confirmation box
- Donate Page: If my plugin helped you save you time and effort, it would be greatly appreciated.
- Help Page: All of this information is also contained there.

Youtube Post Importer allows you to import a single video, a playlist, or a channel 
(which actually grabs the uploaded videos playlist)
Here are some examples of valid links that you can post into the importer field

https://www.youtube.com/watch?v=i6eNvfQ8fTw
https://www.youtube.com/channel/UCwdKySs6j48yl1ae8csAurQ
https://www.youtube.com/playlist?list=PLyH-qXFkNSxn8iiN2M1rnkpQRDJIGDe6g

If the link is to a playlist/user/channel instead of just a single video, you will be prompted
to check a checkbox in order to verify that you understand that the plugin will be importing all 
the videos on the associated playlist, that it may take a while and slowdown your website. 
In addition, there are optional date selection fields if you wish to filter the import data

[Minimum Date (Inclusive) on the Left] -- [Maximum Date (Inclusive) on the Right]

Successes:	Videos that were successfully imported into individual Wordpress posts with the settings supplied
            by the options page
Failures:	  Videos which could not successfully import into the database.
Skipped:  	Videos that were ignored during the import because of a date filter that shows up for playlist imports.
            Using just the video/channel id and not the full URL won't work - Ex. "i6eNvfQ8fTw"

Before actually attempting to import videos into Wordpress, I recommend backing up your database 
and checking the plugin settings page to see if there are any modifications you wish to make to the
data going into Wordpress.
