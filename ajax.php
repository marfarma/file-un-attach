<?php 
/**
 *File un-attach - Ajax events
 *
 *@package Image Store
 *@author Hafid Trujillo
 *@copyright 20010-2011
 *@since 0.5.0
*/

//dont cache file
header('Expires:0');
header('Pragma:no-cache');
header('Cache-control:private');
header('Last-Modified:'.gmdate('D,d M Y H:i:s').' GMT');
header('Cache-control:no-cache,no-store,must-revalidate,max-age=0');

//define constants
define('WP_ADMIN',true);
define('DOING_AJAX',true);

//load wp
require_once '../../../wp-load.php';

//make sure that the request came from the same domain	
if(stripos($_SERVER['HTTP_REFERER'],get_bloginfo('siteurl')) === false) 
	die();

/**
 *Unattach file
 *
 *@return void
 *@since 0.5.0
*/
function file_unattach_unattach_this(){
	check_ajax_referer("funajax");
	
	$postid = (int)$_GET['postid'];
	$imageid = (int)$_GET['imageid'];
	
	delete_post_meta($imageid,'_fun-parent',$postid);
	wp_update_post(array( 'ID' => $imageid, 'post_parent' => 0));

}

/**
 *Attach file
 *
 *@return void
 *@since 0.5.0
*/
function file_unattach_attach_this(){
	check_ajax_referer("funajax");
	
	$postid = (int)$_GET['postid'];
	$imageid = (int)$_GET['imageid'];
	
	add_post_meta($imageid,'_fun-parent',$postid);
}

/**
 *Find posts, function
 *Copied form admin-ajax.php
 *
 *@return void
 *@since 0.5.0
*/
function file_unattach_find_posts(){
	
	check_ajax_referer("funajax");
	if ( empty($_GET['ps']) ) exit;
	
	global $wpdb;

	if ( !empty($_GET['post_type']) && in_array( $_GET['post_type'], get_post_types() ) )
		$what = $_GET['post_type'];
	else $what = 'post';

	$s = stripslashes($_GET['ps']);
	preg_match_all('/".*?("|$)|((?<=[\\s",+])|^)[^\\s",+]+/', $s, $matches);
	$search_terms = array_map('_search_terms_tidy', $matches[0]);

	$searchand = $search = '';
	foreach ( (array) $search_terms as $term ) {
		$term = esc_sql( like_escape( $term ) );
		$search .= "{$searchand}(($wpdb->posts.post_title LIKE '%{$term}%') OR ($wpdb->posts.post_content LIKE '%{$term}%'))";
		$searchand = ' AND ';
	}
	$term = esc_sql( like_escape( $s ) );
	if ( count($search_terms) > 1 && $search_terms[0] != $s )
		$search .= " OR ($wpdb->posts.post_title LIKE '%{$term}%') OR ($wpdb->posts.post_content LIKE '%{$term}%')";
	
	if(!empty($_GET['exclude']))
		$exclude = " AND $wpdb->posts.ID NOT IN (".trim($_GET['exclude'],',').") ";
	
	$posts = $wpdb->get_results( "SELECT ID, post_title, post_date, post_status FROM $wpdb->posts WHERE post_type = '$what' AND post_status IN ('draft', 'publish') AND ($search) $exclude ORDER BY post_date_gmt DESC LIMIT 50" );

	if ( ! $posts ) {
		$posttype = get_post_type_object($what);
		echo '<div class="fun-search-results">'.$posttype->labels->not_found.'</div>';
		exit();
	}
	
	$html = '<table class="widefat fun-search-results" cellspacing="0"><thead><tr><th class="found-radio"><br /></th><th>'.__('Title').'</th><th>'.__('Date').'</th><th>'.__('Status').'</th></tr></thead><tbody>';
	foreach ( $posts as $post ) {

		switch ( $post->post_status ) {
			case 'publish' :
			case 'private' :
				$stat = __('Published');
				break;
			case 'future' :
				$stat = __('Scheduled');
				break;
			case 'pending' :
				$stat = __('Pending Review');
				break;
			case 'draft' :
				$stat = __('Draft');
				break;
		}
		
		if ( '0000-00-00 00:00:00' == $post->post_date ) {
			$time = '';
		} else {
			/* translators: date format in table columns, see http://php.net/date */
			$time = mysql2date(__('Y/m/d'), $post->post_date);
		}

		$html .= '<tr class="found-posts"><td class="found-radio"><input type="checkbox" id="fun-found-'.$post->ID.'" name="found_post['.$post->ID.']" value="' . esc_attr($post->ID) . '"></td>';
		$html .= '<td><label for="found-'.$post->ID.'">'.esc_html( $post->post_title ).'</label></td><td>'.esc_html( $time ).'</td><td>'.esc_html( $stat ).'</td></tr>'."\n\n";
	}
	$html .= '</tbody></table>';

	$x = new WP_Ajax_Response();
	$x->add( array(
		'what' => $what,
		'data' => $html
	));
	$x->send();

}

/**
 *Find posts to which the 
 *image is attached,
 *
 *@return void
 *@since 0.5.0
*/
function file_unattach_find_attached(){
	
	check_ajax_referer("funajax");
	if ( empty($_GET['img']) ) exit;
	
	global $wpdb;
	
	$postid = (int)$_GET['img'];
	$posts = $wpdb->get_results( " 
		SELECT ID, post_title, post_status, post_date FROM $wpdb->posts WHERE $wpdb->posts.ID = 
		( SELECT post_parent FROM $wpdb->posts WHERE $wpdb->posts.post_type = 'attachment' 
			AND $wpdb->posts.ID = $postid ) OR $wpdb->posts.ID IN ( SELECT meta_value FROM ims_postmeta 
			WHERE $wpdb->postmeta.meta_key = '_fun-parent' AND $wpdb->postmeta.post_id = $postid 
		)  ORDER BY post_date_gmt DESC LIMIT 50 "
	);
	
	$postids = array();
	$html = '<table class="widefat fun-files-attached" cellspacing="0"><thead><tr><th class="found-radio"><br /></th><th>'.__('Title').'</th><th>'.__('Date').'</th><th>'.__('Status').'</th></tr></thead><tbody>';
	foreach ( $posts as $post ) {
		$postids[] =  $post->ID;
		switch ( $post->post_status ) {
			case 'publish' :
			case 'private' :
				$stat = __('Published');
				break;
			case 'future' :
				$stat = __('Scheduled');
				break;
			case 'pending' :
				$stat = __('Pending Review');
				break;
			case 'draft' :
				$stat = __('Draft');
				break;
		}
		
		
		if ( '0000-00-00 00:00:00' == $post->post_date ) {
			$time = '';
		} else {
			/* translators: date format in table columns, see http://php.net/date */
			$time = mysql2date(__('Y/m/d'), $post->post_date);
		}

		$html .= '<tr class="found-posts"><td class="found-radio"><input type="checkbox" checked="checked" id="fun-found-'.$post->ID.'" name="found_post['.$post->ID.']" value="' . esc_attr($post->ID) . '"></td>';
		$html .= '<td><label for="found-'.$post->ID.'"><a href="'.get_edit_post_link($post->ID).'">'.esc_html( $post->post_title ).'</a></label></td><td>'.esc_html( $time ).'</td><td>'.esc_html( $stat ).'</td></tr>'."\n\n";
	}
	$html .= '</tbody></table>';
	$html .= '<input name="fun-current-attached" type="hidden" value="'.implode(',',$postids).'" />';

	$x = new WP_Ajax_Response();
	$x->add( array(
		'data' => $html
	));
	$x->send();
}

switch($_GET['action']){
	case 'attach':
		file_unattach_attach_this();
		break;
	case 'find_posts':
		file_unattach_find_posts();
		break;
	case 'find_attached':
		file_unattach_find_attached();
		break;
	case 'unattach':
		file_unattach_unattach_this();
		break;
	default: die();
}


?>