<?php

/**
 * File un-attach - admin settings
 *
 * @package File un-attach
 * @author Hafid Trujillo
 * @copyright 20010-2011
 * @since 0.5.0
 */

class FunAdmin {

	/**
	 * Attached 
	 * images ids array
	 */
	var $ids = array( );
	var $image_sort = array( );

	/**
	 * Constructor
	 *
	 * @return void
	 * @since 0.5.0 
	 */
	function __construct( ) {
		
		add_filter( 'attachment_fields_to_edit', array( &$this, 'attachment_fields' ), 10, 2 );
		
		if ( defined( 'DOING_AJAX' ) || defined( 'DOING_AUTOSAVE' ) )
			return;
		
		add_action( 'pre_get_posts', array( &$this, 'pre_get_images' ), 50 );

		if( is_admin() ){
			
			add_action( 'admin_init', array( &$this, 'init_actions' ), 50 );
			add_action( 'admin_footer', array( &$this, 'admin_footer' ), 50 );
			add_action( 'wp_enqueue_media', array( &$this, 'enqueue_media' ), 50 );
			add_action( 'admin_print_scripts', array( &$this, 'load_admin_scripts' ), 1 );
			add_action( 'manage_media_custom_column', array( &$this, 'custom_column' ), 10, 3 );
			add_action( 'attachment_submitbox_misc_actions', array( &$this, 'attachment_submitbox' ) );
	
			add_filter( 'media_upload_tabs', array( &$this, 'gallery_tab' ), 60 );
			add_filter( 'manage_upload_columns', array( &$this, 'add_columns' ), 10 );
			
		} 
	}
	
	/**
	 *Modify media-template.php file to suport attachment links 
	 *
	 * return void
	 * @since 1.0.5
	 */
	function enqueue_media(){
		remove_action( 'admin_footer', 'wp_print_media_templates' );
		
		require_once FUNATTACH_ABSPATH. '/inc/media-template.php';
		add_action( 'admin_footer', 'fun_print_media_templates' );
	}
	
	/**
	 *Display attach links in singe attachment edit screen
	 *
	 * return void
	 * @since 1.0.5
	 */
	function attachment_submitbox( ){
		echo '<div class="fun-attach misc-pub-section"><label>' . __( 'Attached to:', 'fun' ) . '</label> ';
			$this->attached_link( );
		echo '</div>';
	}

	/**
	 * Add ID Column
	 *
	 * @param array $columns
	 * return array
	 * @since 0.5.0
	 */
	function add_columns( $columns ) {
		unset( $columns['parent'] );
		if ( current_user_can( 'upload_files' ) )
			$columns['fun-attach'] = _x( 'Attached to', 'column name', 'fun' );
		return $columns;
	}
	
	/**
	 * Display attach links
	 *
	 * @param unit $postid
	 * return void
	 * @since 1.0.5
	 */
	function attached_link( $postid = false ){
		global $post;
		
		if( !$postid ) $postid = $post->ID;
		$attach = get_post_meta( $postid, "_fun-parent" );
		
		if ( ( empty( $attach ) && $post->post_parent ) || ( count( $attach ) == 1 && empty( $post->post_parent ) )
				|| ( count( $attach ) == 1 && $attach[0] == $post->post_parent ) ) {

			$parent = ( count( $attach ) == 1 ) ? $attach[0] : $post->post_parent;
			$title = _draft_or_post_title( $parent );
			
			echo '<strong><a href="' . get_edit_post_link( $parent ) . '" >' . $title . '</a></strong><br />';
			echo '<a href="#" id="attached-list-' . $postid . '" class="attached-list">' . __( 'Attach', 'fun' ) . '</a><span> | </span>';
			echo '<a href="#" class="fun-unattach-row" id="file-unattch-' . $postid . '">' . __( 'Detach', 'fun' ) . '</a>';
			
		} elseif ( ( $attach && $post->post_parent ) || ( count( $attach ) > 1 ) ) {

			echo '<strong><a href="#" id="attached-list-' . $postid . '" class="attached-list">'
			. __( 'Multiple', 'fun' ) . '</a></strong>';
			
		} else {

			echo __( '( Unattached )', 'fun' ) . "<br />\n";
			echo '<a href="#" id="fun-find-posts-' . $postid . '" class="fun-find-posts">' . __( 'Attach', 'fun' ) . '</a>';
		}
	}

	/**
	 * Add value to ID album Column
	 *
	 * @param string $column_name
	 * @param unit $postid
	 * return void
	 * @since 0.5.0
	 */
	function custom_column( $column_name, $id ) {
		if ( $column_name != 'fun-attach' )
			return;

		$this->attached_link( $id );
		do_action( 'fun_custom_column', $column_name, $id );
	}

	/**
	 * Add unattch button to media row
	 *
	 * @param array $form_fields
	 * @param object $post
	 * return array
	 * @since 0.5.0
	 */
	function attachment_fields( $form_fields, $image ) {

		//[alx359] added. If in media libary, do not create attach/unattach buttons
		if ( empty( $this->tab ) )
			return $form_fields;

		if ( $this->tab == 'gallery' || $this->tab == 'type' || ( empty( $this->tab ) && DOING_AJAX ) ) {
			if ( isset( $this->image_sort[$image->ID] ) )
				$form_fields['menu_order']['value'] = $this->image_sort[$image->ID];
			
			$form_fields['funattach'] = array( 
				'input' => 'html',
				'label' => __( 'Detach' ),
				'html' => '<input type="button" id="unattach-' . $image->ID . '" value="' . __( 'Detach', 'fun' ) . '" class="button funattach" />
				<span class="fun-message hidden fun-mess-' . $image->ID . '">' . __( " Detach this file?", 'fun' ) . '&nbsp;
				<a href="#" class="fun-yes" id="file-unattch-' . $image->ID . '">' . __( 'Yes', 'fun' ) . '</a>&nbsp; &#8226; &nbsp; 
				<a href="#" class="fun-no">' . __( 'No', 'fun' ) . '</a></span><br />',
			 );
			 
		} elseif ( $this->tab == 'library' && !in_array( $image->ID, $this->ids ) ) {
			$form_fields['fileattach'] = array( 
				'input' => 'html',
				'label' => __( 'Attach' ),
				'html' => '<input type="button" id="attach-' . $image->ID . '" value="' . __( 'Attach', 'fun' ) . '" class="button fileattach" />
				<span class="fun-message hidden fun-mess-' . $image->ID . '">' . __( "File has been attached.", 'fun' ) . '</span><br />',
			 );
		}
		
		return apply_filters( 'fun_attachment_fields', $form_fields, $image );
	}

	/**
	 * Load admin scripts
	 *
	 * @return void
	 * @since 0.5.0
	 */
	function load_admin_scripts( ) {
		global $post;	
		
		$postid = ( isset( $post->ID ) )	 ? $post->ID : false;
		wp_enqueue_script( 'fun-admin', FUNATTACH_URL . 'admin.js', array( 'jquery' ), 'fun' , true );
		wp_localize_script( 'fun-admin', 'funlocal', apply_filters( 'fun_localize_script', array( 
			'postid' => $postid,
			'attach' => __( 'Attach', 'fun' ),
			'detach' => __( 'Detach', 'fun' ),
			'adminurl' => FUNATTACH_URL,
			'nonceajax' => wp_create_nonce( 'funajax' ),
		 ) ) );
	}

	/**
	 * Add additional images to the query
	 *
	 * @param object $query
	 * @return void
	 * @since 0.5.0
	 */
	function pre_get_images( &$query ) {
		global $pagenow;

		if ( empty( $_GET['tab'] ) ||
				$_GET['tab'] != 'gallery'
				|| $pagenow != 'media-upload.php'
				|| empty( $this->results ) )
			return;

		$query->query_vars['include'] = $this->ids;
		$query->query_vars['post__in'] = $this->ids;

		do_action( 'fun_pre_get_images' );

		unset( $query->query_vars['post_parent'] );
	}

	/**
	 * Count images attached
	 *
	 * @param array $tabs
	 * @return array
	 * @since 0.5.0
	 */
	function gallery_tab( $tabs ) {
		global $pagenow, $wpdb;

		if ( $pagenow != 'media-upload.php' )
			return $tabs;

		$postid = ( int ) $_GET['post_id'];
		$this->results = $wpdb->get_results( 
				"SELECT ID FROM $wpdb->posts WHERE $wpdb->posts.post_type = 'attachment'
			AND post_parent = $postid OR $wpdb->posts.ID IN( 
				SELECT post_id FROM $wpdb->postmeta 
				WHERE $wpdb->postmeta.meta_key = '_fun-parent'
				AND $wpdb->postmeta.meta_value = $postid
			 )"
		 );

		if ( empty( $this->results ) )
			return $tabs;
		foreach ( $this->results as $obj )
			$this->ids[$obj->ID] = $obj->ID;

		//insert and re-arrenge tabs
		$lib = $tabs['library'];
		unset( $tabs['library'] );
		$tabs['gallery'] = sprintf( __( 'Gallery ( %s )', 'fun' ), "<span id='attachments-count'>" . count( $this->results ) . "</span>" );
		$tabs['library'] = $lib;

		return $tabs;
	}

	/**
	 * Create unique sort order per gallery
	 *
	 * @return array
	 * @since 0.5.0
	 */
	function init_actions( ) {
		global $pagenow;
		
		if( isset( $_GET['post'] ) && $postid = ( int ) $_GET['post']  )
			$post = get_post( $postid );
		
		if ( isset( $_GET['fun-find-posts-submit'] ) && ( $pagenow == 'upload.php' 
			||  isset( $post->post_type ) && $post->post_type == 'attachment' ) ) {
				
			$imageid = ( int ) $_GET['media'][0];

			do_action( 'fun_before_saving_attachment', $imageid );

			if ( isset( $_GET['found_post'] ) && is_array( $_GET['found_post'] ) ) {
				foreach ( $_GET['found_post'] as $post_id ) {
					delete_post_meta( $imageid, '_fun-parent', $post_id );
					add_post_meta( $imageid, '_fun-parent', $post_id );
				}
			}

			if ( empty( $_GET['fun-search'] ) ) {
				$attached = explode( ',', $_GET['fun-current-attached'] );
				foreach ( $attached as $id ) {
					if ( isset( $_GET['found_post'][$id] ) || !is_numeric( $id ) )
						continue;
					delete_post_meta( $imageid, '_fun-parent', $id );
				}
			}

			$parent = array_shift( $_GET['found_post'] );
			wp_update_post( array( 'ID' => $imageid, 'post_parent' => $parent ) );
			
			if( isset( $_GET['post'] ) && isset( $_GET['action'] ) )
				$pagenow .= '?post=' . $_GET['post'] . '&action='.$_GET['action'];
			
			wp_redirect( admin_url( $pagenow ) . "#post-$imageid" );
			exit( );
		}

		$this->tab = isset( $_GET['tab'] ) ? $_GET['tab'] : false;
		
		if ( $this->tab == 'gallery' ) {

			$this->post_id = ( int ) $_GET['post_id'];
			$this->image_sort = maybe_unserialize( get_post_meta( $this->post_id, '_fun-image-sort', true ) );
			
			if ( empty( $_GET['attachments'] ) )
				return;
			
			$this->image_sort = array( );	
			
			foreach ( $_POST['attachments'] as $attachment_id => $attachment )
				$this->image_sort[$attachment_id] = $attachment['menu_order'];

			do_action( 'fun_before_saving_post', $this->post_id,$this->image_sort );
			
			update_post_meta( $this->post_id, '_fun-image-sort', $this->image_sort );
		}
		
	}

	/**
	 * Create pop box to attach images
	 *
	 * @return void
	 * @since 0.5.0
	 */
	function admin_footer( ) {
		global $pagenow, $post;

		if ( $pagenow != 'upload.php' && !empty( $post->post_type ) && $post->post_type != 'attachment')
			return;
		?>
        <form id="fun-posts-filter" action="" method="get">
        
			<?php do_action( 'fun_before_results_form' ); ?>

			<div id="fun-find-posts" class="find-box" style="display:none">
				<div id="fun-find-posts-head" class="find-box-head"><?php _e( 'Find Posts or Pages', 'fun' ); ?></div>
				<div class="find-box-inside">
					<div class="find-box-search">
                    
						<?php if ( !empty( $_GET['action'] ) ) { ?>
                        <input type="hidden" name="post" value="<?php echo esc_attr( $post->ID ) ?>" />
                        <input type="hidden" name="action" value="<?php echo esc_attr( $_GET['action'] ); ?>" />
                   		<?php } ?>
                            
						<input type="hidden" name="affected" id="fun-affected" value="" />
						<label class="screen-reader-text" for="find-posts-input"><?php _e( 'Search', 'fun' ); ?></label>
						<input type="text" id="fun-find-posts-input" name="ps" value="" />
						<input type="button" id="fun-find-posts-search" value="<?php esc_attr_e( 'Search', 'fun' ); ?>" class="button" /><br />

					</div>	
					<div id="fun-find-posts-response" style="margin:8px 0"></div>
				</div>

				<?php do_action( 'fun_after_results_form' ); ?>

				<div class="find-box-buttons">
					<input id="fun-find-posts-close" type="button" class="button alignleft" value="<?php esc_attr_e( 'Close' ); ?>" />
					<input id="fun-find-posts-submit" name="fun-find-posts-submit" type="submit" class="button-primary alignright" value="<?php esc_attr_e( 'Save', 'fun' ); ?>" />
				</div>
			</div>
            
        </form>   
		<?php
	}

}

$this->admin = new FunAdmin( );
?>