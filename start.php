<?php
/*
Plugin Name: WordPress iSell - Sell Digital Downloads
Description: All in one plugin let you sell your digital products and manage your orders from WordPress.
Author: Muneeb
Version: 1.4
Author URI: http://imuneeb.com/wordpress-sell-digital-downloads-wordpress-isell/
Plugin URI: http://imuneeb.com/wordpress-sell-digital-downloads-wordpress-isell/
Copyright 2012 Muneeb ur Rehman http://imuneeb.com
*/

define( 'iSell_Path', plugin_dir_path(__FILE__) );
define( 'iSell_Dir_Name', dirname( plugin_basename( __FILE__ ) ));
Class WordPress_iSell{
	private $settings;
	function __construct(){
		$this->start();
	}
	function start(){
		

		//start functions and code here
		$this->constants();
		$this->includes();
		$this->actions();
		$this->filters();
		$this->options();

		//isell settings/options
		$this->settings = get_option('isell_options');

	}
	function includes(){
		if ( file_exists(WP_PLUGIN_DIR.'/isell-pluggable.php') )
			include(WP_PLUGIN_DIR.'/isell-pluggable.php');

		include(iSell_Path . 'inc/file_handler.php');
		include(iSell_Path . 'inc/functions.php');
	}
	function actions(){
		//load scripts only on admin dashboard
		add_action('admin_enqueue_scripts',array($this,'admin_enqueue'));

		//init actions
		add_action('init',array($this,'product_post_type'));

		//add meta boxes
		add_action('add_meta_boxes',array($this,'add_meta_boxes'));

		//save post hook to save metabox values
		add_action('save_post',array($this,'save_product_metabox_settings'));
		add_action('save_post',array($this,'save_order_metabox_settings'));

		//change encytype to multipart/form-data of post edit form
		add_action('post_edit_form_tag', array($this,'post_edit_form_tag'));

		//Ajax file uploader
		add_action("wp_ajax_isell_file_upload", array($this,"product_file_upload"));

		//Ajax file delete
		add_action("wp_ajax_isell_delete_file", array($this,"product_delete_file"));

		//file download
		add_action("wp_ajax_isell_download_file", array($this,"product_download_file"));
		add_action("wp_ajax_nopriv_isell_download_file", array($this,"product_download_file"));

		//process paypal ipn
		add_action("wp_ajax_isell_paypal_ipn", array($this,"process_paypal_ipn"));
		add_action("wp_ajax_nopriv_isell_paypal_ipn", array($this,"process_paypal_ipn"));

		//redirect if 'iproduct' is set, to paypal buy now page
		add_action('init',array($this,'do_product_redirect'));

		//create isell settings/options page
		add_action('admin_menu', 'isell_settings_page');

		//send a new order notification email to admin and send an email containing a product download link to customer
		add_action('isell_payment_completed',array($this,'send_notification_emails'),10,2);

		//isell errors shortcode
		add_shortcode('isell_errors',array($this,'isell_errors'));
		

	}
	function filters(){
		//change the message text for product post and order post
		add_filter('post_updated_messages', 'isell_change_product_post_messages');
		add_filter('post_updated_messages', 'isell_change_order_post_messages');

		//remove the post row actions from orders and products custom post type
		add_filter( 'post_row_actions', 'isell_remove_post_row_actions', 10, 1 );

	}
	function constants(){
		//error_codes
		define('ISELL_INVALID_TXN_ID',1);
		define('ISELL_PAYMENT_NOT_COMPLETED',2);
		define('ISELL_DOWNLOAD_LINK_EXPIRED',3);
		define('ISELL_DOWNLOAD_EXCEED_ERROR',4);
		define('ISELL_NO_FILE',5);

		//file chunk size
		define('iSell_CHUNK_SIZE', 1024*6024);
	}
	function options(){
		$errors = array(
				ISELL_INVALID_TXN_ID => __('The transcaction ID is invalid.','isell'),
				ISELL_PAYMENT_NOT_COMPLETED => __('Your payment is not yet completed yet please contact us to resolve the issue.','isell'),
				ISELL_DOWNLOAD_LINK_EXPIRED => __('The product download link is expired. Please contact us','isell'),
				ISELL_DOWNLOAD_EXCEED_ERROR => __('You had exceeded the maximum number of downloads allowed for an order. Contact us to resolve this issue','isell'),
				ISELL_NO_FILE => __('The product does not have any files','isell')
		);
$emails = array(
		'order_customer_product_download' => array(
				'subject' => __('Your {product_name} File Download - Order {txn_id}','isell'),
				'message' => __('Dear {customer_name},

Thank you for your order.  You may download using the following URL:

{product_download_url}','isell')
			),
		'admin_new_order' => array(
				'subject' => __('iSell Notification: New Order: {txn_id} - {customer_name} - {product_name}','isell'),
				'message' => __('You have received a new order for {product_name}

To view/edit the order, visit the following address:

{edit_order_link}','isell')
			)  
	);
		$isell_options = array(
				'paypal' => array(
						'email' => 'example@example.com',
						'platform' => 'sandbox'
					),
				'store' => array(
						'currency' => 'USD',
						'error_page' => '',
						'errors' => $errors,
						'emails' => $emails,
						'thanks_page' => ''
					),
				'isell' => array(
						'version' => '1.4',
						'developer' => 'Muneeb'
					),
				'file_management' => array(
						'directory_name' => uniqid(),
						'max_downloads' => 5
					),
				'advanced' => array(
						'use_fsockopen_or_curl' => 'fsockopen'
				)
			);
		if (get_option('isell_options') )
			$isell_options = get_option('isell_options');
		else
			add_option('isell_options',$isell_options,'','yes');

	}
	function reset_options(){
		$errors = array(
				ISELL_INVALID_TXN_ID => __('The transcaction ID is invalid.','isell'),
				ISELL_PAYMENT_NOT_COMPLETED => __('Your payment is not yet completed yet please contact us to resolve the issue.','isell'),
				ISELL_DOWNLOAD_LINK_EXPIRED => __('The product download link is expired. Please contact us','isell'),
				ISELL_DOWNLOAD_EXCEED_ERROR => __('You had exceeded the maximum number of downloads allowed for an order. Contact us to resolve this issue','isell'),
				ISELL_NO_FILE => __('The product does not have any files','isell')
		);
$emails = array(
		'order_customer_product_download' => array(
				'subject' => __('Your {product_name} File Download - Order {txn_id}','isell'),
				'message' => __('Dear {customer_name},

Thank you for your order.  You may download using the following URL:

{product_download_url}','isell')
			),
		'admin_new_order' => array(
				'subject' => __('iSell Notification: New Order: {txn_id} - {customer_name} - {product_name}','isell'),
				'message' => __('You have received a new order for {product_name}

To view/edit the order, visit the following address:

{edit_order_link}','isell')
			)  
	);
		$isell_options = array(
				'paypal' => array(
						'email' => 'example@example.com',
						'platform' => 'sandbox'
					),
				'store' => array(
						'currency' => 'USD',
						'error_page' => '',
						'errors' => $errors,
						'emails' => $emails,
						'thanks_page' => ''
					),
				'isell' => array(
						'version' => '1.4',
						'developer' => 'Muneeb'
					),
				'file_management' => array(
						'directory_name' => uniqid(),
						'max_downloads' => 5
					),
				'advanced' => array(
						'use_fsockopen_or_curl' => 'fsockopen'
				)
			);
		return update_option('isell_options',$isell_options);

	}
	function product_delete_file(){
		//delete the file attached to the product also removes file records from that product
		global $current_user;
		$response = array(
				'status' => 1,
				'message' => __('File successfully deleted.','isell')
			);
		//check permissions
		if ( (!$current_user->allcaps['manage_options'] && !$current_user->allcaps['edit_posts']) || !wp_verify_nonce( $_REQUEST['nonce'], "isell_file_delete") ){
			$response = array(
				'status' => -1,
				'message' => __('You do not have sufficient permissions.','isell')
			);
			die(json_encode($response));
		}
		
		$post_id = (int)$_REQUEST['post_id'];
		$file_name = get_post_meta($post_id,'orginal_file_name',true);
		$file_handler = new iSell_File_Handler($post_id,$file_name);
		if ( !$file_handler->delete_file($post_id) ){
			$response = array(
				'status' => 2,
				'message' => __('Unable to delete the file either file is deleted or does not exist also please make sure you have set the upload directory permissions compatible with this plugin. The file records for this product may get deleted.','isell')
			);
			
		}
		die(json_encode($response));
	}
	function product_file_upload(){
		//upload the file and then update or create the file records for the product.
		$response = array(
				'status' => 1,
				'message' => __('File successfully uploaded.','isell')
			);
		global $current_user;
		//check permissions
		if ( (!$current_user->allcaps['manage_options'] && !$current_user->allcaps['edit_posts']) || !wp_verify_nonce( $_REQUEST['nonce'], "isell_file_upload") ){

			$response = array(
				'status' => -1,
				'message' => __('You do not have sufficient permissions.','isell')
			);
			die(json_encode($response));
		}

		//no cache headers
		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
		header("Cache-Control: no-store, no-cache, must-revalidate");
		header("Cache-Control: post-check=0, pre-check=0", false);
		header("Pragma: no-cache");
		header('Content-type: application/json');

		// 10 minutes execution time
		@set_time_limit(10 * 60); 

		//init the isell file uploader class and validate the request then move the file upload work to the file handler class
		
		if (isset($_FILES['file']['tmp_name']) && is_uploaded_file($_FILES['file']['tmp_name'])){
			$file = new iSell_File_Handler($_REQUEST['post_id'],isset($_REQUEST["name"]) ? $_REQUEST["name"] : '',$_FILES['file']);
		}else{
			$response = array(
				'status' => 2,
				'message' => __('Failed to move uploaded file.','isell')
			);
		}

		die(json_encode($response));

	}
	function admin_enqueue($page){

		//these scripts are only added to the admin screen
		wp_enqueue_style('isell-all.css',plugins_url('css/all.css',__FILE__));
		wp_enqueue_style("wp-jquery-ui-dialog");
		
		global $wp_version;
		if ( version_compare($wp_version,"3.3","<") ){
			wp_enqueue_script('jquery-ui-widget');
			wp_enqueue_script('jquery-ui-progressbar',plugins_url('js/jquery-ui-progressbar.js',__FILE__),array('jquery-ui-widget'));
		}else{
			wp_enqueue_script('jquery-ui-progressbar');
		}
		
		wp_enqueue_script('plupload.js',plugins_url('js/plupload-full.js',__FILE__),array('jquery'),false,true);
		wp_enqueue_script('isell-all.js',plugins_url('js/all.js',__FILE__),array('jquery'),false,true);
		wp_localize_script( 'isell-all.js', 'isell', 
			array('ajaxurl' => admin_url( 'admin-ajax.php'),
				'flash_swf_url' => plugins_url('js/plupload.flash.swf',__FILE__),
				'silver_xap_url' => plugins_url('js/plupload.silverlight.xap',__FILE__),
				'file_upload_nonce' => wp_create_nonce('isell_file_upload'),
				'file_delete_nonce' => wp_create_nonce('isell_file_delete')
			   ));

	}
	function product_post_type(){
		$product_labels = array(
		    'name' => _x('Products', 'post type general name'),
		    'singular_name' => _x('Product', 'post type singular name'),
		    'add_new' => _x('Add New', 'Product'),
		    'add_new_item' => __('Add New Product'),
		    'edit_item' => __('Edit Product'),
		    'new_item' => __('New Product'),
		    'all_items' => __('All Products'),
		    'view_item' => __('View Product'),
		    'search_items' => __('Search Products'),
		    'not_found' =>  __('No Products found'),
		    'not_found_in_trash' => __('No Products found in Trash'), 
		    'parent_item_colon' => '',
		    'menu_name' => __('Products')

	  	);
	  	$product_args = array(
		    'labels' => $product_labels,
		    'public' => false,
		    'publicly_queryable' => false,
		    'show_ui' => true, 
		    'show_in_menu' => true, 
		    'query_var' => false,
		    'rewrite' => false,
		    'capability_type' => 'post',
		    'has_archive' => false, 
		    'hierarchical' => false,
		    'menu_position' => null,
		    'supports' => array( null )
	  	);
	  	$order_labels = array(
		    'name' => _x('Orders', 'post type general name'),
		    'singular_name' => _x('Order', 'post type singular name'),
		    'add_new' => _x('Add New', 'Order'),
		    'add_new_item' => __('Add New Order'),
		    'edit_item' => __('Edit Order'),
		    'new_item' => __('New Order'),
		    'all_items' => __('All Orders'),
		    'view_item' => __('View Order'),
		    'search_items' => __('Search Orders'),
		    'not_found' =>  __('No Orders found'),
		    'not_found_in_trash' => __('No Orders found in Trash'), 
		    'parent_item_colon' => '',
		    'menu_name' => __('Orders')

	  	);
	  	$order_args = array(
		    'labels' => $order_labels,
		    'public' => false,
		    'publicly_queryable' => false,
		    'show_ui' => true, 
		    'show_in_menu' => true, 
		    'query_var' => false,
		    'rewrite' => false,
		    'capability_type' => 'post',
		    'has_archive' => false, 
		    'hierarchical' => false,
		    'menu_position' => null,
		    'supports' => array( null )
	  	);
	  	register_post_type('isell-product',$product_args);
	  	register_post_type('isell-order',$order_args);
	}
	function add_meta_boxes(){
		add_meta_box( 
        'product_info_meta_box',
        __( 'Product Info', 'isell' ),
        array($this,'product_info_metabox'),
        'isell-product'
    	);
    	add_meta_box( 
        'product_file_meta_box',
        __( 'File', 'isell' ),
        array($this,'product_file_metabox'),
        'isell-product' 
    	);
    	add_meta_box( 
        'order_buyer_info',
        __( 'Buyer Info', 'isell' ),
        array($this,'order_buyer_info_metabox'),
        'isell-order' 
    	);
    	add_meta_box( 
        'order_payment_info',
        __( 'Payment Info', 'isell' ),
        array($this,'order_payment_info_metabox'),
        'isell-order' 
    	);
    	add_meta_box( 
        'order_product_info',
        __( 'Product Info', 'isell' ),
        array($this,'order_product_info_metabox'),
        'isell-order',
        'side'
    	);
	}
	function product_info_metabox($post){
		$post_id  = $post->ID;
		if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) return $post_id;
		$currency = $this->settings['store']['currency'];
		include_once(iSell_Path.'views/metabox_product_info.php');
	}
	function product_file_metabox($post){
		$post_id  = $post->ID;
		if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) return $post_id;
		include_once(iSell_Path.'views/metabox_product_file.php');
	}
	function order_product_info_metabox($post){
		$post_id  = $post->ID;
		if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) return $post_id;
		$product_info = get_post_meta($post_id,'product_info',true);
		$payment_info = get_post_meta($post_id,'payment_info',true);
		
		if ( !$product_info ){
			$product_info = array(
					'id' => '',
					'name' => '',
					'download_url' => '',
					'downloads' => '',
					'link_status' => ''
				);
		}else{
			$product_info['name'] = get_post_meta($product_info['id'],'product_name',true);
			$download_url = sprintf("%s?action=%s&product=%s&order=%s&trans=%s",admin_url( 'admin-ajax.php'),'isell_download_file',$product_info['id'],$post_id,$payment_info['txn_id']);
			$product_info['download_url'] = $download_url;
		}
		include_once(iSell_Path.'views/metabox_order_product_info.php');
	}
	function order_buyer_info_metabox($post){
		$post_id  = $post->ID;
		if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) return $post_id;
		$buyer_info = get_post_meta($post_id,'buyer_info',true);
		if ( !$buyer_info ){
			$buyer_info = array(
					'first_name' => '',
					'last_name' => '',
					'email' => '',
					'phone' => '',
					'country' => '',
					'state' => '',
					'city' => '',
					'zip' => ''
				);
		}
		include_once(iSell_Path.'views/metabox_order_buyer_info.php');
	}
	function order_payment_info_metabox($post){
		$post_id  = $post->ID;
		if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) return $post_id;
		$currency = $this->settings['store']['currency'];
		$payment_info = get_post_meta($post_id,'payment_info',true);

		if ( !$payment_info ){
			$payment_info = array(
				'status' => '',
				'amount_paid' => '',
				'txn_id' => ''
			);
		}
		include_once(iSell_Path.'views/metabox_order_payment_info.php');
	}
	function save_order_metabox_settings($post_id){
		if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) return $post_id;
		if ( !current_user_can( 'edit_post', $post_id ) )	return;
		if ( wp_is_post_revision( $post_id ) )return;
		if ( !isset($_POST['order_token']) )	return;
		$first_name = $_POST['first_name'];
		$last_name = $_POST['last_name'];
		$email = $_POST['email'];
		$phone = $_POST['phone'];
		$country = $_POST['country'];
		$state = $_POST['state'];
		$city = $_POST['city'];
		$zip = $_POST['zip'];
		$payment_status = $_POST['payment_status'];
		$amount_paid = $_POST['amount_paid'];
		$txn_id = $_POST['txn_id'];
		$product_id = (int)$_POST['product_id'];
		$link_status = $_POST['product_link_status'];
		$buyer_info = array(
				'first_name' => esc_html($first_name),
				'last_name' => esc_html($last_name),
				'email' => esc_html($email),
				'phone' => esc_html($phone),
				'country' => esc_html($country),
				'state' => esc_html($state),
				'city' => esc_html($city),
				'zip' => esc_html($zip)
			);
		
		$payment_info = array(
				'status' => $payment_status,
				'amount_paid' => $amount_paid,
				'txn_id' => $txn_id
			);
		$product_info = get_post_meta($post_id,'product_info',true);
		if ( !$product_info ){
			$product_info = array(
					'id' => $product_id,
					'name' => '',
					'download_url' => '',
					'downloads' => '',
					'link_status' => ''
				);
		}
		$product_info['id'] = $product_id;
		$product_info['link_status'] = $link_status;

		$title = sprintf("Order: %s | ID: %s | Status: %s",get_post_meta($product_info['id'],'product_name',true),$txn_id,$payment_status);
		//change the post title to order title
		remove_action('save_post',array($this,'save_order_metabox_settings'));
		
		wp_update_post(array(
				'ID' => $post_id,
			 	'post_title' =>  $title
		));
		
		add_action('save_post',array($this,'save_order_metabox_settings'));
		//end change title to order title

		update_post_meta($post_id,'buyer_info',$buyer_info);
		update_post_meta($post_id,'payment_info',$payment_info);
		update_post_meta($post_id,'product_info',$product_info);
		update_post_meta($post_id,'txn_id',$txn_id);

	}
	function save_product_metabox_settings($post_id){
		if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) return $post_id;
		if ( !current_user_can( 'edit_post', $post_id ) )	return;
		if ( wp_is_post_revision( $post_id ) )return;
		if ( !isset($_POST['product_token']) )	return;
		$product_name = stripslashes($_POST['product_name']);
		$product_price = stripslashes($_POST['product_price']);
		$product_file_name = stripslashes($_POST['product_file_name']);
		//change the post title to product name
		remove_action('save_post',array($this,'save_product_metabox_settings'));
		
		if ( !empty($product_name) ){
			wp_update_post(array(
				'ID' => $post_id,
			 	'post_title' =>  $product_name
			));
		}else{
			wp_update_post(array(
				'ID' => $post_id,
			 	'post_title' =>  'Product ' . $post_id
			));
		}
		add_action('save_post',array($this,'save_product_metabox_settings'));
		//end change title to product name
		
		update_post_meta($post_id,'product_name',$product_name);
		update_post_meta($post_id,'product_file_name',$product_file_name);
		
		if ( is_numeric($product_price) ){
			update_post_meta($post_id,'product_price',$product_price);
		}else{
			update_post_meta($post_id,'',$product_price);
		}
	}
	
	function post_edit_form_tag() {
    	echo ' enctype="multipart/form-data"';
	}
	
	function product_download_file(){
		do_action('isell_product_download');
		
		if ( !isset($_REQUEST['product']) || !isset($_REQUEST['order']) || !isset($_REQUEST['trans'])  ){
			die();
		}
	 	
		$product_id = (int)$_REQUEST['product'];
		$order_id = (int)$_REQUEST['order'];
		$trans_id = (int)$_REQUEST['trans'];
		$options = isell_get_options();
		$error_page = $options['store']['error_page'];
		$error_page = apply_filters('isell_error_page_url', $error_page);
		$max_downloads = (int)$options['file_management']['max_downloads'];

		if ( !is_int($product_id) || !is_int($order_id) || !is_int($trans_id) ){
			die();
		}
		$payment_info = get_post_meta($order_id,'payment_info',true);
		$product_info = get_post_meta($order_id,'product_info',true);
		if ( get_post_status($order_id) != 'publish' ) die();
		if ( !get_post_meta($product_id,'product_file',true) || !$payment_info || !$product_info ){
			//invalid parameters do nothing
			die(0);
		}
		
		if ( $payment_info['txn_id'] != $trans_id  ){
			//invalid transaction id
			isell_error_redirect(ISELL_INVALID_TXN_ID,$error_page);
		}
		if ( strtolower($payment_info['status']) != 'completed' ){
			//payment is pending or not made 
			isell_error_redirect(ISELL_PAYMENT_NOT_COMPLETED,$error_page);
		}
		if ( strtolower($product_info['link_status']) != 'valid' ){
			//link has been expired
			isell_error_redirect(ISELL_DOWNLOAD_LINK_EXPIRED,$error_page);
		}
		if ( (int)$product_info['downloads'] >= (int)$max_downloads  ){
			//downloads exceeds the max number of downloads allowed in settings
			isell_error_redirect(ISELL_DOWNLOAD_EXCEED_ERROR,$error_page);
		}
		
		$file_name = get_post_meta($product_id,'product_file_name',true);
		$file = get_post_meta($product_id,'product_file',true);
		if ( !$file_name ){
			$file_name = $file;
		}
		
		if (file_exists($file)) {
			
			ob_start();
			$product_info['downloads'] += 1;
			update_post_meta($order_id,'product_info',$product_info);
			if ( function_exists('apache_get_modules') 
      && in_array('mod_xsendfile', apache_get_modules()) ){
				header ('X-Sendfile: ' . $file);
			    header('Content-Description: File Transfer');
			    header('Content-Type: application/octet-stream');
			    header('Content-Disposition: attachment; filename='.basename($file_name));
			    header('Content-Transfer-Encoding: binary');
			    header('Expires: 0');
			    header('Cache-Control: must-revalidate');
			    header('Pragma: public');
			    header('Content-Length: ' . filesize($file));
			}else{
				set_time_limit(0);
				header('Content-Description: File Transfer');
			    header('Content-Type: application/octet-stream');
			    header('Content-Disposition: attachment; filename='.basename($file_name));
			    header('Content-Transfer-Encoding: binary');
			    header('Expires: 0');
			    header('Cache-Control: must-revalidate');
			    header('Pragma: public');
			    header('Content-Length: ' . filesize($file));
			    ob_clean();
			    flush();
			    //readfile($file);
			    $this->readfile_chunked($file);
			}
		    exit;
		}
		isell_error_redirect(ISELL_NO_FILE,$error_page);
		die();
	}
	function process_paypal_ipn(){
		include(iSell_Path.'inc/payments/ipnlistener.php');
		include(iSell_Path.'inc/payments/process_paypal_ipn.php');
	}
	function do_product_redirect(){
		do_action('isell_product_redirect');

		if ( isset($_REQUEST['iproduct']) ){
			$product_id = (int)$_REQUEST['iproduct'];
			if ( is_int($product_id) ){
				if ( get_post_status($product_id) == 'publish' ){
					$options = isell_get_options();
					$platform = $options['paypal']['platform'];
					$product_name = get_post_meta($product_id,'product_name',true);
					$price = get_post_meta($product_id,'product_price',true);
					$notify_url = admin_url('admin-ajax.php').'?action=isell_paypal_ipn';
					$notify_url = apply_filters('isell_notify_url', $notify_url);
					$amount = number_format($price, 2);
					
					if ( $platform == 'sandbox' )
						$url = 'https://www.sandbox.paypal.com/cgi-bin/webscr?';
					else
						$url = 'https://www.paypal.com/cgi-bin/webscr?';

					$url = apply_filters('isell_payment_gateway_url', $url);

					$parameters = array(
							'currency_code' => $options['store']['currency'],
							'cmd' => '_xclick',
							'business' => $options['paypal']['email'],
							'receiver_email' => $options['paypal']['email'],
							'item_name' => $product_name,
							'amount' => $amount,
						    'item_number' => (int)$product_id,
						    'notify_url' => $notify_url,
						    'return' => $options['store']['thanks_page'],
						    'rm' => 2

						);
					$parameters = apply_filters('isell_payment_gateway_parameters', $parameters);

					$parameters = http_build_query($parameters);
					$redirect_url = $url . $parameters;

					$redirect_url = apply_filters('isell_payment_gateway_redirect_url', $redirect_url, $parameters);

					ob_start();
					
					header("Location: $redirect_url");
					
					ob_clean();
					flush();
					exit;
				}
			}
		}
		
		return;
		

	}
	function get_email($name){
		include(iSell_Path.'views/notification_emails.php');
		$emails = apply_filters('isell_emails', $emails, $name);
		return $emails[$name];
	}
	function get_error($error_code){
		include(iSell_Path.'views/isell_errors.php');
		$errors = apply_filters('isell_errors', $errors, $error_code);
		return $errors[$error_code];
	}
	function send_notification_emails($data,$order_id){
		$this->send_customer_product_download_email($data,$order_id);
		$this->send_admin_new_order_email($data,$order_id);
	}
	function send_customer_product_download_email($data,$order_id){
		$email = $this->get_email('order_customer_product_download');
		
		$subject = $email['subject'];
		$message = $email['message'];
		
		$subject = apply_filters('isell_customer_product_download_email_subject', $subject, $subject);
		$message = apply_filters('isell_customer_product_download_email_message', $message, $message);
		
		do_action('before_isell_send_product_download_email');

		$payer_email = $data['payer_email'];
		$product_id = (int)$data['item_number'];
		$txn_id = $data['txn_id'];
		$product_download_url = isell_generate_product_download_url($order_id,$product_id,$txn_id);
		$customer_name = wp_strip_all_tags($data['address_name']);
		$product_name = get_post_meta($product_id,'product_name',true);
		
		$subject_replacements = array(
			'{product_name}' => $product_name,
		    '{txn_id}' => $txn_id
		);
		$message_replacements = array(
			'{product_download_url}' => $product_download_url,
		    '{customer_name}' => $customer_name
		);

		$subject = str_ireplace(array_keys($subject_replacements), $subject_replacements, $subject);
		$message = str_ireplace(array_keys($message_replacements), $message_replacements, $message);

		wp_mail($payer_email,$subject,$message);
	}
	function send_admin_new_order_email($data,$order_id){
		$email = $this->get_email('admin_new_order');
		
		$subject = $email['subject'];
		$message = $email['message'];
		
		$subject = apply_filters('isell_admin_new_order_email_subject', $subject, $subject);
		$message = apply_filters('isell_admin_new_order_email_subject', $message, $message);

		do_action('before_isell_send_admin_new_order_email');

		$payer_email = $data['payer_email'];
		$product_id = (int)$data['item_number'];
		$txn_id = $data['txn_id'];
		$product_download_url = isell_generate_product_download_url($order_id,$product_id,$txn_id);
		$edit_order_link = admin_url( sprintf('post.php?post=%s&action=edit',$order_id));
		$customer_name = wp_strip_all_tags($data['address_name']);
		$product_name = get_post_meta($product_id,'product_name',true);

		$subject_replacements = array(
			'{product_name}' => $product_name,
		    '{txn_id}' => $txn_id,
		    '{customer_name}' => $customer_name
		);
		$message_replacements = array(
			'{product_download_url}' => $product_download_url,
		    '{customer_name}' => $customer_name,
		    '{edit_order_link}' => $edit_order_link,
		    '{product_name}' => $product_name
		);

		$subject = str_ireplace(array_keys($subject_replacements), $subject_replacements, $subject);
		$message = str_ireplace(array_keys($message_replacements), $message_replacements, $message);
		
		wp_mail(get_option('admin_email'),$subject,$message);
	}
	function isell_errors($atts, $content=null){
		 extract( shortcode_atts( array(
      				'show' => true,
      	 ), $atts ) );
      	 if ( !$show ) return;
      	 $error_code = $_REQUEST['isell_error'];
      	 ob_start();
      	 echo apply_filters('isell_error',$this->get_error($error_code),$error_code);
      	 $return_content = ob_get_contents();
      	 ob_end_clean();
      	 return $return_content;
	}
	function readfile_chunked($filename, $retbytes = TRUE){
	    $buffer = '';
	    $cnt =0;
	    
	    $handle = fopen($filename, 'rb');
	    if ($handle === false) {
	      return false;
	    }
	    while (!feof($handle)) {
	      $buffer = fread($handle, iSell_CHUNK_SIZE);
	      echo $buffer;
	      ob_flush();
	      flush();
	      if ($retbytes) {
	        $cnt += strlen($buffer);
	      }
	    }
	    $status = fclose($handle);
	    if ($retbytes && $status) {
	      return $cnt; // return num. bytes delivered like readfile() does.
	    }
	    return $status;
  }
	
}

new WordPress_iSell;
?>