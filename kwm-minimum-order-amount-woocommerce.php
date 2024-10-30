<?php
/*
Plugin Name:  KWMLab Minimum Order Amount for Woocommerce
Plugin URI:   https://shop.kwmlab.com.br/produto/kwm-minimum-order-amount-for-woocommerce/
Description:  Force a minimum amount of purchase of products or value to be able to finalize the purchase in your e-commerce
Version:      1.0
Author:       KWMLAB
Author URI:   https://kwmlab.com.br
License:      GPL2
License URI:  https://www.gnu.org/licenses/gpl-2.0.html
Text Domain:  kwmmoaw
Domain Path:  /languages


KWMLab Minimum Order Amount for Woocommerce is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
any later version.
 
KWMLab Minimum Order Amount for Woocommerce is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
 
You should have received a copy of the GNU General Public License
along with KWMLab Minimum Order Amount for Woocommerce. If not, see https://www.gnu.org/licenses/gpl-2.0.html.
*/


//Incluindo o arquivo de ativação

//Registra a função para ocorrer na ativação do plugin

include(plugin_dir_path(__FILE__).'install.php');

register_activation_hook( __FILE__ , 'kwmmoaw_minimum_order_amount_for_woocommerce_intall_hook');


load_textdomain('kwmmoaw', plugin_dir_path( __FILE__) . 'languages/' . get_locale() . '.mo');

//função quer verifica se o woocommerce está instalado
function kwmmoaw_is_woocommerce_active() {
	$active_plugins = ( is_multisite() ) ?
		array_keys( get_site_option( 'active_sitewide_plugins', array() ) ) :
		apply_filters( 'active_plugins', get_option( 'active_plugins', array() ) );
	foreach ( $active_plugins as $active_plugin ) {
		$active_plugin = explode( '/', $active_plugin );
		if ( isset( $active_plugin[1] ) && 'woocommerce.php' === $active_plugin[1] ) {
			return true;
		}
	}
	return false;
}

/*
	Código relacionado as opções do plugin e exibição no backend e frontend
*/
if ( kwmmoaw_is_woocommerce_active() ) {


	// display custom admin notice
	if(isset($_GET['page']) && $_GET['page'] == 'wc-settings'){
		function kwmmoaw_add_notice_buy_pro() { 
			
			if(isset($_GET['hide-kwmmoaw-notice']) && $_GET['hide-kwmmoaw-notice'] == 'hidden'){
				update_option( 'hide_kwmmoaw_notice', 'yes' );
			}
	    	
	    	$enable_notice = get_option('hide_kwmmoaw_notice');
			
	    	if($enable_notice != 'yes'){
				?>
					<div class="notice woocommerce-message">
						<a class="woocommerce-message-close notice-dismiss" style="top:0;" href="<?php echo admin_url(); ?>admin.php?page=wc-settings&hide-kwmmoaw-notice=hidden">Dispensar</a>
						<p><?php echo __('Buy the pro version of the KWMLab Minimum Order Amount for Woocommerce, it contains many more customization options and cost R$ 5,00', 'kwmmoaw'); ?> <a href="https://shop.kwmlab.com.br/produto/kwm-minimum-order-amount-for-woocommerce/" target="_blank"><?php echo __('Purchase', 'kwmmoaw'); ?></a></p>
					</div>
					
				<?php
	    	} 
	    }
		add_action('admin_notices', 'kwmmoaw_add_notice_buy_pro');
	}


	/* =====================Opções no admin do woocommerce===========================*/
	
	add_filter( 'woocommerce_general_settings', 'kwmmoaw_add_a_setting' );
	function kwmmoaw_add_a_setting( $settings ) {

		$settings[] = array( 
			'name' => __( 'Minimum Order Amount', 'kwmmoaw' ), 
			'type' => 'title', 
			'desc' => '', 
			'id' => 'woocommerce_kwmmoaw_settings' );

		$settings[] = array(
			'title'    	=> __( 'Enable', 'kwmmoaw' ),
			'desc'     	=> '',
			'id'       	=> 'kwm_minimum_order_amount_enable',
			'desc'  	=> __( 'Check to enable lock', 'kwmmoaw' ),
			'type'     	=> 'checkbox',
			'default'	=> '',
			'desc_tip'	=> false,
		);

		$settings[] = array(
			'title'    	=> __( 'Minimum value', 'kwmmoaw' ),
			'desc'     	=> '',
			'id'       	=> 'kwm_minimum_order_amount_enable_minimum_pricing',
			'desc'  	=> '<br />' .__( 'Use "." To separate decimal values example "50.49"', 'kwmmoaw' ),
			'type'     	=> 'text',
			'default'	=> '',
			'desc_tip'	=> false,
			'placeholder' => __( '50.49', 'kwmmoaw' ),
		);

		$settings[] = array(
			'title'    	=> __( 'Message notice', 'kwmmoaw' ),
			'desc'     	=> '',
			'id'       	=> 'kwm_minimum_order_amount_message_notice',
			'desc'  	=> __( 'Use this shortcode to return the minimum purchase value in the message [minimum].', 'kwmmoaw') . '<br />' .__( 'Use this shortcode to return the current value of the purchase in the message [total_in_cart].', 'kwmmoaw' ),
			'type'     	=> 'textarea',
			'default'	=> '',
			'desc_tip'	=> false,
			'placeholder' => __( 'You must have an order with a minimum of [minimum] to place your order, your current order total is [total_in_cart].', 'kwmmoaw' ),
		);


		$settings[] = array( 'type' => 'sectionend', 'id' => 'woocommerce_kwm_minimum_order_amount_settings');

		return $settings;

	}


	
	/* ===================== exibir no frontend do woocommerce ===========================*/

	add_action( 'woocommerce_check_cart_items' , 'kwmmoaw_minimum_order_amount' );
	function kwmmoaw_minimum_order_amount() {

    	// Set this variable to specify a minimum order value
	    $enable_settings = get_option('kwm_minimum_order_amount_enable');


		if($enable_settings == 'yes'){

			$minimum = get_option('kwm_minimum_order_amount_enable_minimum_pricing');

			if($minimum){
				$message_notice = strip_tags(get_option( 'kwm_minimum_order_amount_message_notice' ));

				global $woocommerce;
			    if( version_compare( $woocommerce->version, '2.1', ">=" ) ) {

					if($message_notice){

						$message_notice = strtr($message_notice, array(
							'[minimum]' =>  strip_tags(wc_price( $minimum )),
							'[total_in_cart]' => strip_tags(wc_price( WC()->cart->total ))
						));

					}else{
						$message_notice = sprintf( __( 'You must have an order with a minimum of %s to place your order, your current order total is %s.', 'kwmmoaw') , 
		                    strip_tags(wc_price( $minimum )), 
		                    strip_tags(wc_price( WC()->cart->total ))
		                );
					}

				    if ( WC()->cart->total < $minimum ) {

				        if( is_cart() ) {

				            wc_print_notice($message_notice , 'error');

				        } else if( is_checkout() ){

					    	wc_add_notice($message_notice, 'error');
					    	
					    }
				    }

				//no caso de versão mais antigas do woocommerce 2.0.9 para baixo
				}else{
					if ( $woocommerce->cart->get_cart_total() < $minimum ) {

						if($message_notice){

							$message_notice = strtr($message_notice, array(
								'[minimum]' =>  wc_price( $minimum ),
								'[total_in_cart]' => wc_price( $woocommerce->cart->get_cart_total() )
							));

						}else{
							$message_notice = sprintf(  __( 'You must have an order with a minimum of %s to place your order.', 'kwmmoaw') , $minimum );
						}

						$woocommerce->add_error( $message_notice );
					}
				}
			}
			
		}

	}

}