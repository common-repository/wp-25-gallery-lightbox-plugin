<?php
/*
Plugin Name: WP 2.5 Gallery Lightbox
Plugin URI: http://www.samburdge.co.uk/plugins
Description: Adds Lightbox to WordPress 2.5 Gallery Feature
Version: 1.3
Author: Sam Burdge
Author URI: http://www.samburdge.co.uk
*/
/*
UPDATES
25/4/2008
Minor update to version 1.1
- updated jquery version to 1.2.3 to fix IE7 bug.

28/4/2008
Minor update to version 1.2 
- added priority of 12 to add_filter to bring in line with 2.5.1 shortcode priority.
- moved style elements to the head of the page so that the gallery XHTML code validates! (<style> tags are not allowed in the body of the page)

29/4/2008
No version update
- minor bug fix - changed the modifier of the first preg_replace statement to U (ungreedy)

2/5/2008
Minor update to version 1.3 
- css bugfix for safari 3.1.1
*/

function wp_lightbox_js(){

if(!is_admin()){
echo '<script type="text/javascript" src="'.get_bloginfo('url').'/wp-content/plugins/wp_gallery_lightbox/jquery_lightbox/js/jquery-1.2.3.pack.js"></script>
<script type="text/javascript" src="'.get_bloginfo('url').'/wp-content/plugins/wp_gallery_lightbox/jquery_lightbox/js/jquery.lightbox.js"></script>
<link rel="stylesheet" href="'.get_bloginfo('url').'/wp-content/plugins/wp_gallery_lightbox/jquery_lightbox/css/jquery.lightbox.packed.css" type="text/css" />
';

}
}

add_action('wp_print_scripts','wp_lightbox_js');


function normal_lightbox($content){
global $post;
$thePostID = $post->ID;
return preg_replace('/<a(.*?)href="(.*?).(jpg|jpeg|png|gif|bmp|ico)"(.*?)><img/U', '<a$1href="$2.$3" $4 rel="lightbox-'.$thePostID.'"><img', $content);
}

function gallery_lightbox($content){
global $post;
$thePostID = $post->ID;
$thestr = preg_replace('/<a(.*?)href=(.*?)attachment(.*?) (.*?)><img(.*?)src=(.*?).(jpg|jpeg|png|gif|bmp|ico)(.*?)\/>/i', '<a$1href=$6.$7" $4 rel="lightbox-'.$thePostID.'"><img$5src=$6.$7$8/>', $content);

$thestr2 = preg_replace('/<a(.*?)href=(.*?)-(\d+)x(\d+)/i','<a$1href=$2', $thestr);

return $thestr2;
}

add_filter('the_content', 'gallery_lightbox',12);
add_filter('the_content', 'normal_lightbox',2);

remove_shortcode('gallery');
add_shortcode('gallery', 'gallery_shortcode2');

function gallery_shortcode2($attr) {
	global $post;

	// Allow plugins/themes to override the default gallery template.
	$output = apply_filters('post_gallery', '', $attr);
	if ( $output != '' )
		return $output;

	// We're trusting author input, so let's at least make sure it looks like a valid orderby statement
	if ( isset( $attr['orderby'] ) ) {
		$attr['orderby'] = sanitize_sql_orderby( $attr['orderby'] );
		if ( !$attr['orderby'] )
			unset( $attr['orderby'] );
	}

	extract(shortcode_atts(array(
		'orderby'    => 'menu_order ASC, ID ASC',
		'id'         => $post->ID,
		'itemtag'    => 'dl',
		'icontag'    => 'dt',
		'captiontag' => 'dd',
		'columns'    => 3,
		'size'       => 'thumbnail',
	), $attr));

	$id = intval($id);
	$attachments = get_children("post_parent=$id&post_type=attachment&post_mime_type=image&orderby={$orderby}");

	if ( empty($attachments) )
		return '';

	if ( is_feed() ) {
		$output = "\n";
		foreach ( $attachments as $id => $attachment )
			$output .= wp_get_attachment_link($id, $size, true) . "\n";
		return $output;
	}

	$listtag = tag_escape($listtag);
	$itemtag = tag_escape($itemtag);
	$captiontag = tag_escape($captiontag);
	$columns = intval($columns);
	$itemwidth = $columns > 0 ? floor(100/$columns) : 100;
	
	$output = apply_filters('gallery_style', "
		
		<!-- see gallery_shortcode() in wp-includes/media.php -->
		<div class='gallery'>");

	foreach ( $attachments as $id => $attachment ) {
		$link = wp_get_attachment_link($id, $size, true);
		$output .= "<{$itemtag} class='gallery-item' style='width: {$itemwidth}%'>";
		$output .= "
			<{$icontag} class='gallery-icon'>
				$link
			</{$icontag}>";
		if ( $captiontag && trim($attachment->post_excerpt) ) {
			$output .= "
				<{$captiontag} class='gallery-caption'>
				{$attachment->post_excerpt}
				</{$captiontag}>";
		}
		$output .= "</{$itemtag}>";
		if ( $columns > 0 && ++$i % $columns == 0 )
			$output .= '<br style="clear: both" />';
	}

	$output .= "
			<br style='clear: both;' />
		</div>\n";

	return $output;
}

function gallery_stylesheet(){
echo '
<style type="text/css">
.gallery {
margin: auto;
}

.gallery-item {
float: left;
margin: 10px 0 0 0;
padding: 0px;
text-align: center;
}

.gallery img {
border: 1px solid #cfcfcf;
padding: 0;
margin: 0;
}

.gallery-caption {
margin-left: 0;
}
</style>
';
}

add_action('wp_head','gallery_stylesheet');

?>