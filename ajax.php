<?php
/**
 * File un-attach - Ajax events
 *
 * @package Image Store
 * @author Hafid Trujillo
 * @copyright 20010-2011
 * @since 0.5.0
 */

//dont cache file
header( 'Last-Modified:' . gmdate( 'D,d M Y H:i:s' ) . ' GMT' );
header( 'Cache-control:no-cache,no-store,must-revalidate,max-age=0' );

//define constants
define( 'WP_ADMIN', true );
define( 'DOING_AJAX', true );

$_SERVER['PHP_SELF'] = "/wp-admin/fun-ajax.php";

//load wp
require_once '../../../wp-load.php';

if ( empty( $_REQUEST['action'] ) )
	die( );

/**
 * Unattach file
 *
 * @return void
 * @since 0.5.0
 */
function file_unattach_unattach_this( ) {
	check_ajax_referer( "funajax" );

	$postid = ( int ) $_GET['postid'];
	$imageid = ( int ) $_GET['imageid'];

	delete_post_meta( $imageid, '_fun-parent', $postid );
	wp_update_post( array( 'ID' => $imageid, 'post_parent' => 0 ) );
}

/**
 * Attach file
 *
 * @return void
 * @since 0.5.0
 */
function file_unattach_attach_this( ) {
	check_ajax_referer( "funajax" );
	
	if( empty( $_GET['postid'] ) || empty( $_GET['imageid'] ) )
		return false;
	
	$postid = ( int ) $_GET['postid'];
	$imageid = ( int ) $_GET['imageid'];

	add_post_meta( $imageid, '_fun-parent', $postid );
}

/**
 * Find posts, function
 * Copied form admin-ajax.php
 *
 * @return void
 * @since 0.5.0
 */
function file_unattach_find_posts( ) {

	check_ajax_referer( "funajax" );
	
	if ( empty( $_GET['ps'] ) )
		exit;

	global $wpdb;
	
	$post_types = get_post_types( array( 'public' => true ), 'objects' );
	unset( $post_types['attachment'] );
	
	$s = stripslashes( $_GET['ps'] );
	$searchand = $search = '';
	$args = array(
		'post_status' => 'any',
		'posts_per_page' => 50,
		'post_type' => array_keys( $post_types ),
	);
	
	if ( '' !== $s )
		$args['s'] = $s;
		
	if( !empty( $_GET['exclude'] ))
		$args['exclude'] = explode( ',', trim($_GET['exclude'], ',') );

	$posts = get_posts( $args );

	if ( !$posts )
		wp_die( __( 'No items found.' ) );

	$postids = array( );
	$html = '<table class="widefat fun-search-results" cellspacing="0"><thead>
	<tr><th class="found-radio">' . __( 'Attached', 'fun' ) . '</th>
	<th>' . __( 'Title', 'fun' ) . '</th>
	<th>' . __( 'Date', 'fun' ) . '</th>
	<th>' . __( 'Status', 'fun' ) . '</th></tr></thead><tbody>';

	foreach ( $posts as $post ) {

		if ( isset( $post->ID ) )
			$postids[] = $post->ID;

		switch ( $post->post_status ) {
			case 'publish' :
			case 'private' :
				$stat = __( 'Published' );
				break;
			case 'future' :
				$stat = __( 'Scheduled' );
				break;
			case 'pending' :
				$stat = __( 'Pending Review' );
				break;
			case 'draft' :
			case 'auto-draft' :
				$stat = __( 'Draft' );
				break;
		}

		$time = ( '0000-00-00 00:00:00' == $post->post_date )  ?  '' : mysql2date( __( 'Y/m/d' ), $post->post_date );

		$html .= '<tr class="found-posts"><td class="found-radio">
			<input type="checkbox" id="fun-found-' . $post->ID . '" name="found_post[' . $post->ID . ']" value="' . esc_attr( $post->ID ) . '"></td>';
		$html .= '<td><label for="found-' . $post->ID . '">' . esc_html( $post->post_title ) . '</label></td>
			<td>' . esc_html( $time ) . '</td><td>' . esc_html( $stat ) . '</td></tr>' . "\n\n";
	}
	
	$html .= '</tbody></table>';
	$html .= '<input name="fun-current-attached" type="hidden" value="' . implode( ',', $postids ) . '" />';

	$x = new WP_Ajax_Response( );
	$x->add( array( 
		'data' => $html,
		'action' => NULL
	 ) );
	$x->send( );

	die( );
}

/**
 * Check if image is attached
 *
 * @return void
 * @since 0.5.0
 */
function file_unattach_is_attached( ){
	
	check_ajax_referer( "funajax" );
	
	if ( empty( $_GET['img'] ) || empty( $_GET['postid'] ))
		exit;

	global $wpdb;

	$postid = ( int ) $_GET['postid'];
	$imageid = ( int ) $_GET['img'];
	
	$posts = $wpdb->get_results( " 
		SELECT ID FROM $wpdb->posts WHERE $wpdb->posts.ID = 
		( SELECT post_parent FROM $wpdb->posts WHERE $wpdb->posts.post_type = 'attachment' 
			AND $wpdb->posts.ID = $imageid ) OR $wpdb->posts.ID IN ( SELECT meta_value FROM $wpdb->postmeta 
			WHERE $wpdb->postmeta.meta_key = '_fun-parent' AND $wpdb->postmeta.post_id = $imageid
			 AND $wpdb->postmeta.meta_value = $postid
		 ) ORDER BY post_date_gmt DESC LIMIT 50 "
	 );
	 	 
	if( !empty( $posts ) ){
		echo '<a class="funattach" id="unattach-' . $imageid . '" href="#" style="display:block">' . __( 'Detach', 'fun' ) . '</a>';
		echo '<span class="fun-message hidden fun-mess-' . $imageid . '">' . __( " Detach this file?", 'fun' ) . '&nbsp;';
		echo '<a class="fun-yes" href="#" id="file-unattch-'. $imageid . '">' . __( 'Yes', 'fun' ) . '</a> &nbsp;';
		echo '<a class="fun-no" href="#" >' . __( 'No', 'fun' ) . '</a></span>';
	}else{
		echo '<a class="fileattach" id="attach-' . $imageid . '" href="#" style="display:block">' . __( 'Attach', 'fun' ) . '</a>';
		echo '<span class="fun-message hidden fun-mess-' . $imageid . '">' . __( "File has been attached", 'fun' ) . '</span>';
	}
}

/**
 * Find posts to which the 
 * image is attached,
 *
 * @return void
 * @since 0.5.0
 */
function file_unattach_find_attached( ) {

	check_ajax_referer( "funajax" );
	
	if ( empty( $_GET['img'] ) )
		exit;

	global $wpdb;

	$postid = ( int ) $_GET['img'];
	
	$posts = $wpdb->get_results( " 
		SELECT ID, post_title, post_status, post_date FROM $wpdb->posts WHERE $wpdb->posts.ID = 
		( SELECT post_parent FROM $wpdb->posts WHERE $wpdb->posts.post_type = 'attachment' 
			AND $wpdb->posts.ID = $postid ) OR $wpdb->posts.ID IN ( SELECT meta_value FROM $wpdb->postmeta 
			WHERE $wpdb->postmeta.meta_key = '_fun-parent' AND $wpdb->postmeta.post_id = $postid 
		 ) ORDER BY post_date_gmt DESC LIMIT 50 "
	 );

	$postids = array( );

	$html = '<table class="widefat fun-search-results" cellspacing="0"><thead>
	<tr><th class="found-radio">' . __( 'Attached', 'fun' ) . '</th>
	<th>' . __( 'Title', 'fun' ) . '</th>
	<th>' . __( 'Date', 'fun' ) . '</th>
	<th>' . __( 'Status', 'fun' ) . '</th></tr>
	</thead><tbody>';

	foreach ( $posts as $post ) {

		if ( isset( $post->ID ) )
			$postids[] = $post->ID;

		switch ( $post->post_status ) {
			case 'publish' :
			case 'private' :
				$stat = __( 'Published' );
				break;
			case 'future' :
				$stat = __( 'Scheduled' );
				break;
			case 'pending' :
				$stat = __( 'Pending Review' );
				break;
			case 'draft' :
			case 'auto-draft' :
				$stat = __( 'Draft' );
				break;
		}
		if ( '0000-00-00 00:00:00' == $post->post_date ) {
			$time = '';
		} else {
			/* translators: date format in table columns, see http://php.net/date */
			$time = mysql2date( __( 'Y/m/d' ), $post->post_date );
		}

		$html .= '<tr class="found-posts"><td class="found-radio"><input type="checkbox" checked="checked" id="fun-found-' . $post->ID . '" name="found_post[' . $post->ID . ']" value="' . esc_attr( $post->ID ) . '"></td>';
		$html .= '<td><label for="found-' . $post->ID . '"><a href="' . get_edit_post_link( $post->ID ) . '">' . esc_html( $post->post_title ) . '</a></label></td><td>' . esc_html( $time ) . '</td><td>' . esc_html( $stat ) . '</td></tr>' . "\n\n";
	}
	$html .= '</tbody></table>';
	$html .= '<input name="fun-current-attached" type="hidden" value="' . implode( ',', $postids ) . '" />';

	$x = new WP_Ajax_Response( );
	$x->add( array( 
		'data' => $html,
		'action' => NULL
	 ) );
	$x->send( );

	die( );
}

switch ( $_GET['action'] ) {
	case 'attach':
		file_unattach_attach_this( );
		break;
	case 'is_attached':
		file_unattach_is_attached( );
		break;
	case 'find_posts':
		file_unattach_find_posts();
		break;
	case 'find_attached':
		file_unattach_find_attached( );
		break;
	case 'unattach':
		file_unattach_unattach_this( );
		break;
	default: die( );
}
?>