<?php
$listener = new IpnListener();

$options = isell_get_options();
$platform = $options['paypal']['platform'];
if ( $platform == 'sandbox' )
	$listener->use_sandbox = true;

//uncomment the line below to disable ssl, paypal ipn might not work
//$listener->use_ssl = false; 

//set it to true to use fsockopen
$listener->use_curl = false;
if ( $options['advanced']['use_fsockopen_or_curl'] == 'curl' )
	$listener->use_curl = true;

try {
	//verify that ipn is valid
    $listener->requirePostMethod();
    $verified = $listener->processIpn();
} catch (Exception $e) {
   //do nothing
    exit(0);
}
if ( $verified ){
	if ( $_POST['payment_status'] == 'Completed' || $_POST['payment_status'] == 'Pending' ){
		 if ( $_POST['receiver_email'] == $options['paypal']['email'] ){
		 	$product_id = $_POST['item_number'];
		 	$price = get_post_meta($product_id,'product_price',true);
		 	if ( !$price ) return;
		 	if ( (float)$_POST['mc_gross'] >= (float)$price ){
		 		if ( $_POST['mc_currency'] == $options['store']['currency'] ){
		 			global $wpdb;
		 			$txn_id = $wpdb->escape($_POST['txn_id']);
					$txn_id_query = $wpdb->prepare( "SELECT * 
									 FROM  $wpdb->postmeta 
									 WHERE  meta_value =  %s AND meta_key = %s",$txn_id,'txn_id');
					if ( $wpdb->query($txn_id_query) >= 1 ){ 
						
						if ( $_POST['payment_status'] == 'Completed' ) {

							$get_txn_id_query = $wpdb->prepare( "SELECT post_id 
									FROM  $wpdb->postmeta 
									WHERE  meta_value =  %s AND meta_key = %s",$txn_id,'txn_id');
							$order_id = $wpdb->get_var($get_txn_id_query);
							$product_id = $wpdb->escape($_POST['item_number']);

							if ( $order_id ) {

								update_post_meta($order_id,'ipn_text_report',$listener->getTextReport());
								$payment_info = get_post_meta($order_id,'payment_info',true);
								$payment_info['status'] = 'Completed';
								update_post_meta($order_id,'payment_info',$payment_info);
								$product_name = get_the_title($product_id);
								$order_title = sprintf("Order: %s | ID: %s | Status: %s",$product_name,$txn_id,$payment_info['status']);
								$order_post = array(
									'ID' => $order_id,
									'post_title' => wp_strip_all_tags($order_title)
								);
								wp_update_post($order_post);

								do_action('isell_payment_completed',$_POST, $order_id);

							}

						}

						exit;
					}

		 			$payer_email = $_POST['payer_email'];
		 			$payment_status = esc_html($_POST['payment_status']);
		 			$first_name = esc_html($_POST['first_name']);
		 			$last_name = esc_html($_POST['last_name']);
		 			$country_code = esc_html($_POST['address_country_code']);
		 			$zip_code = esc_html($_POST['address_zip']);
		 			$state = esc_html($_POST['address_state']);
		 			$city = esc_html($_POST['address_city']);
		 			$street = esc_html($_POST['address_street']);
		 			$amount_paid = esc_html($_POST['mc_gross']);
		 			$product_name = get_post_meta($product_id,'product_name',true);
		 			$title = sprintf("Order: %s | ID: %s | Status: %s",$product_name,$txn_id,$payment_status);
		 			$buyer_info = array(
						'first_name' => $first_name,
						'last_name' => $last_name,
						'email' => $payer_email,
						'phone' => '',
						'country' => $country_code,
						'state' => $state,
						'city' => $city,
						'zip' => $zip_code
					);
					$payment_info = array(
						'status' => $payment_status,
						'amount_paid' => $amount_paid,
						'txn_id' => $txn_id
					);
					$product_info = array(
						'id' => $product_id,
						'name' => $product_name,
						'download_url' => '',
						'downloads' => 0,
						'link_status' => 'valid'
					);
		 			$order_post = array(
					     'post_title' => wp_strip_all_tags($title),
					     'post_status' => 'publish',
					     'post_author' => 1,
					     'post_type' => 'isell-order'
					  );
		 			$order_id = wp_insert_post($order_post);
		 			if ( !is_wp_error($order_id) ){
			 			update_post_meta($order_id,'txn_id',$txn_id);
			 			update_post_meta($order_id,'ipn_text_report',$listener->getTextReport());
			 			update_post_meta($order_id,'buyer_info',$buyer_info);
						update_post_meta($order_id,'payment_info',$payment_info);
						update_post_meta($order_id,'product_info',$product_info);
					}else{
						wp_mail(get_option('admin_email'),'iSell: Error in creating an order',$listener->getTextReport());
					}
					if ( $_POST['payment_status'] == 'Completed' )
						do_action('isell_payment_completed',$_POST, $order_id);
					else
						do_action('isell_payment_pending',$_POST, $order_id);

		 		}
		 	}
		 }
	}else {
		if ( $_POST['payment_status'] == 'Refunded' ) {
			if ( $_POST['receiver_email'] == $options['paypal']['email'] ){
		 		global $wpdb;
		 		$txn_id = $wpdb->escape($_POST['txn_id']);
				$txn_id_query = $wpdb->prepare( "SELECT post_id 
									FROM  $wpdb->postmeta 
									WHERE  meta_value =  %s AND meta_key = %s",$txn_id,'txn_id');
				$order_id = $wpdb->get_var($txn_id_query);
				$product_id = $wpdb->escape($_POST['item_number']);

				if ( $order_id  ) {
					update_post_meta($order_id,'ipn_text_report',$listener->getTextReport());
					$payment_info = get_post_meta($order_id,'payment_info',true);
					$payment_info['status'] = 'Refunded';
					update_post_meta($order_id,'payment_info',$payment_info);
					$product_name = get_the_title($product_id);
					$order_title = sprintf("Order: %s | ID: %s | Status: %s",$product_name,$txn_id,$payment_info['status']);
					$order_post = array(
							'ID' => $order_id,
							'post_title' => wp_strip_all_tags($order_title)
						);
					wp_update_post($order_post);

					do_action('isell_payment_refunded',$_POST, $order_id);

				}
		 	}
		}//end payment status refunded 
	} 
}
?>