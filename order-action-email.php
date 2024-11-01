<?php
add_action( 'init', 'woae_register_email_cpt' );
/**
 * Register a Email post type.
 *
 * @link http://codex.wordpress.org/Function_Reference/register_post_type
 */
function woae_register_email_cpt() {
	$labels = array(
		'name'               => _x( 'Order Action Emails', 'post type general name', 'woocommerce_woac' ),
		'singular_name'      => _x( 'Order Action Email', 'post type singular name', 'woocommerce_woac' ),
		'menu_name'          => _x( 'Order Action', 'admin menu', 'woocommerce_woac' ),
		'name_admin_bar'     => _x( 'Email', 'add new on admin bar', 'woocommerce_woac' ),
		'add_new'            => _x( 'Add New', 'Email', 'woocommerce_woac' ),
		'add_new_item'       => __( 'Add New Email', 'woocommerce_woac' ),
		'new_item'           => __( 'New Email', 'woocommerce_woac' ),
		'edit_item'          => __( 'Edit Email', 'woocommerce_woac' ),
		'view_item'          => __( 'View Email', 'woocommerce_woac' ),
		'all_items'          => __( 'Action Emails', 'woocommerce_woac' ),
		'search_items'       => __( 'Search Emails', 'woocommerce_woac' ),
		'parent_item_colon'  => __( 'Parent Emails:', 'woocommerce_woac' ),
		'not_found'          => __( 'No Emails found.', 'woocommerce_woac' ),
		'not_found_in_trash' => __( 'No Emails found in Trash.', 'woocommerce_woac' )
	);
	$args = array(
		'labels'             => $labels,
    'description'        => __( 'Description.', 'woocommerce_woac' ),
		'public'             => false,
		'publicly_queryable' => false,
		'show_ui'            => true,
		'show_in_menu'       => 'woocommerce',
		'query_var'          => true,
		'rewrite'            => array( 'slug' => 'order-action-email' ),
		'capability_type'    => 'post',
		'has_archive'        => true,
		'hierarchical'       => false,
		'menu_position'      => 10,
		'supports'           => array( 'title', 'editor' )
	);

	register_post_type( 'woae_email', $args );
}


// define the edit_form_after_editor callback
function action_edit_form_after_email_editor( $post ) {
			//only work for email post type
			if($post->post_type != 'woae_email'){
				return;
			}
			echo '<p class="description">Message Support Tags:{billing_first_name}, {billing_last_name}, {billing_name}, {order_url}, {order_number}</p>';
};

// add the action
add_action( 'edit_form_after_editor', 'action_edit_form_after_email_editor', 10, 1 );


/**
 * Add meta box
 *
 * @param post $post The post object
 * @link https://codex.wordpress.org/Plugin_API/Action_Reference/add_meta_boxes
 */
function woae_email_settings_meta_boxes( $post ){
	add_meta_box( 'woae_email_settings_meta_box', __( 'Email Settings', 'woocommerce_woac' ), 'woae_email_settings_build_meta_box', 'woae_email', 'normal', 'high' );
}
add_action( 'add_meta_boxes_woae_email', 'woae_email_settings_meta_boxes' );

function woae_email_settings_build_meta_box( $post ){
	// make sure the form request comes from WordPress
	wp_nonce_field( basename( __FILE__ ), 'woae_settings_box_nonce' );
	if(isset($_GET['post']) && ($_GET['action'] == 'edit')){
		// retrieve the _woae_subject current value
		$woae_subject = esc_html(get_post_meta( $post->ID, '_woae_subject', true ));
	}else{
		if($woae_subject == ''){
			$woae_subject = __( 'Order #{order_number} Action Email', 'woocommerce_woac' );
		}
	}

	?>
	<div class='inside'>
		<table class="form-table">
		<tr>
		<th scope="row"><label for="woae_subject"><?php echo __( 'Email Subject', 'woocommerce_woac' ); ?></label></th>
		<td><input type="text" class="regular-text" name="woae_subject" id="woae_subject" value="<?php echo $woae_subject; ?>" placeholder="<?php echo __( 'Email Subject', 'woocommerce_woac' ); ?>">
		<p class="description" id="woae_subject-description"><?php echo __( 'Support Tags: {order_number}', 'woocommerce_woac' ); ?></p></td>
		</tr>
		</table>
	</div>
	<?php
}

/**
 * Store custom field meta box data
 *
 * @param int $post_id The post ID.
 * @link https://codex.wordpress.org/Plugin_API/Action_Reference/save_post
 */
function woae_email_settings_save_meta_box_data( $post_id ){
	// verify meta box nonce
	if ( !isset( $_POST['woae_settings_box_nonce'] ) || !wp_verify_nonce( $_POST['woae_settings_box_nonce'], basename( __FILE__ ) ) ){
		return;
	}
	// return if autosave
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ){
		return;
	}
  // Check the user's permissions.
	if ( ! current_user_can( 'edit_post', $post_id ) ){
		return;
	}
	// store custom fields values
	if ( isset( $_REQUEST['woae_subject'] ) ) {
		update_post_meta( $post_id, '_woae_subject', sanitize_text_field( $_POST['woae_subject'] ) );
	}

}
add_action( 'save_post_woae_email', 'woae_email_settings_save_meta_box_data' );
