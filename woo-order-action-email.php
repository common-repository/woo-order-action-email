<?php
/*
Plugin Name: Woocommerce order action email
Plugin URI: http://rmweblab.com/
Description: Woocommerce order action email allow to add multiple custom order action email and send email to customers.
Author: Anas
Version: 2.0.0
Author URI: http://rmweblab.com
Text Domain: woocommerce_woac
Domain Path: /languages

Copyright: © 2017 RMWebLab.
License: GNU General Public License v3.0
License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/


if ( ! defined( 'ABSPATH' ) ) {
exit;
}

class WC_Custom_Order_Action_Email{

	public $current_action_name = '';
	public $all_action_email = '';

	/**
	 * Constructor
	 */
	public function __construct() {

		/**
		 * Init for custom post type register and other init hook related
		 */
		add_action( 'plugins_loaded', array( $this, 'init' ), 0 );

		add_action( 'admin_init', array( $this, 'send_email_for_custom_order_action' ), 0 );

		add_action( 'wp', array( $this, 'process_email_post' ) );

		/**
		 * Filter name is woocommerce_order_actions to add opiton to action dropdown
		 */
		add_action('woocommerce_order_actions', array( $this, 'woae_wc_order_action' ), 10, 1 );


	}

	/**
	 * Init localisations and hook
	 */
	public function init() {
		//Check if woocommerce plugin is active here
		if (!defined('WC_VERSION')) {
        // no woocommerce
				return;
    }

		//Register Email post type
		require_once('order-action-email.php');

		// Localisation
		load_plugin_textdomain( 'woocommerce_woac', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}

	/**
	 * Call all action published email
	 */
	public function process_email_post(){
		$this->all_action_email  = $this->get_current_order_action_custom_email();
	}

	public function send_email_for_custom_order_action(){

			if(isset($_REQUEST['wc_order_action'])){

				$wc_order_action  = $_REQUEST['wc_order_action'];
				//Check if custom order email
				if (strpos($wc_order_action, '_woae_email') !== false){

					$this->current_action_name = $wc_order_action;
					/**
					 * Filter name is woocommerce_order_action_{$action_slug}
					 */
					add_action( 'woocommerce_order_action_'.$this->current_action_name, array( $this, 'triger_woae_custom_action'), 1 );
				}

			}
	}

	/**
	 * Filter name is woocommerce_order_actions to add opiton to action dropdown
	 */
	public function woae_wc_order_action( $actions ) {
		if ( is_array( $actions ) ) {
			$order_action_email = get_option('order_action_email');
			if(isset($order_action_email) && (count($order_action_email) > 0)){
				foreach ($order_action_email as $action => $label) {
					$actions[$action] = $label;
				}
			}
		}
		return $actions;
	}

	public function triger_woae_custom_action( $order ) {

		$current_order_id = $order->id;
		$request_email_post = $this->current_action_name;
		$request_email_post_arr = explode('_', $request_email_post);
		if(isset($request_email_post_arr[0])){
			$request_email_post_id = trim($request_email_post_arr[0]);
		}

		//If valid request
		if ( get_post_status ( $request_email_post_id ) ) {
			$email_post_title = get_the_title($request_email_post_id);

			//pupulate billing info http://woocommerce.wp-a2z.org/oik_api/wc_api_customersget_customer/
			$billing_email = $order->get_billing_email();

			$email_subject_msg = get_post_meta($request_email_post_id, '_woae_subject', true);
			$search = array();
			$replace = array();
			$search[] = '{order_number}';
			$replace[] = $order->id;
			$email_subject = str_replace($search, $replace, $email_subject_msg);

			$message_send = $this->process_email_message($order, $request_email_post_id);

			$headers  = 'MIME-Version: 1.0' . "\r\n";
			$headers .= 'Content-type: text/plain; charset=iso-8859-1' . "\r\n";
			add_filter('wp_mail_content_type',create_function('', 'return "text/html"; '));
			@wp_mail( $billing_email, $email_subject, $message_send, $headers );

			$order->add_order_note( sprintf( __( '%s email notification manually sent.', 'woocommerce_woac' ), $email_post_title ), false, true );
		}

	}

	public function process_email_message($order, $request_email_post_id){
		$message = '';

		$email_post_content_row   = get_post( $request_email_post_id );
		$email_post_content =  apply_filters( 'the_content', $email_post_content_row->post_content );

		$billing_first_name = $order->get_billing_first_name();
		$billing_last_name = $order->get_billing_last_name();
		$billing_name = $billing_first_name . ' ' . $billing_last_name;
		$order_url = $order->get_view_order_url();

		$search = array();
		$replace = array();
		$search[] = '{billing_first_name}';
		$replace[] = $billing_first_name;

		$search[] = '{billing_last_name}';
		$replace[] = $billing_last_name;

		$search[] = '{billing_name}';
		$replace[] = $billing_name;

		$search[] = '{order_url}';
		$replace[] = $order_url;

		$search[] = '{order_number}';
		$replace[] = $order->id;

		$message = str_replace($search, $replace, $email_post_content);

		return $message;
	}

	public function get_current_order_action_custom_email(){
		$result = array();
		$woae_email_args = array( 'post_type' => 'woae_email', 'posts_per_page' => '-1');
		$woae_email_query = new WP_Query( $woae_email_args );
		if ( $woae_email_query->have_posts() ) {
				while ( $woae_email_query->have_posts() ) {
					$woae_email_query->the_post();
					$email_id = get_the_ID();
					$result[$email_id.'_woae_email'] = get_the_title();
				}
				wp_reset_postdata();
		}
		update_option('order_action_email', $result);
		return $result;
	}


}

new WC_Custom_Order_Action_Email();
