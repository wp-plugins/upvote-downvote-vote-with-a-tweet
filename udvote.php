<?php
/**
 * Plugin Name: Upvote / Downvote - Vote with a Tweet
 * Plugin URI: http://upvoting.net/
 * Description: Provides access to vote counts from upvoting.com
 * Version: 1.1.1
 * Author: Upvote Downvote
 * Author URI: https://profiles.wordpress.org/upvote-downvote
 * License: GPL2
 */


/**
 * --- Functional overview ---
 * This plugin requires the following information from the post table or the shortcode.
 * 	1. The voteID			(udv_voteID)
 *	2. The display format		(udv_format)
 *	3. The output location		(udv_location) - only used with the in-post admin version.
 *
 * Using this information we can determine what needs to be inserted into each post, and where.
*/


// Include this line to prevent direct access to the plugin file.
defined('ABSPATH') or die("No script kiddies please!");

// add the shortcode handler
add_shortcode('udvote', 'udv_shortcodeFunc');

/*************************** PARAMETERS SECTION ***************************/
function udv_paramBaseUrl() {
	return "http://upvoting.net/img"; }

function udv_paramImageFormat() {
	return ".png"; }

function udv_paramSiteURL() {
	return "http://upvoting.com/vote/"; }

/*************************** SHORTCODE SECTION ***************************/

// [udvote voteid="" format=""]
function udv_shortcodeFunc( $atts ) {
	// the a varable will hold an array with either 0 or the user value (if supplied) for both voteid and format
	$a = shortcode_atts( array(
        	'voteid' => '0',
		'format' => '0',
		), $atts );

	// If either of the value in the array are 0 we know that they were not supplied so display a message. 
	if ($a['voteid'] == 0 or $a['format'] == 0)
	{
		// The data is incomplete, return an informative message.
		return "Missing voteid or format";
	}

	// The values must be OK so we can pass them to the relevant functions and allow them to do the work
    	return udv_getDisplayOutput($a['voteid'], $a['format']);
}

/*************************** GENERAL SECTION ***************************/

// This is the primary function that coordinates the output.
// Input can come from either the shortcode or the content/db params, the output will be the same.
function udv_getDisplayOutput($voteid, $format)
{
	// voteid = the integer vote id
	// format = the integer format

	// get the url of the image.
	$path = udv_getImageUrlForVote($voteid, $format);
	
	// add the surrounding HTML and return.
	return udv_createBaseHTMLFragment($voteid, $path);
}

// This builds the entire path (URI) to the requested image.
function udv_getImageUrlForVote($voteid, $format)
{
	// assign the base url.
	$path = udv_paramBaseUrl();

	// assign the path fragment.
	$path .= udv_getURLPathForVoteID($voteid);
	
	// assign the format/filename.
	$path .= udv_getFormatFromInteger($format);

	// assign the image format.
	$path .= udv_paramImageFormat();

	return $path;
}

// this function returns a path fragment relating to the images location on the host server.
function udv_getURLPathForVoteID($voteid)
{
	// Examples
	// topic 123 : 000/123/
	// topic 14234 : 014/234/
	// topic 1234568 : 001/234/568

	// cast the voteid to a string.
	$str_topicID = (string)$voteid;

	// declare a path holding variable.
	$path = "";
	
	// Check if the voteid length is a multiple of 3 and if not pad with leading zeroes until it is and is a minimum length of 6.
	while ((strlen ($str_topicID) % 3) != 0 or strlen ($str_topicID) < 4)
	{
        	$str_topicID = "0" . $str_topicID ;
	}

	// now we know the path length is a multiple of 3 we can divide it up and add the slashes.
	for ($i = 0;  $i < strlen($str_topicID) / 3; $i++)
	{
            	$path .= "/" . substr ($str_topicID, $i * 3, 3);
	}
        
        $path = $path . "/"; # add a final trailing slash.

	return $path;
}

// this returns the filename relating to the chosen image format.
function udv_getFormatFromInteger($format)
{
	$arr = array(
        	1 => '728x90v1',
		2 => '728x90v2',
		3 => '336x280v1',
		);

	return $arr[$format];
}

// this function creates an html code fragment that we use to include the image on the page.
function udv_createBaseHTMLFragment($voteid, $pathToImage)
{
	// get the main site url
	$siteUrl = udv_paramSiteURL();

	// create an image tag html fragment.
	$img = '<img src="%s" style="width:100%%;" alt="Upvote Downvote" />';
	$img = sprintf($img , $pathToImage);

	// create a link html fragment, include above img.
	$lnk = '<a href="%s%s" title="See the Latest Results and Vote with a Tweet">%s</a>';
	$lnk = sprintf($lnk, $siteUrl, $voteid, $img);

	// create a placeholder div html fragment, include above link.
	$div = '<div id="udv_%s">%s</div>';
	$div = sprintf($div, $voteid, $lnk);
	
	// just in case we add any further html later.
	$html = $div;

	// return the formatted html.
	return $html;
}

/*************************** WIDGET SECTION ***************************/

// create the widget by extending the widget class
class udv_widget extends WP_Widget {

	function __construct() {
		parent::__construct(
			// Base ID of widget
			'udv_widget', 
	
			// Widget name that will appear in admin UI
			__('Upvote / Downvote', 'udv_widget_domain'), 
	
			// Widget description
			array( 'description' => __( 'Provides access to vote counts from upvoting.com' ), ) 
		);
	}
	
	// widget front-end 
	public function widget( $args, $instance ) {
		// widget title
		$udvtitle =  apply_filters( 'widget_title', 'Upvote / Downvote' );
	
		// topicids are a single string of comma delimited values.
		$topicids = $instance['topicids'] ;
		
		// display format - the standard numerical list of formats
		$topicformat = $instance['topicformat'];
	
		// before and after widget arguments are defined by themes so include them
		echo $args['before_widget'];
	
		// add the title and image if the topicids have been defined, otherwise show nothing.
		if ( ! empty( $topicids ) and $topicids != 'Comma separated topic IDs')
		{
			// display the widget title
			 echo $args['before_title'] . $udvtitle . $args['after_title'];
	
			// split the topic ids on the commas.
			$arr_topicids = explode(',', $topicids);
	
			// get a random number between 0 and the number of topic ids -1.
			$topicids_count = count($arr_topicids) - 1;
			$randno = rand(0, $topicids_count);
	
			// get the random topicid to display.
			$topicid = trim($arr_topicids[$randno]);
		
			// get the html to output
			$html = udv_getDisplayOutput($topicid, $topicformat);
	
			// display the html
			echo __($html, 'udv_widget_domain' );
		}
	
		// before and after widget arguments are defined by themes so include them
		echo $args['after_widget'];
	}
			
	// widget backend (admin interface)
	public function form( $instance ) {
		// set default value for the topicids, if this changes remember to change it in the front-end display logic.
		if ( isset( $instance[ 'topicids' ] ) ) {
			$topicids = $instance[ 'topicids' ];
		}
		else {
			$topicids = __( 'Comma separated topic IDs', 'udv_widget_domain' );
		}
		
		// set the default topic format.
		if ( isset( $instance[ 'topicformat' ] ) ) {
			$topicformat = $instance[ 'topicformat' ];
		}
		else {
			$topicformat = __( '3', 'udv_widget_domain' );
		}
		
		// widget admin form, standard HTML
		?>
			<p>
			<label for="<?php echo $this->get_field_id( 'topicids' ); ?>"><?php _e( 'Topic IDs:' ); ?></label> 
			<input class="widefat" id="<?php echo $this->get_field_id( 'topicids' ); ?>" name="<?php echo $this->get_field_name( 'topicids' ); ?>" type="text" value="<?php echo esc_attr( $topicids ); ?>" />
			One will be randomly selected on each display e.g. 302,323,380
			</p>
			<p>
			<label for="<?php echo $this->get_field_id( 'topicformat' ); ?>"><?php _e( 'Image Format:' ); ?></label> 
			<select class="widefat" id="<?php echo $this->get_field_id( 'topicformat' ); ?>" name="<?php echo $this->get_field_name( 'topicformat' ); ?>">
	     			<option value="1" <?php if (esc_attr($topicformat) == '1' ) {echo ("selected"); }   ?>>Leaderboard, 728 x 90, text</option>
				<option value="2" <?php if (esc_attr($topicformat) == '2' ) {echo ("selected"); }   ?>>Leaderboard, 728 x 90, graph</option>
				<option value="3" <?php if (esc_attr($topicformat) == '3' ) {echo ("selected"); }   ?>>Large Rectangle, 336 x 280, text</option>
			</select>
			</p>
		<?php 
	}
		
	// update widget replacing old instances with new
	public function update( $new_instance, $old_instance ) {
		// create instance array
		$instance = array();
		// define our widget instance parameters
		$instance['topicids'] = ( ! empty( $new_instance['topicids'] ) ) ? strip_tags( $new_instance['topicids'] ) : '';
		$instance['topicformat'] = ( ! empty( $new_instance['topicformat'] ) ) ? strip_tags( $new_instance['topicformat'] ) : '';
		return $instance;
}

} // class udv_widget ends

// register and load the widget
function udv_load_widget() {
	register_widget( 'udv_widget' );
}
add_action( 'widgets_init', 'udv_load_widget' );














// Don't include the php closing tag to avoid header errors as a result of post tag whitespace.
/*End of File*/