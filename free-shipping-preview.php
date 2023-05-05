<?php
/**
 * Plugin Name: Alerta de Frete Grátis
 * Plugin URI: https://samuel-gama.vercel.app/
 * Description: O plugin "Frete Grátis Alerta" é a solução perfeita para ajudar seus clientes a aproveitarem ao máximo a possibilidade de receberem frete grátis em suas compras. Com uma mensagem simples e elegante, o plugin exibe um alerta que informa o usuário sobre quanto falta para atingir o valor mínimo para frete grátis. Assim, seus clientes podem continuar comprando com tranquilidade, sem se preocupar em gastar demais ou perder a oportunidade de economizar. O "Frete Grátis Alerta" é fácil de instalar e configurar, garantindo uma experiência de compra mais agradável e satisfatória para seus clientes.
 * Version: 1.0.0
 * Author: Samuel Gama
 * Text Domain: free-shipping-alert
 * WO requires up to: 6.2
 * WC tested up to: 7.6.0
 *
 * @package FreeShippingAlert
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'WC_FSA_FILE' ) ) {
	define( 'WC_FSA_FILE', __FILE__ );
}



if ( file_exists( WP_PLUGIN_DIR . '/woocommerce/woocommerce.php' ) ) {
    if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
        include_once( WP_PLUGIN_DIR . '/woocommerce/woocommerce.php' );
    }
}

add_action( 'plugins_loaded', 'fsa_load_woocommerce' );

function fsa_load_woocommerce() {
    if ( ! class_exists( 'WC_FSA' ) ) {
        include_once dirname( WC_FSA_FILE ) . '/includes/class-fsa.php';
    }
}


