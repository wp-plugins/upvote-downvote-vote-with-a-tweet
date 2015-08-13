<?php
/**
 * Plugin Name: Upvote / Downvote - Vote with a Tweet
 * Plugin URI: https://upvoting.net/
 * Description: Displays banner images with your topic results
 * Version: 1.4.1
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

// add the admin settings page hook
add_action('admin_menu', 'udv_optionsMenu');

// add the admin settings registration hook, but only if needed
if (!empty ($GLOBALS['pagenow']) and ('options-general.php' === $GLOBALS['pagenow'] 
	or 'options.php' === $GLOBALS['pagenow']))
{ add_action('admin_init', 'udv_optionsRegistration'); }


/*************************** PARAMETERS SECTION ***************************/
/*
 * This section holds parameters and defaults used by the various plugin functions
*/
 
// The root url that serves the banners
function udv_paramBaseUrl() {
	return "https://upvoting.net/img"; }

// The image format to retrieve from the server 
function udv_paramImageFormat() {
	return ".png"; }

// The root url for links to the upvoting site
function udv_paramSiteURL() {
	return "https://upvoting.com/vote/"; }

// The list of image formats available, this is the human friendly version
function udv_paramImageFormatsText() {
	$array = array(
    	1 => 'Leaderboard, 728 x 90, text',
    	2 => 'Leaderboard, 728 x 90, graph',
    	3 => 'Large Rectangle, 336 x 280, text',
    	4 => 'Large Square, 250 x 250, text',
    	5 => 'Vertical Rectangle, 240 x 400, text',
    	6 => 'Wide Leaderboard, 1200 x 150, text',
    	7 => 'Wide Leaderboard, 1200 x 150, graph',
	);
	return $array;
}

// The list of image formats available, this is the filenames, need to be the same order as above
function udv_paramImageFormatsFilename() {
	$array = array(
        1 => '728x90v1',
		2 => '728x90v2',
		3 => '336x280v1',
		4 => '250x250v1',
		5 => '240x400v1',
		6 => '1200x150v1',
		7 => '1200x150v2',
	);
	return $array;
}	

// Default global options, set in the settings section
function udv_paramDefaultGlobalOptions() {
	$array = array(
        'includeDiv' => 'true',
        'imageWidthType'  => 'percent',
        'matchDivToImage' => 'false',
        'div' => '',
        'img'  => '',
        'href'   => ''
    );	
	return $array;
}

// Default widget options, set in the settings section
function udv_paramDefaultWidgetOptions() {
	$array = array(
        'includeDiv' => 'global',
        'div' => '',
        'img'  => '',
        'href'   => ''
    );	
	return $array;
}

// Default banner options, set in the settings section
function udv_paramDefaultBannerOptions() {
	$array = array(
		'includeDiv' => 'global',
		'float' => 'none',
		'div' => '',
		'img' => '',
	);
	return $array;
}


/*************************** GENERAL SECTION ***************************/
/*
 * This section contains all of the functions that generate the html output that will ultimately be rendered
 * in the browser.
*/

// This is the primary function that coordinates the output.
// Input can come from either the shortcode or the content/db params, the output will be the same.
// -- parameters
// $voteid	: 	integer		|	vote id
// $format	: 	integer 	| 	format, relates to the array index in udv_paramImageFormatsFilename
// $type	: 	string		|	'b':banner, 'w':widget
// -- returns
// string	|	the full html fragment
function udv_getDisplayOutput($voteid, $format, $type) {
	// get the url of the image to display
	$path = udv_getImageUrlForVote($voteid, $format);
	
	// add the surrounding HTML and return complete fragment.
	return udv_createBaseHTMLFragment($voteid, $path, $format, $type);
}

// This builds the full path (URI) to the requested image.
// -- parameters
// $voteid	: 	integer		|	vote id
// $format	: 	integer 	| 	format, relates to the array index in udv_paramImageFormatsFilename
// -- returns
// string	|	full url to results image/banner
function udv_getImageUrlForVote($voteid, $format) {
	// assign the base url.
	$path = udv_paramBaseUrl();
	
	// assign the path fragment.
	$path .= udv_getURLPathForVoteID($voteid);	
	
	// assign the format/filename.
	$path .= udv_getFormatFromInteger($format);
	
	// assign the image format.
	$path .= udv_paramImageFormat();
	
	// return constructed string 
	return $path;
}

// This function returns a path fragment relating to the images location on the host server.
// The images are stored using a directory tree structure created by a simple algorithm.
// This function implements that algorithm allowing the path fragment to be built.
// Examples
// topic 123 : 000/123/
// topic 14234 : 014/234/
// topic 1234567 : 001/234/567
// -- parameters
// $voteid	: 	integer		|	vote id
// -- returns
// string	|	a section of the path to the image to display
function udv_getURLPathForVoteID($voteid) {
	// cast the voteid to a string.
	$str_topicID = (string)$voteid;
	
	// declare a path holding variable.
	$path = "";
	
	// Check if the voteid length is a multiple of 3 and if not pad with leading zeroes until it is and is a minimum length of 6.
	while ((strlen ($str_topicID) % 3) != 0 or strlen ($str_topicID) < 4) {
        	$str_topicID = "0" . $str_topicID ;
	}

	// now we know the path length is a multiple of 3 we can divide it up and add the slashes.
	for ($i = 0;  $i < strlen($str_topicID) / 3; $i++) {
            	$path .= "/" . substr ($str_topicID, $i * 3, 3);
	}
    
	// add a final trailing slash.
    $path = $path . "/"; 

	// return constructed string 
	return $path;
}

// This returns the filename relating to the chosen image format.
// The $format relates to the array index in udv_paramImageFormatsFilename
// -- parameters
// $format	: 	integer		|	vote id
// -- returns
// string	|	image filename without extension
function udv_getFormatFromInteger($format)
{
	$arr = udv_paramImageFormatsFilename();
	return $arr[$format];
}

// This determines the height and width of the image being displayed.
// The $format relates to the array index in udv_paramImageFormatsFilename
// -- parameters
// $format	: 	integer		|	vote id
// -- returns
// array	|	2 elements: 'w','h' = width and height respectively
function getImageWidthHeight($format) {
	$str_fileName = udv_getFormatFromInteger($format);
	// as the filename includes the dimensions we can use this to get the dimensions to display.
	// format of string: widthxheightvy
	
	// get width
	$pos_x = strpos($str_fileName, 'x');
	$w = substr($str_fileName, 0, $pos_x);
	
	// get height
	$pos_x = $pos_x + 1;
	$pos_v = strpos($str_fileName, 'v');
	$pos_v = $pos_v - strlen($str_fileName);
	$h = substr($str_fileName, $pos_x, $pos_v);
	
	// build and return the array
	$array = array(
			'w' => $w,
			'h' => $h,
			);
	return $array;
}

// This function creates an html code fragment that we use to include the image on the page.
// -- parameters
// $voteid	: 	integer		|	vote id
// $pathToImage : string	|	the full url of the image to display
// $format	: 	integer 	| 	format, relates to the array index in udv_paramImageFormatsFilename
// $type	: 	string		|	'b':banner, 'w':widget
// -- returns
// string	|	completed html fragment ready for inclusion in a web page
function udv_createBaseHTMLFragment($voteid, $pathToImage, $format, $type)
{
	// get the main site url
	$siteUrl = udv_paramSiteURL();
	
	// get the options from the wp_options table
	$globalOptions = shortcode_atts(udv_paramDefaultGlobalOptions(), get_option('udv:globalOptions', udv_paramDefaultGlobalOptions())); //array
	$widgetOptions =  shortcode_atts(udv_paramDefaultWidgetOptions(), get_option('udv:widgetOptions', udv_paramDefaultWidgetOptions())); //array
	
	$bannerOptions = get_option('udv:bannerOptions', null); //array
	
	// as $bannerOptions is a 2d array we need to get the correct dimension
	if (is_array($bannerOptions) && array_key_exists($format, $bannerOptions)) {
		$bannerOptions = $bannerOptions[$format];
	}
	
	// assign the default values to the $bannerOptions array
	$bannerOptions = shortcode_atts(udv_paramDefaultBannerOptions(), $bannerOptions); //array
	
	// assign required params	
	// image height and width
	$imgHW = getImageWidthHeight($format); //array
	
	// global options
	$imageWidthType = esc_attr($globalOptions['imageWidthType']);
	$includeDiv = esc_attr($globalOptions['includeDiv']);
	$matchDivToImage = esc_attr($globalOptions['matchDivToImage']);
	// global styles
	$styleDiv = esc_attr($globalOptions['div']);
	$styleImg = esc_attr($globalOptions['img']);
	$styleHref = esc_attr($globalOptions['href']);
	// widget options
	$widgetDiv = esc_attr($widgetOptions['includeDiv']);
	$widgetStyleDiv = esc_attr($widgetOptions['div']);
	$widgetStyleImg = esc_attr($widgetOptions['img']);
	$widgetStyleHref = esc_attr($widgetOptions['href']);
	// banner options
	$bannerDiv = $bannerOptions['includeDiv'];
	$bannerFloat = $bannerOptions['float'];
	$bannerStyleDiv = esc_attr($bannerOptions['div']);
	$bannerStyleImg = esc_attr($bannerOptions['img']);
	
	// tidy up any custom styles.
	// add the end ; if missing from custom style
	// div
	if ($styleDiv != '' && substr($styleDiv, -1) != ';') { $styleDiv .= ';'; }
	if ($widgetStyleDiv != '' && substr($widgetStyleDiv, -1) != ';') { $widgetStyleDiv .= ';'; }
	if ($bannerStyleDiv != '' && substr($bannerStyleDiv, -1) != ';') { $bannerStyleDiv .= ';'; }
	// img
	if ($styleImg != '' && substr($styleImg, -1) != ';') { $styleImg .= ';'; }
	if ($widgetStyleImg != '' && substr($widgetStyleImg, -1) != ';') { $widgetStyleImg .= ';'; }
	if ($bannerStyleImg != '' && substr($bannerStyleImg, -1) != ';') { $bannerStyleImg .= ';'; }
	// href
	if ($styleHref != '' && substr($styleHref, -1) != ';') { $styleHref .= ';'; }
	if ($widgetStyleHref != '' && substr($widgetStyleHref, -1) != ';') { $widgetStyleHref .= ';'; }
	
	// determine if we are going to render a surrounding div
	$useSurroundingDiv = false;
	if ($type == 'b') { // type = b (banner)
		if ($bannerDiv == 'true' || ($includeDiv == 'true' && $bannerDiv == 'global')) {
			$useSurroundingDiv = true;
		}
	} else { /// type = w (widget)
		if ($widgetDiv == 'true' || ($includeDiv == 'true' && $widgetDiv == 'global')) {
			$useSurroundingDiv = true;
		}
	}

	// if the $bannerFloat var is not none and we are including the div then we have to force the div 
	// to the image size or it won't do anything.
	if ($bannerFloat != 'none' && $useSurroundingDiv) {
		$matchDivToImage = 'true';
	}
	
	// handle the div formatting
	$divStyleTag = '';
	
	if ($type == 'b') { // type = b (banner)
		// prepare the div style tag, width parameter
		if ($matchDivToImage == 'true') {
			$styleDiv = sprintf('width:%spx;%s', $imgHW['w'], $styleDiv);
		}
		
		// div float property
		if ($bannerFloat != 'none') {
			$styleDiv = sprintf('float:%s;%s', $bannerFloat, $styleDiv);
		}
		
		// div custom styles, global and banner
		$divStyleTag = sprintf(' style="%s%s" ', $styleDiv, $bannerStyleDiv);

	} else { // type = w (widget)
		if ($styleDiv != '' || $widgetStyleDiv != '') {
			// div custom styles, global and widget	
			$divStyleTag = sprintf(' style="%s%s" ', $styleDiv, $widgetStyleDiv);
		}
	}

	// prepare the href style tag
	// href custom style
	$hrefStyleTag = '';
	
	if ($type == 'b') { // type = b (banner)
		// do nothing, keep this structure for uniformity
	} else { // type = w (widget)
		// href custom styles, global and widget	
		$styleHref .= $widgetStyleHref;
	}
	if ($styleHref != '') {
		$hrefStyleTag = sprintf(' style="%s" ', $styleHref);
	} 
	
	// prepare the img style tag
	$imgStyleTag = '';
	
	if ($type == 'b') { // type = b (banner)
		// width type.
		if ($imageWidthType == 'percent') {
			$styleImg = sprintf('width:100%%;%s', $styleImg);
		}
		else {
			$styleImg = sprintf('width:%spx;%s', $imgHW['w'], $styleImg);
		}
		// img float, only applied to the image if the div is not rendered.
		if ($bannerFloat != 'none' && !$useSurroundingDiv) {
			$styleImg = sprintf('float:%s;%s', $bannerFloat, $styleImg); 
		}
		// img custom style
		if ($styleImg != '' || $bannerStyleImg != '') {
			// img custom styles, global and banner	
			$imgStyleTag = sprintf(' style="%s%s" ', $styleImg, $bannerStyleImg);
		}
	} else { // type = w (widget)
		// add the width %
		$styleImg = sprintf('width:100%%;%s', $styleImg);
			
		if ($styleImg != '' || $widgetStyleImg != '') {			
			// img custom styles, global and widget	
			$imgStyleTag = sprintf(' style="%s%s" ', $styleImg, $widgetStyleImg);
		}
	}
	
	// create an image tag html fragment.
	$img = '<img src="%s" %s alt="Upvote Downvote" />';
	$img = sprintf($img , $pathToImage, $imgStyleTag);

	// create a link html fragment, include above img.
	$lnk = '<a href="%s%s" title="See the Latest Results and Vote with a Tweet" %s>%s</a>';
	$lnk = sprintf($lnk, $siteUrl, $voteid, $hrefStyleTag, $img);

	// create a div html fragment, include above link.
	if ($useSurroundingDiv) {
		$div = '<div id="udv_%s" %s>%s</div>';
		$div = sprintf($div, $voteid, $divStyleTag, $lnk);
		$html = $div;
	} else {
		$html = $lnk;
	}

	// tidy up the html fragment
	$html = str_replace('  ', ' ', $html);
	$html = str_replace(' >', '>', $html);

	// debug code, leave in for convenience.
	// echo "<pre>" . str_replace('>', '&gt;', str_replace('<', '&lt;', $html)) . "</pre>";

	// return the formatted html.
	return $html;
}


/*************************** SHORTCODE SECTION ***************************/
/* 
 * This section handles the shortcode implementation
 * General format of shortcode: [udvote voteid="" format=""]
*/

// This function takes the supplied shortcode attributes and returns a completed html fragment. 
// -- parameters
// $atts	: 	array		|	shortcode attributes supplied by user
// -- returns
// string	|	completed html fragment ready for inclusion in a web page
function udv_shortcodeFunc($atts) {
	// the $a varable will hold an array with either 0 or the user value (if supplied)
	// for both voteid and format
	$a = shortcode_atts( array(
        	'voteid' => '0',
			'format' => '0',
		), $atts );

	// If either of the value in the array are 0 we know that they were not supplied so display a message. 
	if ($a['voteid'] == 0 or $a['format'] == 0)	{
		// The data is incomplete, return an informative message.
		return "Missing voteid or format";
	}

	// The values are OK if we get here so we can pass them to the display function
    return udv_getDisplayOutput(trim($a['voteid']), trim($a['format']), 'b');
}


/*************************** MAINCODE SECTION ***************************/
/*
 * This section handles the display of the banners when attached to a post using the form on
 * the post editing page.
 * The required info is stored in the post meta table.
*/

// This function is called by the post rendering routine and and either prepends or appends the
// generated html fragment.
// -- parameters
// $content	: 	string		|	the post
// -- returns
// string	|	the full html fragment
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
			$original = ''; // empty this param as it may hold a large amount of data
			return $content;
		}
		
		// get the html fragment
		$html = udv_getDisplayOutput($topicid, $format, 'b');
		
		// position the html either before or after the content
		if ($position == 'top') {
			$content = sprintf('%s %s', $html, $content);
		}
		else {
			$content = sprintf('%s %s', $content, $html);
		}
	}
	catch (Exception $e) {
		// error has occurred - reset the original content in case we have affected it.
		$content = $original;
	} 
	// no 'finally' in php < 5.5, so can't use it. 

	$original = ''; // just empty this param as it may hold a large amount of data
	
	// return $content
	return $content;
}


/*************************** WIDGET SECTION ***************************/
/* 
 * This section handles the display of the widget, this is broadly similar to the display
 * in a post, the main difference is where the settings are retrieved from.
*/

// Create the widget by extending the widget class
class udv_widget extends WP_Widget {

	function __construct() {
		parent::__construct(
			// Base ID of widget
			'udv_widget', 
			// Widget name that will appear in admin UI
			__('Upvote / Downvote', 'udv_widget_domain'), 
			// Widget description
			array( 'description' => __('Provides access to vote counts from upvoting.com'), ) 
		);
	}
	
	// Widget front-end - this is what gets displayed
	// -- parameters
	// $args	: 	array		|	the widget parameters
	// $instance:	widget		|	the widget class instance
	// -- returns
	// nothing
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
			$html = udv_getDisplayOutput($topicid, $topicformat, 'w');
	
			// display the html
			echo __($html, 'udv_widget_domain');
		}
	
		// before and after widget arguments are defined by themes so include them
		echo $args['after_widget'];
	}
			
	// Widget backend (admin interface)
	// -- parameters
	// $instance:	widget		|	the widget class instance
	// -- returns
	// nothing
	public function form($instance) {
		// set default value for the topicids text
		// if this changes remember to change it in the front-end display logic as it is used 
		// to determine a display condition
		if ( isset( $instance[ 'topicids' ] ) ) {
			$topicids = $instance[ 'topicids' ];
		} else {
			$topicids = __( 'Comma separated topic IDs', 'udv_widget_domain' );
		}
		
		// set the default topic format.
		if ( isset( $instance[ 'topicformat' ] ) ) {
			$topicformat = $instance[ 'topicformat' ];
		} else {
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

// Register and load the widget
function udv_loadWidget() {
	register_widget( 'udv_widget' );
}

/*************************** ADMIN SECTION (POST PAGES) ***************************/
/*
 * This section deals with the form that is displayed on 'post' and 'page' pages.
*/

// This adds UDV form elements to the edit and new post pages in the admin.
// -- parameters
// none
// -- returns
// nothing
function udv_addMetaBoxes() {
	// only show on post and page screens
	$screens = array( 'post', 'page' );
	
	// loop over the screens and designate the callback function
	foreach ($screens as $screen) {
		add_meta_box(
			'udv_formID_main',
			__( 'Upvote / Downvote - Vote with a Tweet', 'udv_textdomain' ),
			'udv_formID_main_callback',
			$screen
		);
	}
}

// This is the 'post'/'page' page callback function
// -- parameters
// $post	:	post		|	the post
// -- returns
// nothing
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

// This handles the saving of the udv form control values. Also validates the relevant controls.
// -- parameters
// $post_id	:	integer		|	the post ID
// -- returns
// nothing
function udv_metaBoxesSave($post_id)
{
	// abort if auto save
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

/*************************** ADMIN SECTION (SETTINGS/OPTIONS PAGE) ***************************/
/* 
 * This section deals with the settings page that appears in the backend 'Settings' section.
*/ 

// This function is called by the hook and inserts the options page
// -- parameters
// none
// -- returns
// nothing
function udv_optionsMenu() {
	// add the page to the options UI
	add_options_page('Upvote / Downvote - Vote with a Tweet - Options', 'Upvote / Downvote', 'manage_options', 'udv-uidMenuOptions', 'udv_optionsMenuCallback');	
}

// This function registers the various options sections on the options page
// -- parameters
// none
// -- returns
// nothing
function udv_optionsRegistration(){
	// create the settings sections
	// global settings
	add_settings_section('udv_optionsMenuSection_head', 'Upvote / Downvote - Vote with a Tweet', 'udv_optionsMenuSection_headCallback', 'udv-uidMenuOptions');	
	add_settings_section('udv_optionsMenuSection_html', 'Global Settings', 'udv_optionsMenuSection_htmlCallback', 'udv-uidMenuOptions');
	
	// widget settings
	add_settings_section('udv_optionsWidgetSection_html', 'Widget Settings', 'udv_optionsWidgetSection_htmlCallback', 'udv-uidMenuOptions');
	
	// per banner settings
	add_settings_section('udv_optionsMenuSection_banners', 'Advanced Options', 'udv_optionsMenuSection_bannersCallback', 'udv-uidMenuOptions');
	
	$imageFormats = udv_paramImageFormatsText(); //array
	foreach ($imageFormats as $key => $value)
	{
		add_settings_section('udv_optionsMenuSection_banner:'.$key, 'Image: ' . $value, 'udv_optionsMenuSection_bannerCallback', 'udv-uidMenuOptions');
		udv_adminBannerOptions('udv_optionsMenuSection_banner:'.$key);	
	}
	
	// register the various parameters that we have options for and add the fields to which they relate
	udv_adminGlobalOptions();
	udv_adminWidgetOptions();
}

// This is called when the options page is loaded. Basically we use this to
// check the user permissions and to render the form html.
// -- parameters
// none
// -- returns
// nothing
function udv_optionsMenuCallback() {
	// Ensure that the user has the relevant permissions.
	if (!current_user_can('manage_options')) {
		wp_die(__( 'You do not have sufficient permissions to access this page.'));
	}
	
	echo '<form method="POST" action="options.php">'; // open the form

	// insert and render the settings sections
	settings_fields('udv-uidMenuOptions');
	do_settings_sections('udv-uidMenuOptions');
	
	echo '</div>'; // close the div that contains the advanced options, this is opened elsewhere
	
	submit_button(); // render the submit button
	
	echo '</form>'; // close the form
}

// This function is called by a section when rendered, it displays the heading text
// -- parameters
// none
// -- returns
// nothing
function udv_optionsMenuSection_headCallback() {
	echo '<img style="float:left;padding-right:20px;padding-bottom:20px;" src="' . plugins_url('img/ud1-165x200.png', __FILE__) . '" />';
	echo '<p>The <strong>Upvote / Downvote</strong> plugin for WordPress enables both shortcode, post and widget placement of topic banners. You can choose which topic(s) to display, the banner size and position, and display your topics current scores.</p>';
	echo '<p>No registration or login needed. Anyone can <strong>Vote with a Tweet!</strong></p>';
	echo '<p>Earn money with our advert sharing using Google Adsense.</p>';
	echo '<p>Create your own topics on <a href="https://upvoting.com/create/">Upvoting.com</a></p>';
	echo '<p>For Developer Resources including sample banner sizes and a list of trending topics visit <a href="https://upvoting.net/">Upvoting.net</a></p>';
	echo '<p>Just add our widget to your sidebar to get going! You only need to change these settings if you need to tweak your layout.<br style="clear:left;" /></p>';
}

// This function is called by a section when rendered, it displays the heading text
// -- parameters
// none
// -- returns
// nothing
function udv_optionsMenuSection_htmlCallback() {
	echo 'These are global settings that affect all image sizes, including the widget.';
}

// This function is called by a section when rendered, it displays the heading text
// -- parameters
// none
// -- returns
// nothing
function udv_optionsWidgetSection_htmlCallback() {
	echo 'These settings only affect the widget.';
}

// This function is called by a section when rendered, it displays the heading text
// -- parameters
// none
// -- returns
// nothing
function udv_optionsMenuSection_bannersCallback() {
	echo 'Banner specific settings that affect individual image formats.<br /><br />';
	echo '<script type="text/javascript">
    		jQuery(document).ready(function() {
        		jQuery(".udv_showAdvancedOptions").click(function() {
            		jQuery(".udv_panelAdvancedOptions").slideToggle("slow");
					if (jQuery(".udv_showAdvancedOptions").prop("value") == "Hide Advanced Options")
					{
						jQuery(".udv_showAdvancedOptions").prop("value", "Show Advanced Options");
					} else {
						jQuery(".udv_showAdvancedOptions").prop("value", "Hide Advanced Options");
					}
        		});
    		});
		</script>
		<style type="text/css">
			div.udv_panelAdvancedOptions {
				display: none;
			}
		</style>'
		;	
	echo '<input type="button" class="udv_showAdvancedOptions button button-primary" value="Show Advanced Options" />';
	echo '<div class="udv_panelAdvancedOptions">'; // open the div that contains the advanced options
}

// This function is called by a section when rendered, it displays the heading text
// -- parameters
// none
// -- returns
// nothing
function udv_optionsMenuSection_bannerCallback() { }

///////////////////////////////////////////// Global Options /////////////////////////////////////////////

// The global settings callback function
// -- parameters
// none
// -- returns
// nothing
function udv_adminGlobalOptions() {
	
	// global options name
	$option_name_go   = 'udv:globalOptions';
	
	// fetch existing options
    $option_values_go = get_option($option_name_go);
	
	// get default options
	$default_values_go = udv_paramDefaultGlobalOptions();
	
	// parse option values into predefined keys, discarding unknowns
    $data_go = shortcode_atts($default_values_go, $option_values_go);
	
	// register the setting
	register_setting( 'udv-uidMenuOptions', $option_name_go, 'udv_globalOptionsValidate');
	
	// register setting | surrounding div on/off
	add_settings_field('udv_optionsField_htmlIncludeDiv', 'Include surrounding &lt;div&gt;', 'udv_optionsField_htmlIncludeDivCallback', 'udv-uidMenuOptions', 'udv_optionsMenuSection_html',
		array (
            'label_for'   => 'label1',
            'name'        => 'includeDiv',
            'value'       => esc_attr($data_go['includeDiv']),
            'option_name' => $option_name_go
        )
	);
	
	// register setting | percentage width or fixed width
	add_settings_field('udv_optionsField_htmlImageWidthType', 'Image width type', 'udv_optionsField_htmlImageWidthTypeCallback', 'udv-uidMenuOptions', 'udv_optionsMenuSection_html',
		array (
            'label_for'   => 'label2',
            'name'        => 'imageWidthType',
            'value'       => esc_attr($data_go['imageWidthType']),
            'options'     => array (
                	'percent'  => 'Percentage (default)',
                	'fixed'   => 'Fixed width'
			),
            'option_name' => $option_name_go
        )
	);
	
	// register setting | match container div width to image width
	add_settings_field('udv_optionsField_htmlMatchDivToImageWidth', 'Div width type', 'udv_optionsField_htmlMatchDivToImageWidthCallback', 'udv-uidMenuOptions', 'udv_optionsMenuSection_html',
		array (
            'label_for'   => 'label3',
            'name'        => 'matchDivToImage',
            'value'       => esc_attr($data_go['matchDivToImage']),
            'options'     => array (
                	'false'  => 'Div has no width set (default)',
                	'true'   => 'Div is set to same width as image'
			),
            'option_name' => $option_name_go
        )
	);
	
	// register setting | custom div style
	add_settings_field('udv_optionsField_htmlStyleDiv', '&lt;div&gt; custom style', 'udv_optionsField_htmlStyleDivCallback', 'udv-uidMenuOptions', 'udv_optionsMenuSection_html',
		array (
            'label_for'   => 'label4',
            'name'        => 'div',
            'value'       => esc_attr($data_go['div']),
            'option_name' => $option_name_go
        )
	);
	
	// register setting |  custom img style
	add_settings_field('udv_optionsField_htmlStyleImg', '&lt;img&gt; custom style', 'udv_optionsField_htmlStyleImgCallback', 'udv-uidMenuOptions', 'udv_optionsMenuSection_html',
		array (
            'label_for'   => 'label5',
            'name'        => 'img',
            'value'       => esc_attr($data_go['img']),
            'option_name' => $option_name_go
        )
	);
		
	// register setting | custom href style
	add_settings_field('udv_optionsField_htmlStyleHref', '&lt;a href&gt; custom style', 'udv_optionsField_htmlStyleHrefCallback', 'udv-uidMenuOptions', 'udv_optionsMenuSection_html',
		array (
            'label_for'   => 'label6',
            'name'        => 'href',
            'value'       => esc_attr($data_go['href']),
            'option_name' => $option_name_go
        )
	);
}

// Callback - setting | surrounding div on/off
// -- parameters
// $args	:	array 	|	arguments supplied to callback by setting registration function
// -- returns
// nothing
function udv_optionsField_htmlIncludeDivCallback($args) {
	printf('<input type="checkbox" id="%3$s" name="%1$s[%2$s]" value="%4$s" ' . checked('true', $args['value'], false) . '/>',
		$args['option_name'],
        $args['name'],
        $args['label_for'],
        $args['value']
    );
	
	print 'Uncheck this box to remove the &lt;DIV&gt; wrapper';
}

// Callback - setting | percentage width or fixed width
// -- parameters
// $args	:	array 	|	arguments supplied to callback by setting registration function
// -- returns
// nothing
function udv_optionsField_htmlImageWidthTypeCallback($args) {
	printf('<select name="%1$s[%2$s]" id="%3$s">',
		$args['option_name'],
        $args['name'],
        $args['label_for']	
	);	
	
	foreach ($args['options'] as $val => $title)
        printf(
            '<option value="%1$s" %2$s>%3$s</option>',
            $val,
            selected($val, $args['value'], FALSE),
            $title
        );
	
	print '</select>';
}

// Callback - setting | match container div width to image width
// -- parameters
// $args	:	array 	|	arguments supplied to callback by setting registration function
// -- returns
// nothing
function udv_optionsField_htmlMatchDivToImageWidthCallback ($args) {
	printf('<select name="%1$s[%2$s]" id="%3$s">',
		$args['option_name'],
        $args['name'],
        $args['label_for']	
	);	
	
	foreach ($args['options'] as $val => $title)
        printf(
            '<option value="%1$s" %2$s>%3$s</option>',
            $val,
            selected($val, $args['value'], FALSE),
            $title
        );
	
	print '</select>';
}

// Callback - setting | custom div style
// -- parameters
// $args	:	array 	|	arguments supplied to callback by setting registration function
// -- returns
// nothing
function udv_optionsField_htmlStyleDivCallback($args) {
	printf(
        '<input type="text" name="%1$s[%2$s]" id="%3$s" size="80" class="code" value="%4$s" />',
        $args['option_name'],
        $args['name'],
        $args['label_for'],
        $args['value']
    );
}

// Callback - setting | custom img style
// -- parameters
// $args	:	array 	|	arguments supplied to callback by setting registration function
// -- returns
// nothing
function udv_optionsField_htmlStyleImgCallback($args) {
	printf(
        '<input type="text" name="%1$s[%2$s]" id="%3$s" size="80" class="code" value="%4$s" />',
        $args['option_name'],
        $args['name'],
        $args['label_for'],
        $args['value']
    );
}

// Callback - setting | custom href style
// -- parameters
// $args	:	array 	|	arguments supplied to callback by setting registration function
// -- returns
// nothing
function udv_optionsField_htmlStyleHrefCallback($args) {
	printf(
        '<input type="text" name="%1$s[%2$s]" id="%3$s" size="80" class="code" value="%4$s" />',
        $args['option_name'],
        $args['name'],
        $args['label_for'],
        $args['value']
    );
}

// The validator for the settings in this form section.
// -- parameters
// $values	:	array 	|	array of form elements
// -- returns
// array 	|	the array of values to be serialised and stored in database
function udv_globalOptionsValidate($values) {
	// set the default value for each setting
	$default_values_go = udv_paramDefaultGlobalOptions();
	
	// exit if we have received dodgy data returning the default values
	if (!is_array($values))
        return $default_values_go;
	
	// this will be what we return to store in the db
	$out = array ();
	
	// loop over each default value
	foreach ($default_values_go as $key => $value) {
    	// if the supplied value for the key is empty then add the default value to the out array
    	// unless it is for the checkbox: includeDiv
        if ('includeDiv' !== $key) {
            $out[$key] = trim($values[$key]);
        }
		else {
            // handle the includeDiv checkbox
            if (empty($values[$key]) && 'includeDiv' === $key) {
            	// set setting to 'false'
				$out[$key] = 'false';
			}
			else {
				// if the supplied value is "something", then set setting to 'true'
				$out[$key] = 'true';	
			}
        }
    }

    return $out;		
}

///////////////////////////////////////////// Widget Options /////////////////////////////////////////////

// The widget settings callback function
// -- parameters
// none
// -- returns
// nothing
function udv_adminWidgetOptions() {
			
	// global options
	$option_name_wo   = 'udv:widgetOptions';
	
	// fetch existing options
    $option_values_wo = get_option($option_name_wo);
	
	// get default options
	$default_values_wo = udv_paramDefaultWidgetOptions();
	
	// parse option values into predefined keys, discarding unknowns
    $data_wo = shortcode_atts($default_values_wo, $option_values_wo);
	
	// register the setting
	register_setting('udv-uidMenuOptions', $option_name_wo, 'udv_widgetOptionsValidate');
	
	// register setting | widget option - display in div
	add_settings_field('udv_optionsField_widgetIncludeDiv', 'Include surrounding &lt;div&gt;', 'udv_optionsField_widgetIncludeDivCallback', 'udv-uidMenuOptions', 'udv_optionsWidgetSection_html',
		array (
            'label_for'   => 'label_w1',
            'name'        => 'includeDiv',
            'value'       => esc_attr($data_wo['includeDiv']),
            'options'     => array (
            		'global'  => 'Use global setting (default)',
                	'true'  => 'Include surrounding &lt;div&gt;',
                	'false' => 'Do not include surrounding &lt;div&gt;',
			),
            'option_name' => $option_name_wo
        )
	);
		
	// register setting | widget option - custom div style
	add_settings_field('udv_optionsField_widgetStyleDiv', '&lt;div&gt; custom style', 'udv_optionsField_widgetStyleDivCallback', 'udv-uidMenuOptions', 'udv_optionsWidgetSection_html',
		array (
            'label_for'   => 'label_w2',
            'name'        => 'div',
            'value'       => esc_attr($data_wo['div']),
            'option_name' => $option_name_wo
        )
	);
	
	// register setting | widget option - custom img style
	add_settings_field('udv_optionsField_widgetStyleImg', '&lt;img&gt; custom style', 'udv_optionsField_widgetStyleImgCallback', 'udv-uidMenuOptions', 'udv_optionsWidgetSection_html',
		array (
            'label_for'   => 'label_w3',
            'name'        => 'img',
            'value'       => esc_attr($data_wo['img']),
            'option_name' => $option_name_wo
        )
	);
		
	// register setting | widget option - custom href style
	add_settings_field('udv_optionsField_widgetStyleHref', '&lt;a href&gt; custom style', 'udv_optionsField_widgetStyleHrefCallback', 'udv-uidMenuOptions', 'udv_optionsWidgetSection_html',
		array (
            'label_for'   => 'label_w4',
            'name'        => 'href',
            'value'       => esc_attr($data_wo['href']),
            'option_name' => $option_name_wo
        )
	);
}

// Callback - setting | widget option - display in div
// -- parameters
// $args	:	array 	|	arguments supplied to callback by setting registration function
// -- returns
// nothing
function udv_optionsField_widgetIncludeDivCallback($args) {	
	// add the selection list
	printf('<select name="%1$s[%2$s]" id="%3$s">',
		$args['option_name'],
        $args['name'],
        $args['label_for']
	);	
	
	foreach ($args['options'] as $val => $title)
        printf(
            '<option value="%1$s" %2$s>%3$s</option>',
            $val,
            selected($val, $args['value'], FALSE),
            $title
        );
	
	print '</select>';	
}

// Callback -  setting | widget option - custom div style
// -- parameters
// $args	:	array 	|	arguments supplied to callback by setting registration function
// -- returns
// nothing
function udv_optionsField_widgetStyleDivCallback($args) {
	printf(
        '<input type="text" name="%1$s[%2$s]" id="%3$s" size="80" class="code" value="%4$s" />',
        $args['option_name'],
        $args['name'],
        $args['label_for'],
        $args['value']
    );
}

// Callback - setting | widget option - custom img style
// -- parameters
// $args	:	array 	|	arguments supplied to callback by setting registration function
// -- returns
// nothing
function udv_optionsField_widgetStyleImgCallback($args) {
	printf(
        '<input type="text" name="%1$s[%2$s]" id="%3$s" size="80" class="code" value="%4$s" />',
        $args['option_name'],
        $args['name'],
        $args['label_for'],
        $args['value']
    );
}

// Callback -  setting | widget option - custom href style
// -- parameters
// $args	:	array 	|	arguments supplied to callback by setting registration function
// -- returns
// nothing
function udv_optionsField_widgetStyleHrefCallback($args) {
	printf(
        '<input type="text" name="%1$s[%2$s]" id="%3$s" size="80" class="code" value="%4$s" />',
        $args['option_name'],
        $args['name'],
        $args['label_for'],
        $args['value']
    );
}

// The validator for the settings in this form section.
// -- parameters
// $values	:	array 	|	array of form elements
// -- returns
// array 	|	the array of values to be serialised and stored in database
function udv_widgetOptionsValidate($values) {
		
	// prepare output array
	$out = array();
	
	// exit if we have received dodgy data, returning an empty array
	if (!is_array($values))
        return $out;
	
	// set the default values, updating with supplied values.
	$widgetOptions = shortcode_atts(udv_paramDefaultWidgetOptions(), $values); //array
		
	// populate the settings array
	foreach ($widgetOptions as $key => $value) {
		$out[$key] = trim($values[$key]);
	}
	
	return $out;
}

///////////////////////////////////////////// Per-Banner Options /////////////////////////////////////////////

// The per-banner settings callback function
// -- parameters
// $sectionID:	string		| the name of the settings section that this callback is being called by
// -- returns
// nothing
function udv_adminBannerOptions($sectionID) {

	// get the numeric id from the sectionid string
	$id = substr($sectionID, strrpos($sectionID, ':') + 1);

	// banner options name
	$option_name_bo   = 'udv:bannerOptions';
	
	// fetch existing options as these are in a 2d array we need to get the second dimension
    $option_values_bo = get_option($option_name_bo);
	if (is_array($option_values_bo) && array_key_exists($id, $option_values_bo)) {
		$option_values_bo = $option_values_bo[$id];
	}
	
	// get default styles
	$default_values_bo = udv_paramDefaultBannerOptions();
	
	// parse option values into predefined keys, discarding unknowns
    $data_bo = shortcode_atts($default_values_bo, $option_values_bo);
	
	// register the setting
	register_setting('udv-uidMenuOptions', $option_name_bo, 'udv_bannerOptionsValidate');
	
	// register setting | banner option - display in div
	add_settings_field('udv_optionsField_bannerIncludeDiv', 'Include surrounding &lt;div&gt;', 'udv_optionsField_bannerIncludeDivCallback', 'udv-uidMenuOptions', $sectionID,
		array (
            'label_for'   => 'label_b1',
            'name'        => 'includeDiv',
            'value'       => esc_attr($data_bo['includeDiv']),
            'options'     => array (
            		'global'  => 'Use global setting (default)',
                	'true'  => 'Include surrounding &lt;div&gt;',
                	'false' => 'Do not include surrounding &lt;div&gt;',
			),
            'option_name' => $option_name_bo,
            'id'		  => $id
        )
	);
	
	// register setting | banner option - float
	add_settings_field('udv_optionsField_bannerFloat', 'Alignment', 'udv_optionsField_bannerFloatCallback', 'udv-uidMenuOptions', $sectionID,
		array (
            'label_for'   => 'label_b2:' . $id,
            'name'        => 'float',
            'value'       => esc_attr($data_bo['float']),
            'options'     => array (
                	'none'  => 'None (default)',
                	'left'  => 'Left',
                	'right' => 'Right',
                	'inherit'  => 'Inherit',
			),
            'option_name' => $option_name_bo,
            'id'		  => $id
        )
	);
	
	// register setting | banner option - div style
	add_settings_field('udv_optionsField_bannerDivStyle', '&lt;div&gt; custom style', 'udv_optionsField_bannerDivStyleCallback', 'udv-uidMenuOptions', $sectionID,
		array (
            'label_for'   => 'label_b3:' . $id,
            'name'        => 'div',
            'value'       => esc_attr($data_bo['div']),
            'option_name' => $option_name_bo,
            'id'		  => $id
        )
	);
		
	// register setting | banner option - img style
	add_settings_field('udv_optionsField_bannerImgStyle', '&lt;img&gt; custom style', 'udv_optionsField_bannerImgStyleCallback', 'udv-uidMenuOptions', $sectionID,
		array (
            'label_for'   => 'label_b4:' . $id,
            'name'        => 'img',
            'value'       => esc_attr($data_bo['img']),
            'option_name' => $option_name_bo,
            'id'		  => $id
        )
	);
}

// Callback - setting | banner option - display in div
// -- parameters
// $args	:	array 	|	arguments supplied to callback by setting registration function
// -- returns
// nothing
function udv_optionsField_bannerIncludeDivCallback($args) {	
	// Add a hidden field that holds the imageType ID.
	printf('<input type="hidden" name="%1$s[%4$s][%2$s]" id="%3$s" value="%4$s" />',
		$args['option_name'],
        'id',
        $args['label_for'],
        $args['id']
	);
	
	// add the selection list
	printf('<select name="%1$s[%4$s][%2$s]" id="%3$s">',
		$args['option_name'],
        $args['name'],
        $args['label_for'],
        $args['id']	
	);	
	
	foreach ($args['options'] as $val => $title)
        printf(
            '<option value="%1$s" %2$s>%3$s</option>',
            $val,
            selected($val, $args['value'], FALSE),
            $title
        );
	
	print '</select>';	
}

// Callback - setting | banner option - float
// -- parameters
// $args	:	array 	|	arguments supplied to callback by setting registration function
// -- returns
// nothing
function udv_optionsField_bannerFloatCallback($args) {
	printf('<select name="%1$s[%4$s][%2$s]" id="%3$s">',
		$args['option_name'],
        $args['name'],
        $args['label_for'],
        $args['id']	
	);	
	
	foreach ($args['options'] as $val => $title)
        printf(
            '<option value="%1$s" %2$s>%3$s</option>',
            $val,
            selected($val, $args['value'], FALSE),
            $title
        );
	
	print '</select>';	
}

// Callback - setting | banner option - div style
// -- parameters
// $args	:	array 	|	arguments supplied to callback by setting registration function
// -- returns
// nothing
function udv_optionsField_bannerDivStyleCallback($args) {
	printf(
        '<input type="text" name="%1$s[%5$s][%2$s]" id="%3$s" size="80" class="code" value="%4$s" />',
        $args['option_name'],
        $args['name'],
        $args['label_for'],
        $args['value'],
        $args['id']	
    );
}

// Callback - setting | banner option - img style
// -- parameters
// $args	:	array 	|	arguments supplied to callback by setting registration function
// -- returns
// nothing
function udv_optionsField_bannerImgStyleCallback($args) {
	printf(
        '<input type="text" name="%1$s[%5$s][%2$s]" id="%3$s" size="80" class="code" value="%4$s" />',
        $args['option_name'],
        $args['name'],
        $args['label_for'],
        $args['value'],
        $args['id']	
    );
}

// The validator for the settings in this form section.
// -- parameters
// $values	:	array 	|	array of form elements
// -- returns
// array 	|	the array of values to be serialised and stored in database
function udv_bannerOptionsValidate($values) {
	
	// prepare output array
	$out = array();
	
	// exit if we have received dodgy data, returning an empty array
	if (!is_array($values))
        return $out;
	
	// this is an array of arrays, 2d so loop over 1st dimension.
	foreach ($values as $key_1 => $value_1) {
		// set the default values for each banner, updating with supplied values.
		$bannerOptions = shortcode_atts(udv_paramDefaultBannerOptions(), $value_1); //array
		
		// set the id, this is the imageFormat id and becomes the key in the final 2d array.
		$id = $value_1['id'];
		
		// declare a new array for holding the settings array for this banner.
		$arr = array();
		
		// populate the settings array
		foreach ($bannerOptions as $key => $value) {
			if ($key != 'id') {
				$arr[$key] = trim($value);
			}	
		}
		
		// add settings array to output array with imageformat id as key.
		$out[$id] = $arr;
	}
	
	return $out;
}


// Don't include the php closing tag to avoid header errors as a result of post tag whitespace.
/*End of File*/