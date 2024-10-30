<?php 
function kwmmoaw_minimum_order_amount_for_woocommerce_intall_hook(){

	if ( version_compare( PHP_VERSION, '5.2.1', '<' )

	  	or version_compare( get_bloginfo( 'version' ), '3.3', '<' ) ) {

		deactivate_plugins( basename( __FILE__ ) );

	}
} 



?>