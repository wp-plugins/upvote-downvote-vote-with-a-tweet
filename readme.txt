=== Upvote / Downvote - Vote with a Tweet ===
Contributors: Adam_WP, quotesuk
Tags: vote,votes,voting,upvote,downvote,twitter,tweet,adsense,shortcode,widget,post,posts,page,pages,snippet,plugin
Requires at least: 3.9
Tested up to: 4.3
Stable tag: trunk
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Allows users to vote on a topic using Twitter.
Display results in standard banner sizes on posts, pages or widget. 
Optional Adsense Revenue Share.

== Description ==

The **Upvote / Downvote** plugin for WordPress enables shortcode, post and widget placement of topic banners. You can choose which topic(s) to display, the banner size and position, and display your topics current scores.

No registration or login needed. Anyone can **Vote with a Tweet!**

Earn money with our advert sharing using Google Adsense.

Create your own topics on [Upvoting.com](https://upvoting.com "Visit Upvoting.com")

For Developer Resources including sample banner sizes and a list of trending topics visit [Upvoting.net](https://upvoting.net "Visit Upvoting.net")

Just add our widget to your sidebar to get going!


= Shortcode =

`[udvote voteid="123" format="1"]`

The shortcode will be replaced with the image of choice.

- The voteid is the number that appears on the voting page (look near the bottom) or in the authverify tweet.

- The format can be one of these values;

	* 1 - Leaderboard, 728 x 90, text display of scores.
	* 2 - Leaderboard, 728 x 90, graphical display of scores.
	* 3 - Large Rectangle, 336 x 280, text display of scores.
	* 4 - Large Square, 250 x 250, text display of scores.
	* 5 - Vertical Rectangle, 240 x 400, text display of scores.
	* 6 - Wide Leaderboard, 1200 x 150, text display of scores.
	* 7 - Wide Leaderboard, 1200 x 150, graphical display of scores.
	
- Simply copy and paste the tag into your post where you want the banner to appear.


= Widget =

The widget is installed with the plugin and enables you to add a vote count banner anywhere you can add a widget on your theme. You can assign one or more vote IDs and the widget will display a randomly selected one each time.

- Add the widget to your sidebar or other widget area.

- Add any number of vote IDs separated by commas. e.g. 302,323,380

- Select the style of banner, square is usually best for the sidebar.

- Hit "Save".


= Post/Page Form =

- On every post page the Upvote / Downvote form will appear. 

- Just type in the topic id and optionally change the format and position.

- Save/Update the post and the vote results will appear.


= Shortcode generator =

- On every post page the Upvote / Downvote form will appear.  

- Just type in the topic id and optionally change the format. Position is not used.

- Hit the "Generate Shortcode" button and the snippet will appear below the button.

- Copy and paste the shortcode into your post where you want the image to appear.

- Remember to clear the "Topic ID" box if you don't want the image appearing twice.



= Global Settings =
-----
The main settings page allows you to set global parameters that will affect the display of all banners.

**Include surrounding &lt;div&gt;** 

The banner is placed on the page using this generic html snippet:

> &lt;div&gt;&lt;a href&gt;&lt;img /&gt;&lt;/a&gt;&lt;/div&gt;

Uncheck this box to omit the &lt;div&gt; element from the output, this can also be set on a per-banner basis

**Image width type**

- Percentage : The image width is set to "100%" to fill any container. 

- Fixed width : The width of the image is set to the actual pixel width of the image.

**Div width type**

Select whether or not the div has no width attribute set (default) or has a "width:123px" style that matches the width of the contained image. Useful if you have inherited "floats".

**&lt;div&gt; custom style**

Anything you enter in this box will appear in the style tag of the div. e.g. "padding-bottom:20px;"
If you exclude the div element then this will have no effect.

**&lt;img&gt; custom style**

Anything you enter in this box will appear in the style tag of the image. e.g. "padding-bottom:20px;"

**&lt;a href&gt; custom style**

Anything you enter in this box will appear in the style tag of the link. e.g. "display:block;"


= Widget Settings =
-----
These settings only affect the widget. You can override the global settings here if needed.

**Include surrounding &lt;div&gt;**

Similar to the global parameter with the same name, you can override the global setting here.

**&lt;div&gt; custom style**

Anything you enter in this box will appear in the style tag of the div. e.g. "padding-bottom:20px;"
If you exclude the div element then this will have no effect.

**&lt;img&gt; custom style**

Anything you enter in this box will appear in the style tag of the image. e.g. "padding-bottom:20px;"

**&lt;a href&gt; custom style**

Anything you enter in this box will appear in the style tag of the link. e.g. "display:block;"


= Advanced Options =
-----
These allow to adjust how specific sizes of banner are displayed. Each section reflects one of the available banner sizes.

**Include surrounding &lt;div&gt;**

Similar to the global parameter with the same name, you can override the global setting here.

**Alignment**

Applies the style "float:xyz;" to outermost element (either the div or the image). The available options are; none/left/right/inherit. 

**&lt;div&gt; custom style**

Anything you enter in this box will appear in the style tag of the div. e.g. "padding-bottom:20px;"
If you exclude the div element then this will have no effect.

**&lt;img&gt; custom style**

Anything you enter in this box will appear in the style tag of the image. e.g. "padding-bottom:20px;"


== Installation ==
1. Upload the `upvote-downvote-vote-with-a-tweet` directory to the `/wp-content/plugins/` directory
2. Activate the plugin through the `Plugins` menu in WordPress
3. Add banners to the sidebar using the Upvote / Downvote widget
4. Use either shortcode or the administrator interface to add banners to your posts.


== Frequently Asked Questions ==

= What banner sizes are currently available? =
The following banner sizes are available:

	* Leaderboard, 728 x 90 - with text results
	* Leaderboard, 728 x 90 - with pie chart results
	* Large Rectangle, 336 x 280 - with text results
	* Large Square, 250 x 250 - with text results
	* Vertical Rectangle, 240 x 400 - with text results
	* Wide Leaderboard, 1200 x 150 - with text results
	* Wide Leaderboard, 1200 x 150 - with pie chart results

= I want a different size banner, can you make it for me? =
Over time we will be adding all of the standard banner sizes, starting with the most common.

= What is "upvoting.com"? =
[Upvoting.com](https://upvoting.com "Visit Upvoting.com") is a web site where you can ask any question that has a yes/no or upvote/downvote type response. Anyone can answer that has a twitter account. It is easy and simple to use and any of your users can be directed to the site to vote on your questions. As you can display Google Adsense banners on your question pages you can earn revenue from this. No sign-up or personal details are required.


== Screenshots ==
1. Include the banners in your posts or the sidebar.
2. Comprehensive settings are available if needed.
3. The 'post' form including shortcode generator.


== Changelog ==
= 2014 - 08 - 20 v1.0.0 =
* Initial release, shortcode only version.

= 2014 - 08 - 22 v1.1.0 =
* Added widget.

= 2014 - 08 - 22 v1.1.1 =
* Additional documentation.

= 2014 - 08 - 27 v1.2.0 =
* Added admin post page form to include image at top or bottom of posts.
* Added a shortcode generator on the post page.

= 2014 - 08 - 28 v1.2.5 =
* Added global settings page for customisation of certain features.

= 2014 - 09 - 05 v1.3.0 =
* Added widget and per-banner settings for advanced customisation.
* Added 2 new banner sizes (250x250) and (240x400).

= 2015 - 01 - 04 v1.3.1 =
* Version revision for Wordpress 4.1.
* Minor wording changes.

= 2015 - 05 - 29 v1.4.0 =
* All links to the upvote/downvote servers are now SSL enabled so you won't get any warnings when using https on your website.
* Version revision for Wordpress 4.2.
* Added a new banner size (1200x150).

= 2015 - 08 - 13 v1.4.1 =
* Version revision for Wordpress 4.3.

