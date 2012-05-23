<?php

function isell_change_product_post_messages($messages){
	 	global $post;
		$messages['isell-product'] = array(
					0 => '', // Unused. Messages start at index 1.
					1 =>  __('Product updated.'),
					2 => __('Custom field updated.'),
					3 => __('Custom field deleted.'),
					4 => __('Product updated.'),
					/* translators: %s: date and time of the revision */
					5 => isset($_GET['revision']) ? sprintf( __('Product restored to revision from %s'), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
					6 => __('Product created.'),
					7 => __('Product saved.'),
					8 => '',
					9 => sprintf( __('Product scheduled for: <strong>%1$s</strong>.'),
					  // translators: Publish box date format, see http://php.net/date
					  date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ) ),
					10 => __('Product draft updated.')
				);

		return $messages;
}
function isell_change_order_post_messages($messages){
		global $post;
		$messages['isell-order'] = array(
					0 => '', // Unused. Messages start at index 1.
					1 =>  __('Order updated.'),
					2 => __('Custom field updated.'),
					3 => __('Custom field deleted.'),
					4 => __('Order updated.'),
					/* translators: %s: date and time of the revision */
					5 => isset($_GET['revision']) ? sprintf( __('Order restored to revision from %s'), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
					6 => __('Order created.'),
					7 => __('Order saved.'),
					8 => '',
					9 => sprintf( __('Order scheduled for: <strong>%1$s</strong>.'),
					  // translators: Publish box date format, see http://php.net/date
					  date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ) ),
					10 => __('Order draft updated.')
				);

		return $messages;
}
function isell_remove_post_row_actions( $actions )
{
    if( get_post_type() === 'isell-order' ){
        unset( $actions['view'] );
        unset( $actions['inline hide-if-no-js'] );
        unset( $actions['pgcache_purge'] );
    }
    if( get_post_type() === 'isell-product' ){
        unset( $actions['view'] );
        unset( $actions['inline hide-if-no-js'] );
        unset( $actions['pgcache_purge'] );
    }
    return $actions;
}

function isell_generate_product_url($product_id){
		$product_url = sprintf('%s?iproduct=%s',site_url(),$product_id);
		return apply_filters ( 'isell_product_url' , $product_url, $product_id );
}
function isell_generate_product_download_url($order_id,$product_id,$txn_id){
	$product_download_url = sprintf("%s?action=%s&product=%s&order=%s&trans=%s",admin_url( 'admin-ajax.php'),'isell_download_file',$product_id,$order_id,$txn_id);
	return apply_filters ( 'isell_product_download_url' ,$product_download_url, $order_id, $product_id, $txn_id );
}

function isell_shortcode_list_product_files(){
		
}
function isell_get_options(){
	return get_option('isell_options');
}
function isell_settings_page(){
	add_menu_page(__('iSell Settings','isell'), 'iSell', 'manage_options', __FILE__, 'isell_settings_page_view');
}
if ( !function_exists('isell_save_settings') ){
function isell_save_settings($options){
	$options['paypal'] = array(
			'email' => $_POST['paypal_email'],
			'platform' => $_POST['paypal_platform']
		);
	$options['store'] = array(
			'currency' => $_POST['currency'],
			'error_page' => $_POST['error_page']
		);
	$options['file_management']['max_downloads'] = (int)$_POST['max_downloads'];

	update_option('isell_options',$options);
	return $options;
}
}
if ( !function_exists('isell_settings_page_view') ){
function isell_settings_page_view(){
	$options = get_option('isell_options');
	$currencies = isell_currencies();
	if ( isset($_POST['submit']) && isset($_POST['isell_options_page']) ){
		if ( !wp_verify_nonce($_POST['nonce'],'isell_options_page') ) return;
		$options = isell_save_settings($options);
	}
	
	include_once(iSell_Path.'/views/settings_page.php');
}
}
function isell_currencies(){
	return array('USD' => array('title' => 'U.S. Dollar', 'code' => 'USD', 'symbol_left' => '$', 'symbol_right' => '', 'decimal_point' => '.', 'thousands_point' => ',', 'decimal_places' => '2'),
                           'EUR' => array('title' => 'Euro', 'code' => 'EUR', 'symbol_left' => '', 'symbol_right' => '€', 'decimal_point' => '.', 'thousands_point' => ',', 'decimal_places' => '2'),
                           'JPY' => array('title' => 'Japanese Yen', 'code' => 'JPY', 'symbol_left' => '¥', 'symbol_right' => '', 'decimal_point' => '.', 'thousands_point' => ',', 'decimal_places' => '2'),
                           'GBP' => array('title' => 'Pounds Sterling', 'code' => 'GBP', 'symbol_left' => '£', 'symbol_right' => '', 'decimal_point' => '.', 'thousands_point' => ',', 'decimal_places' => '2'),
                           'CHF' => array('title' => 'Swiss Franc', 'code' => 'CHF', 'symbol_left' => '', 'symbol_right' => 'CHF', 'decimal_point' => ',', 'thousands_point' => '.', 'decimal_places' => '2'),
                           'AUS' => array('title' => 'Australian Dollar', 'code' => 'AUS', 'symbol_left' => '$', 'symbol_right' => '', 'decimal_point' => '.', 'thousands_point' => ',', 'decimal_places' => '2'),
                           'CAD' => array('title' => 'Canadian Dollar', 'code' => 'CAD', 'symbol_left' => '$', 'symbol_right' => '', 'decimal_point' => '.', 'thousands_point' => ',', 'decimal_places' => '2'),
                           'SEK' => array('title' => 'Swedish Krona', 'code' => 'SEK', 'symbol_left' => '', 'symbol_right' => 'kr', 'decimal_point' => ',', 'thousands_point' => '.', 'decimal_places' => '2'),
                           'HKD' => array('title' => 'Hong Kong Dollar', 'code' => 'HKD', 'symbol_left' => '$', 'symbol_right' => '', 'decimal_point' => '.', 'thousands_point' => ',', 'decimal_places' => '2'),
                           'NOK' => array('title' => 'Norwegian Krone', 'code' => 'NOK', 'symbol_left' => 'kr', 'symbol_right' => '', 'decimal_point' => ',', 'thousands_point' => '.', 'decimal_places' => '2'),
                           'NZD' => array('title' => 'New Zealand Dollar', 'code' => 'NZD', 'symbol_left' => '$', 'symbol_right' => '', 'decimal_point' => '.', 'thousands_point' => ',', 'decimal_places' => '2'),
                           'MXN' => array('title' => 'Mexican Peso', 'code' => 'MXN', 'symbol_left' => '$', 'symbol_right' => '', 'decimal_point' => '.', 'thousands_point' => ',', 'decimal_places' => '2'),
                           'SGD' => array('title' => 'Singapore Dollar', 'code' => 'SGD', 'symbol_left' => '$', 'symbol_right' => '', 'decimal_point' => '.', 'thousands_point' => ',', 'decimal_places' => '2'),
                           'BRL' => array('title' => 'Brazilian Real', 'code' => 'BRL', 'symbol_left' => 'R$', 'symbol_right' => '', 'decimal_point' => ',', 'thousands_point' => '.', 'decimal_places' => '2'),
                           'CNY' => array('title' => 'Chinese RMB', 'code' => 'CNY', 'symbol_left' => '￥', 'symbol_right' => '', 'decimal_point' => '.', 'thousands_point' => ',', 'decimal_places' => '2'),
                           'CZK' => array('title' => 'Czech Koruna', 'code' => 'CZK', 'symbol_left' => '', 'symbol_right' => 'Kč', 'decimal_point' => ',', 'thousands_point' => '.', 'decimal_places' => '2'),
                           'DKK' => array('title' => 'Danish Krone', 'code' => 'DKK', 'symbol_left' => '', 'symbol_right' => 'kr', 'decimal_point' => ',', 'thousands_point' => '.', 'decimal_places' => '2'),
                           'HUF' => array('title' => 'Hungarian Forint', 'code' => 'HUF', 'symbol_left' => '', 'symbol_right' => 'Ft', 'decimal_point' => '.', 'thousands_point' => ',', 'decimal_places' => '2'),
                           'ILS' => array('title' => 'Israeli New Shekel', 'code' => 'ILS', 'symbol_left' => '₪', 'symbol_right' => '', 'decimal_point' => '.', 'thousands_point' => ',', 'decimal_places' => '2'),
                           'INR' => array('title' => 'Indian Rupee', 'code' => 'INR', 'symbol_left' => 'Rs.', 'symbol_right' => '', 'decimal_point' => '.', 'thousands_point' => ',', 'decimal_places' => '2'),
                           'MYR' => array('title' => 'Malaysian Ringgit', 'code' => 'MYR', 'symbol_left' => 'RM', 'symbol_right' => '', 'decimal_point' => '.', 'thousands_point' => ',', 'decimal_places' => '2'),
                           'PHP' => array('title' => 'Philippine Peso', 'code' => 'PHP', 'symbol_left' => 'Php', 'symbol_right' => '', 'decimal_point' => '.', 'thousands_point' => ',', 'decimal_places' => '2'),
                           'PLN' => array('title' => 'Polish Zloty', 'code' => 'PLN', 'symbol_left' => '', 'symbol_right' => 'zł', 'decimal_point' => ',', 'thousands_point' => '.', 'decimal_places' => '2'),
                           'THB' => array('title' => 'Thai Baht', 'code' => 'THB', 'symbol_left' => '', 'symbol_right' => '฿', 'decimal_point' => '.', 'thousands_point' => ',', 'decimal_places' => '2'),
                           'TWD' => array('title' => 'Taiwan New Dollar', 'code' => 'TWD', 'symbol_left' => 'NT$', 'symbol_right' => '', 'decimal_point' => '.', 'thousands_point' => ',', 'decimal_places' => '2'));
}

add_action('admin_notices', 'isell_admin_notice');
function isell_admin_notice() {
    global $current_user ;
    $user_id = $current_user->ID;
        /* Check that the user hasn't already clicked to ignore the message */
    if ( !current_user_can('manage_options') ) return;
    if ( ! get_user_meta($user_id, 'isell_ignore_notice') ) {
        echo '<div class="updated"><p>';
        printf(__('Thank you for using the iSell Plugin, Please go to the <a href="%1$s">iSell settings</a> page to setup the plugin.'), admin_url().'?page='. iSell_Dir_Name .'/inc/functions.php&isell_ignore_notice=0');
        echo "</p></div>";
    }
}
add_action('admin_init', 'isell_ignore_notice');
function isell_ignore_notice() {
    global $current_user;
        $user_id = $current_user->ID;
        /* If user clicks to ignore the notice, add that to their user meta */
        if ( isset($_GET['isell_ignore_notice']) && '0' == $_GET['isell_ignore_notice'] ) {
             add_user_meta($user_id, 'isell_ignore_notice', 'true', true);
    }
}

//language
function isell_load_plugin_textdomain() {
  load_plugin_textdomain( 'isell', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' ); 
}
add_action('plugins_loaded', 'isell_load_plugin_textdomain');

?>