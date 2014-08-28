<?php
/**
 * Plugin Name: Upvote / Downvote - Vote with a Tweet
 * Plugin URI: http://upvoting.net/
 * Description: Provides access to vote counts from upvoting.com
 * Version: 1.2.0
 * Author: Upvote Downvote
 * Author URI: https://profiles.wordpress.org/upvote-downvote
 * License: GPL2
 */


/**
 * --- Functional overview ---
 * This plugin requires the following information from the post meta table or the shortcode.
 * 	1. The voteID			(udv_voteID)
 *	2. The display format	(udv_format)
 *	3. The output location	(udv_location) - only used with the in-post admin version.
 *
 * Using this information we can determine what needs to be inserted into each post, and where.
*/


// Include this line to prevent direct access to the plugin file.
defined('ABSPATH') or die("No script kiddies please!");

// add the widget
add_action('widgets_init', 'udv_loadWidget');

// add the shortcode handler
add_shortcode('udvote', 'udv_shortcodeFunc');

// add the content handler
add_filter('the_content', 'udv_maincodeFunc');

// add the admin post page meta boxes
add_action('add_meta_boxes', 'udv_addMetaBoxes');

// add the admin post pages save hook
add_action('save_post', 'udv_metaBoxesSave');


/*************************** PARAMETERS SECTION ***************************/
function udv_paramBaseUrl() {
	return "http://upvoting.net/img"; }

function udv_paramImageFormat() {
	return ".png"; }

function udv_paramSiteURL() {
	return "http://upvoting.com/vote/"; }

function udv_paramImageFormatsText() {
	$array = array(
    	1 => 'Leaderboard, 728 x 90, text',
    	2 => 'Leaderboard, 728 x 90, graph',
    	3 => 'Large Rectangle, 336 x 280, text',
	);
	return $array;
}

function udv_paramImageFormatsFilename() {
	$array = array(
        1 => '728x90v1',
		2 => '728x90v2',
		3 => '336x280v1',
	);
	return $array;
}	

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

/*************************** MAINCODE SECTION ***************************/

function udv_maincodeFunc($content) {
	// $content holds the post content
	
	// create a copy of the content so we can revert if needed
	$original = $content;
	
	try {
		// get the params from the meta info for this post
		// get the post meta info for this post
		$postMetaValues = get_post_custom(get_the_ID());
		// topic ids
		$topicid = isset($postMetaValues['udv_formID_main_topicid']) ? esc_attr($postMetaValues['udv_formID_main_topicid'][0]) : 0;
		// image formats
		$format = isset($postMetaValues['udv_formID_main_format']) ? esc_attr($postMetaValues['udv_formID_main_format'][0]) : 0;
		// image position
		$position = isset($postMetaValues['udv_formID_main_position']) ? esc_attr($postMetaValues['udv_formID_main_position'][0]) : 0;
	
		// if we don't have the minimum required parameters then just return the content unchanged
		if ($topicid == 0 or trim($topicid) == '' or $format == 0) {
			return $content;
		}
		
		// get the html fragment
		$html = udv_getDisplayOutput($topicid, $format, $position);
		
		// position the html either before or after the content
		if ($position == 'top') {
			$content = sprintf('%s %s', $html, $content);
		}
		else {
			$content = sprintf('%s %s', $content, $html);
		}
	}
	catch (Exception $e) {
		// reset the original content in case we have affected it.
		$content = $original;
	} 
	// no 'finally' in php < 5.5, so can't use it. 

	// return $content
	return $content;
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
	$arr = udv_paramImageFormatsFilename();
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
	public function widget($args, $instance) {
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
			echo __($html, 'udv_widget_domain');
		}
	
		// before and after widget arguments are defined by themes so include them
		echo $args['after_widget'];
	}
			
	// widget backend (admin interface)
	public function form($instance) {
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
	     		<?php 
	     		$arrOfImageFormats = udv_paramImageFormatsText();
	     		while ($arrValue = current($arrOfImageFormats)) {
					$selectedVar = '';
					if (esc_attr($topicformat) == key($arrOfImageFormats)) {
						 $selectedVar = "selected"; 
					}
					
					$htmlstr = sprintf('<option value="%s" %s>%s</option>', key($arrOfImageFormats), $selectedVar, $arrValue);
					echo __($htmlstr, 'udv_widget_domain');
				
					next($arrOfImageFormats);
				}
	     		?>
			</select>
			</p>
		<?php 
	}
		
	// update widget replacing old instances with new
	public function update($new_instance, $old_instance) {
		// create instance array
		$instance = array();
		// define our widget instance parameters
		$instance['topicids'] = ( ! empty( $new_instance['topicids'] ) ) ? strip_tags( $new_instance['topicids'] ) : '';
		$instance['topicformat'] = ( ! empty( $new_instance['topicformat'] ) ) ? strip_tags( $new_instance['topicformat'] ) : '';
		return $instance;
}

} // class udv_widget ends

// register and load the widget
function udv_loadWidget() {
	register_widget( 'udv_widget' );
}

/*************************** ADMIN SECTION (POST PAGES) ***************************/

// this adds UDV form elements to the edit and new post pages in the admin. 
function udv_addMetaBoxes() {
	// only show on post and page screens
	$screens = array( 'post', 'page' );
	
	foreach ( $screens as $screen ) {
		add_meta_box(
			'udv_formID_main',
			__( 'Upvote/Downvote - Vote with a Tweet', 'udv_textdomain' ),
			'udv_formID_main_callback',
			$screen
		);
	}
}

function udv_formID_main_callback($post) {
	// get the post meta info for this post
	$postMetaValues = get_post_custom($post->ID);
	// topic ids
	$udv_formID_main_topicid_text = isset( $postMetaValues['udv_formID_main_topicid'] ) ? esc_attr( $postMetaValues['udv_formID_main_topicid'][0] ) : '';
	// image formats
	$udv_formID_main_format_value = isset( $postMetaValues['udv_formID_main_format'] ) ? esc_attr( $postMetaValues['udv_formID_main_format'][0] ) : '';
	// image position
	$udv_formID_main_position_value = isset( $postMetaValues['udv_formID_main_position'] ) ? esc_attr( $postMetaValues['udv_formID_main_position'][0] ) : '';
	
	// add a nonce for security purposes
	wp_nonce_field( 'udv_metaBoxNonce_this', 'udv_metaBoxNonce' );
	
	// build the HTML form UI
	?>
	
	<p>
	<label for="udv_formID_main_topicid">Topic ID</label>
    <input class="fat" type="text" name="udv_formID_main_topicid" id="udv_formID_main_topicid" value="<?php echo $udv_formID_main_topicid_text; ?>" />
	Leave empty to prevent image display.
	</p>
	<p>
	<label for="udv_formID_main_format">Image format</label> 
	<select class="fat" id="udv_formID_main_format" name="udv_formID_main_format">
	    <?php 
	    $arrOfImageFormats = udv_paramImageFormatsText();
	  	while ($arrValue = current($arrOfImageFormats)) {
			$selectedVar = '';
			if ($udv_formID_main_format_value == key($arrOfImageFormats)) {
				 $selectedVar = "selected"; 
			}
					
			$htmlstr = sprintf('<option value="%s" %s>%s</option>', key($arrOfImageFormats), $selectedVar, $arrValue);
			echo __($htmlstr, 'udv_widget_domain');
			
			next($arrOfImageFormats);
		}
	    ?>
	</select>
	</p>
	<p>
	<label for="udv_formID_main_position">Position</label>
	<select class="fat" id="udv_formID_main_position" name="udv_formID_main_position">
		<option value="top" <?php selected( $udv_formID_main_position_value, 'top' ); ?>>Top of post</option>
		<option value="bottom" <?php selected( $udv_formID_main_position_value, 'bottom' ); ?>>Bottom of post</option>		
	</select>
	</p>
	<p>
	<input type="button" name="udv_btnGenerateShortCode" id="udv_btnGenerateShortCode" value="Generate Shortcode" onclick="udv_genSC();">
	&nbsp;&nbsp; Optional - generate shortcode to copy and paste into your post.
	<input type="text" size="40" name="udv_formID_main_shortcode" id="udv_formID_main_shortcode" value="" />
	</p>
	
	<script type="text/javascript">
		function udv_genSC() {
		  	udv_tpc = document.getElementById('udv_formID_main_topicid').value.trim();
		  	udv_fmt = document.getElementById('udv_formID_main_format');
		  	udv_out = document.getElementById('udv_formID_main_shortcode');

		  	udv_scbase = '[udvote voteid="_TPC_" format="_FMT_"]';
		  	
		  	if (udv_tpc != '')
		  	{
		  		udv_fmtVal = udv_fmt.options[udv_fmt.selectedIndex].value;
		  		udv_outStr = udv_scbase.replace('_TPC_', udv_tpc).replace('_FMT_', udv_fmtVal);
		  		udv_out.value = udv_outStr;
		  	}
		  	else
		  	{
		  		udv_out.value = 'Please enter a valid topic ID.';
		  	}
		}
	</script>
	
	<?php
}

// this handles the saving of the udv form control values. Also validates the relevant controls.
function udv_metaBoxesSave($post_id)
{
	// Abort if auto save
    if(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
     
    // if our nonce isn't there, or we can't verify it, abort
    if(!isset($_POST['udv_metaBoxNonce']) || !wp_verify_nonce($_POST['udv_metaBoxNonce'], 'udv_metaBoxNonce_this')) return;
     
    // if current user can't edit this post, abort
    if(!current_user_can('edit_post')) return;
	
	// now we can save the data, make sure data is set before trying to save it
	// topicid
    if(isset($_POST['udv_formID_main_topicid'])) {
        update_post_meta($post_id, 'udv_formID_main_topicid', esc_attr(trim($_POST['udv_formID_main_topicid'])));
	}
	// format
    if(isset($_POST['udv_formID_main_format'])) {
        update_post_meta($post_id, 'udv_formID_main_format', esc_attr($_POST['udv_formID_main_format']));
	}
	// position
	if(isset($_POST['udv_formID_main_position'])) {
        update_post_meta($post_id, 'udv_formID_main_position', esc_attr($_POST['udv_formID_main_position']));
	}
}





// Don't include the php closing tag to avoid header errors as a result of post tag whitespace.
/*End of File*/