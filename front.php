<?php 

/**
*File un-attach - admin settings
*
*@package File un-attach
*@author Hafid Trujillo
*@copyright 20010-2011
*@since 0.5.0
*/
class FunFront{
	
	/**
	*Constructor
	*
	*@return void
	*@since 0.5.0 
	*/
	function __construct(){
		add_action('pre_get_posts',array(&$this,'pre_get_images'),50);
	}
	
	/**
	*Add additional images to the query
	*
	*@param object $query
	*@return void
	*@since 0.5.0
	*/
	function pre_get_images(&$query){
		if(!is_singular()) return;
		
		global $wpdb, $post;
		if( isset( $query->query_vars['post_status'] ) &&
		$query->query_vars['post_status'] == 'inherit' && 
		$query->query_vars['post_parent'] == $post->ID && 
		$query->query_vars['suppress_filters'] == 1){
			
			$results = $wpdb->get_results(
				"SELECT ID FROM $wpdb->posts WHERE $wpdb->posts.post_type = 'attachment'
				AND post_parent = $post->ID OR $wpdb->posts.ID IN( 
					SELECT post_id FROM $wpdb->postmeta 
					WHERE $wpdb->postmeta.meta_key = '_fun-parent'
					AND $wpdb->postmeta.meta_value = $post->ID
				)"
			);
		
			if(empty($results)) return;
			foreach($results as $obj) $ids[] = $obj->ID;
			
			$query->query_vars['include'] = $ids;
			$query->query_vars['post__in'] = $ids;
			unset($query->query_vars['post_parent']);
		}
	}
}
$this->admin = new FunFront( );

/*
* Get post attachments 
* function created by @sebmeric
* Author URI: http://www.sebastien-meric.com
*/
if( !function_exists( 'fun_get_attachments' ) ){ 
	function fun_get_attachments( $args = array() ) {
		global $post;
	
		$defaults = array(
			'post_parent' => 0,
			'post_type' => 'attachment',
			'post_mime_type' => 'image',
			'orderby' => 'menu_order',
			'order' => 'ASC',
			'numberposts' => -1,
			'meta_key' => '',
			'meta_value' => '',
		);
	
		$args = wp_parse_args( $args, $defaults );
	
		if ( !$args['post_parent'] )
			$args['post_parent'] = $post->ID;
	
		if ( !$args['post_parent'] )
			return array();
	
		// usual way to get pdf attached to this post
		$legal_attachments = get_children( $args );
	
		// FileUnattach way to get attachments
		if ( class_exists( 'FileUnattach' ) ) {
			$args['meta_key'] = '_fun-parent';
			$args['meta_value'] = $args['post_parent'];
			$args['post_parent'] = '';
	
			$fun_attachments = get_posts( $args );
		}
	
		$attachments = array_merge( $legal_attachments, $fun_attachments );
	
		// if there are elts in both arrays, then there must be some duplicates.
		// remove those duplicates !
		if ( !empty( $legal_attachments ) && !empty( $fun_attachments ) ) {
			foreach ( $attachments as &$attachment ) {
				$attachment = serialize( $attachment );
			}
	
			$attachments = array_unique( $attachments );
	
			foreach ( $attachments as &$attachment ) {
				$attachment = unserialize( $attachment );
			}
		}
	
		if ( !$attachments ) {
			return array();
		}
	
		return $attachments;
	}
}
?>