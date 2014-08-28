=== Upvote / Downvote - Vote with a Tweet ===
Contributors: Adam_WP, quotesuk
Tags: vote, votes, voting, upvote, downvote, twitter, tweet, adsense
Requires at least: 3.9
Tested up to: 3.9.2
Stable tag: trunk
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Provides convenient access to the vote scores at upvoting.com. Can display the results in various formats on any post or page.

== Description ==

This plugin provides access to the vote scores for any question on Upvote / Downvote. Simply create your topic at upvoting.com or link to other users questions and incorporate the simple shortcode in your posts to display the question and scores. You can also display the results using a widget that you can position in any of your theme widget areas.

= Shortcode =

[udvote voteid="123" format="1"]

The shortcode will be replaced with the image of choice.

- The voteid is the number that appears on the voting page (look near the bottom) or in the authverify tweet.

- The format can be one of 3 values.

	- 1	- Leaderboard, 728 x 90, text display of scores.
	
	- 2	- Leaderboard, 728 x 90, graphical display of scores.
	
	- 3	- Large Rectangle, 336 x 280, text display of scores.

Simply copy and paste the tag into your post where you want the banner to appear.

= Widget =

The widget is installed with the plugin and enables you to add a vote count banner anywhere you can add a widget on your theme. You can assign one or more vote IDs and the widget will display a randomly selected one each time.

Usage is very simple.

- Add the widget to your sidebar or other widget area.

- Add any number of vote IDs separated by commas. e.g. 302,323,380

- Select the style of banner, square is usually best for the sidebar.

- Hit "Save".

= Post/Page Form =

- On every post page the upvote / downvote form will appear. 

- Just type in the topic id and optionally change the format and position.

- Save/Update the post and the vote results will appear.

= Shortcode generator =

- On every post page the upvote / downvote form will appear.  

- Just type in the topic id and optionally change the format. Position is not used.

- Hit the "Generate Shortcode" button and the snippet will appear below the button.

- Copy and paste the shortcode into your post where you want the image to appear.

- Remember to clear the "Topic ID" box if you don't want the image appearing twice.

== Installation ==

1. Upload the `upvote-downvote` directory to the `/wp-content/plugins/` directory
2. Activate the plugin through the \'Plugins\' menu in WordPress
3. Use either shortcode or the administrator interface to add banners to your posts.

== Frequently Asked Questions ==

Q: I want a different size banner, can you make it for me?
A: Over time we will be adding all of the standard banner sizes, starting with the most common.

Q: What is "upvoting.com"?
A: Upvoting.com is a web site where you can ask any question that has a yes/no or upvote/downvote type response. Anyone can answer that has a twitter account. It is easy and simple to use and any of your users can be directed to the site to vote on your questions. As you can display Google Adsense banners on your question pages you can earn revenue from this. No sign-up or personal details are required.

== Changelog ==

= 2014 - 08 - 20 v1.0.0
- Initial release, shortcode only version.

= 2014 - 08 - 22 v1.1.0
- Added widget.

= 2014 - 08 - 22 v1.1.1
- Additional documentation.

= 2014 - 08 - 27 v1.2.0
- Added admin post page form to include image at top or bottom of posts.
- Added a shortcode generator on the post page.
- Tidied up some code to enable easier reuse.

/End Readme