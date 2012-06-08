<?php 
/**
*File un-attach - admin settings
*
*@package File un-attach
*@author Hafid Trujillo
*@copyright 20010-2011
*@since 0.5.0
*/
class FunAdmin{
	
	/**
	* Attached 
	* images ids array
	*/
	var $ids = array();
	
	/**
	*Constructor
	*
	*@return void
	*@since 0.5.0 
	*/
	function __construct( ){
		
		add_filter( 'attachment_fields_to_edit', array( &$this,'attachment_fields'),10,2);
		
		if(defined( 'DOING_AJAX') || defined( 'DOING_AUTOSAVE')) return;
		add_action( 'admin_init', array( &$this,'init_actions'),50);
		add_action( 'admin_footer', array( &$this,'admin_footer'),50);
		add_action( 'pre_get_posts', array( &$this,'pre_get_images'),50);
		add_action( 'admin_print_scripts', array( &$this,'load_admin_scripts'),1);
		add_action( 'manage_media_custom_column', array( &$this,'custom_column'),10,3);
		
		add_filter( 'media_upload_tabs', array( &$this,'gallery_tab'),60);
		add_filter( 'manage_upload_columns', array( &$this,'add_columns'),10);
	}

	/**
	*Add ID Column
	*
	*@param array $columns
	*return array
	*@since 0.5.0
	*/
	function add_columns($columns){
		unset($columns['parent']);
		if(current_user_can( 'upload_files')) 
			$columns['fun-attach'] = _x( 'Attached to', 'column name', $this->domain );
		return $columns;
	}
	
	/**
	* Add value to ID album Column
	*
	*@param string $column_name
	*@param unit $postid
	*return void
	*@since 0.5.0
	*/
	function custom_column( $column_name, $id ){
		if($column_name != 'fun-attach') return;
		
		global $post;
		$attach = get_post_meta( $id,"_fun-parent" );
		if( ( empty($attach) && $post->post_parent ) || ( count( $attach ) ==1 && empty( $post->post_parent ) )
			|| ( count( $attach ) ==1 && $attach[0] == $post->post_parent ) ){
				
			$parent = (count($attach)==1) ? $attach[0] : $post->post_parent;
			$title =_draft_or_post_title( $parent );
			echo '<strong><a href="'.get_edit_post_link($parent) . '" >'.$title.'</a></strong><br />';
			echo '<a href="#" id="attached-list-'.$id.'" class="attached-list">' . __( 'Attach', $this->domain ) . '</a><span> | </span>';
			echo '<a href="#" class="fun-unattach-row" id="file-unattch-'.$post->ID.'">' . __( 'Detach', $this->domain ) . '</a>';
			
		}elseif( ( $attach && $post->post_parent ) || (count($attach)>1)){
			
			echo '<strong><a href="#" id="attached-list-'.$id.'" class="attached-list">'
			.__( 'Multiple', $this->domain ) . '</a></strong>';
			
		}else{ 
		
			echo __( '(Unattached)', $this->domain )."<br />\n"; 
			echo '<a href="#" id="fun-find-posts-'.$id.'" class="fun-find-posts">' . __( 'Attach', $this->domain ) . '</a>';
			
		}
		
		do_action( 'fun_custom_column', $column_name, $id );
	}
	
	/**
	* Add unattch button to media row
	*
	*@param array $form_fields
	*@param object $post
	*return array
	*@since 0.5.0
	*/
	function attachment_fields($form_fields, $post){
		
		//[alx359] added. If in media libary, do not create attach/unattach buttons
		if(empty($this->tab)) 
			return $form_fields;
	
		if( $this->tab == 'gallery' || $this->tab == 'type' || (empty($this->tab) && DOING_AJAX)){
			if( isset( $this->image_sort[$post->ID])) 
				$form_fields['menu_order'] = $this->image_sort[$post->ID];
			$form_fields['funattach'] = array(
				'input'	=> 'html',
				'label'	=> __( 'Detach'),
				'html'		=> '<input type="button" name="unattach-'.$post->ID.'" value="' . __( 'Detach', $this->domain ) . '" class="button funattach" />
				<span class="fun-message hidden fun-mess-'.$post->ID.'">' . __(" Detach this file?", $this->domain ) . '&nbsp;
				<a href="#" class="fun-yes" id="file-unattch-'.$post->ID.'">' . __( 'Yes', $this->domain ) . '</a>&nbsp; &#8226; &nbsp; 
				<a href="#" class="fun-no">' . __( 'No', $this->domain ) . '</a></span><br />',
			);
		}elseif( $this->tab == 'library' && !in_array( $post->ID, $this->ids )){
			$form_fields['fileattach'] = array(
				'input'	=> 'html',
				'label'	=> __( 'Attach' ),
				'html'		=> '<input type="button" name="attach-'.$post->ID.'" value="' . __( 'Attach', $this->domain ) . '" class="button fileattach" />
				<span class="fun-message hidden fun-mess-'.$post->ID.'">' . __("File has been attached.", $this->domain ) . '</span><br />',
			);
		}
		return apply_filters( 'fun_attachment_fields', $form_fields, $post );
	}
	
	/**
	*Load admin scripts
	*
	*@return void
	*@since 0.5.0
	*/
	function load_admin_scripts(){
		wp_enqueue_script( 'fun-admin', FUNATTACH_URL . 'admin.js' , array( 'jquery'), $this->domain , true);
		wp_localize_script( 'fun-admin','funlocal', apply_filters( 'fun_localize_script' , array(
			'adminurl' => FUNATTACH_URL,
			'nonceajax'	=> wp_create_nonce( 'funajax'),
			'unattach' => __( 'Detach', $this->domain ),
		)));
	}
	
	/**
	*Add additional images to the query
	*
	*@param object $query
	*@return void
	*@since 0.5.0
	*/
	function pre_get_images(&$query){
		global $pagenow;
		
		if( empty( $_GET['tab'] ) ||
		$_GET['tab'] != 'gallery' 
		|| $pagenow != 'media-upload.php'
		|| empty($this->results)) 
		return;
			
		$query->query_vars['include'] = $this->ids;
		$query->query_vars['post__in'] = $this->ids;
		
		do_action( 'fun_pre_get_images' );
		
		unset($query->query_vars['post_parent']);
	}
	
	/**
	*Count images attached
	*
	*@param array $tabs
	*@return array
	*@since 0.5.0
	*/
	function gallery_tab($tabs){
		global $pagenow, $wpdb;
		
		if($pagenow != 'media-upload.php') 
			return $tabs;
		
		$postid = (int)$_GET['post_id'];
		$this->results = $wpdb->get_results(
			"SELECT ID FROM $wpdb->posts WHERE $wpdb->posts.post_type = 'attachment'
			AND post_parent = $postid OR $wpdb->posts.ID IN( 
				SELECT post_id FROM $wpdb->postmeta 
				WHERE $wpdb->postmeta.meta_key = '_fun-parent'
				AND $wpdb->postmeta.meta_value = $postid
			)"
		);
		
		if(empty($this->results)) return $tabs;
		foreach($this->results as $obj)
			$this->ids[$obj->ID] = $obj->ID;
		
		//insert and re-arrenge tabs
		$lib = $tabs['library']; unset($tabs['library']);
		$tabs['gallery'] = sprintf(__( 'Gallery (%s)', $this->domain ), 
		"<span id='attachments-count'>".count($this->results)."</span>");
		$tabs['library'] = $lib;
		
		return $tabs;
	}
	
	/**
	*Create unique sort order per gallery
	*
	*@return array
	*@since 0.5.0
	*/
	function init_actions(){ 
		global $pagenow, $FileUnattach;
		
		$this->domain = $FileUnattach->domain;
		
		if( isset( $_GET['fun-find-posts-submit'] ) && $pagenow == 'upload.php' ){
			$imageid = (int)$_GET['media'][0];
			
			do_action( 'fun_before_saving_attachment', $imageid );

			if( isset($_GET['found_post']) && is_array($_GET['found_post'])){
				foreach( $_GET['found_post'] as $post_id ){
					delete_post_meta( $imageid, '_fun-parent', $post_id );
					add_post_meta( $imageid, '_fun-parent', $post_id );
				}
			}
			
			if( empty( $_GET['fun-search'] ) ){
				$attached = explode( ',',$_GET['fun-current-attached']);			
				foreach( $attached as $id ){
					if( isset($_GET['found_post'][$id]) || !is_numeric($id) ) 
						continue;
					delete_post_meta( $imageid, '_fun-parent', $id );
				}
			}
			
			$parent = array_shift($_GET['found_post']);
			wp_update_post( array( 'ID' =>$imageid, 'post_parent' => $parent));
			wp_redirect( admin_url($pagenow) . "#post-$imageid" );
			exit( );
		}
		
		$this->tab = isset( $_GET['tab'] ) ? $_GET['tab'] : false;
		if($this->tab == 'gallery'){
			$this->post_id = (int)$_GET['post_id'];
			$this->image_sort = (empty($_POST['attachments'])) ? 
			maybe_unserialize(get_post_meta($this->post_id,'_fun-image-sort',true)):'';
			
			if(empty($_POST['attachments'])) return;
			foreach($_POST['attachments'] as $attachment_id => $attachment )
				$this->image_sort[$attachment_id] = $attachment['menu_order'];
			
			$data = serialize($this->image_sort);
			do_action( 'fun_before_saving_post', $this->post_id , $data );
			update_post_meta($this->post_id,'_fun-image-sort',$data);
		}
	}
	
	/**
	*Create pop box to attach images
	*
	*@return void
	*@since 0.5.0
	*/
	function admin_footer(){
		global $pagenow;
		if($pagenow != 'upload.php') return;
	?>
	<form id="fun-posts-filter" action="" method="get">
	
	<?php do_action( 'fun_before_results_form' ); ?>
	
	<div id="fun-find-posts" class="find-box" style="display:none">
		<div id="fun-find-posts-head" class="find-box-head"><?php _e( 'Find Posts or Pages', $this->domain ); ?></div>
		<div class="find-box-inside">
			<div class="find-box-search">
	
				<input type="hidden" name="affected" id="fun-affected" value="" />
				<label class="screen-reader-text" for="find-posts-input"><?php _e( 'Search', $this->domain ); ?></label>
				<input type="text" id="fun-find-posts-input" name="ps" value="" />
				<input type="button" id="fun-find-posts-search" value="<?php esc_attr_e( 'Search', $this->domain );?>" class="button" /><br />

				<?php
				$post_types = get_post_types( array( 'public' => true), 'objects' );
				foreach ( $post_types as $post ) {
					if ( 'attachment' == $post->name )
						continue;
				?>
				<input type="radio" name="find-posts-what" id="fun-find-posts-<?php echo esc_attr($post->name); ?>" value="<?php echo esc_attr($post->name); ?>" <?php checked($post->name,'post'); ?> />
				<label for="fun-find-posts-<?php echo esc_attr($post->name); ?>"><?php echo $post->label; ?></label>
				<?php
				} ?>
			</div>	
			<div id="fun-find-posts-response"></div>
		</div>
		
		<?php do_action( 'fun_after_results_form' ); ?>
		
		<div class="find-box-buttons">
			<input id="fun-find-posts-close" type="button" class="button alignleft" value="<?php esc_attr_e( 'Close'); ?>" />
			<input id="fun-find-posts-submit" name="fun-find-posts-submit" type="submit" class="button-primary alignright" value="<?php esc_attr_e( 'Save',  $this->domain ); ?>" />
		</div>
	</div>
	</form>
	<?php
	}

}
$this->admin = new FunAdmin();
?>