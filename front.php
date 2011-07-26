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
		if($query->query_vars['post_status'] == 'inherit' && 
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
$this->admin = new FunFront();
?>