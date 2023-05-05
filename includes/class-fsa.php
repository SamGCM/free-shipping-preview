
<?php
/**
 *
 * @package FreeShippingAlert
 */
class WC_FSA{
    public $is_visible = false;
    public $zero = 0;

    public function __construct() {
        $this->init();
    }

    function init(){

        add_action( 'wp_enqueue_scripts', array( $this, 'seu_plugin_enqueue_styles' ) );
        add_filter( 'woocommerce_cart_actions', array( $this, 'calculate_free_shipping' ), 20);
        add_filter( 'woocommerce_checkout_before_order_review_heading', array( $this, 'calculate_free_shipping' ), 20);
        add_filter( 'woocommerce_cart_item_removed', array( $this, 'calculate_free_shipping' ), 20);
        add_filter( 'woocommerce_after_cart_item_quantity_update', array( $this, 'calculate_free_shipping' ), 20);
        add_filter( 'woocommerce_after_checkout_billing_form', array( $this, 'calculate_free_shipping' ), 20);
        
        if(!($this->is_actual_page_contain($this->get_slug_cart_page())))
        {
            add_filter( 'wp_footer', array( $this, 'calculate_free_shipping' ));
        }
    }

    function calculate_free_shipping() {
        WC()->cart->calculate_totals();

        $cart_total = WC()->cart->subtotal;
        $cart_not_empty = WC()->cart->get_cart_contents_count() > $this->zero;
        $not_is_checkout_page = !($this->is_actual_page_contain($this->get_slug_checkout_page()));

        if( $cart_not_empty && $not_is_checkout_page)
        {
            $this->get_remaining_for_free_shipping($this->get_min_amount(), $cart_total);  
        }
        else {
            echo '<div></div>';
        }
    }

    function get_home_page_slug() {
        $homepage_slug = basename(parse_url(get_home_url())['path']);
        return $homepage_slug;
    }

    function get_remaining_for_free_shipping($minimum_free_shipping_value, $cart_total)
    {
        $minimum_free_shipping = $minimum_free_shipping_value;
        $remaining_for_free_shipping = $minimum_free_shipping - $cart_total;

        if($this->is_visible)
        {

            if ( $remaining_for_free_shipping <= $this->zero ) {
                echo '
                <div class="fsp_notification-top-bar fsp_success">
                    <p>Frete grátis</p>
                </div>
                ';
            } else {
                echo '<div class="fsp_notification-top-bar fsp_alert">
                    <p> Falta '. wc_price( $remaining_for_free_shipping ) .  ' para frete grátis</p>
                </div>';
            }
        }
        
        else
        {
            echo '
            <div class="fsp_notification-top-bar fsp_alert">
                <p> 
                    Selecione um endereço válido no
                    <a class="fsp_link" href="'. wc_get_cart_url() .'#fsa_woocommerce-shipping-totals"> 
                        carrinho 
                    </a>para saber se tem frete grátis disponível. 
                </p>
            </div>
            ';
        }
        
    }

    /*
    *@int
    */
    function get_min_amount() {
        $customer = WC()->customer;
        $min_amounts = [];
    
        $zipcode = $customer->get_shipping_postcode();
        $state = $customer->get_shipping_state();
        $country = $customer->get_shipping_country();
        $state_selected_shipping = '';
        $country_selected_shipping = '';
    
        $shipping_zones = $this->get_free_shipping_methods();

        if (isset(WC()->countries->states[$country]) && isset(WC()->countries->states[$country][$state])) {
            $state_selected_shipping = WC()->countries->states[$country][$state];
            $country_selected_shipping = WC()->countries->countries[ $country ];
        }

        if($state_selected_shipping != '' && $country_selected_shipping != '')
        {
            $this->is_visible = true;
        }
        
        else
        {
            $this->is_visible = false;
        }
    
        foreach ( $shipping_zones as $zone ) {
            $formatted_zone_location = $zone['formatted_zone_location'];
    
            // Verifica se a zona não está vazia
            if ( ! empty( $zone ) ) {
                // Verifica se o CEP está dentro do intervalo
                if ( false !== $this->get_interval_cep( $formatted_zone_location, $zipcode ) ) {
                    $min_amounts[] = $zone['min_amount'];
                }
                // Verifica se o estado selecionado corresponde ao estado da zona
                elseif ( $state_selected_shipping == $formatted_zone_location ) {
                    $min_amounts[] = $zone['min_amount'];
                }
                // Verifica se o país selecionado corresponde ao país da zona
                elseif ( false !== strpos( $formatted_zone_location, $country_selected_shipping ) ) {
                    $min_amounts[] = $zone['min_amount'];
                }
            }
        }
    
        // Seleciona o valor mínimo
        $min_amount = ! empty( $min_amounts ) ? min( $min_amounts ) : 0;
    
        return $min_amount;
    }

    function get_free_shipping_methods()
    {
        $shipping_zones = WC_Shipping_Zones::get_zones();

        $free_shipping_methods = array();

        foreach ( $shipping_zones as $shipping_zone ) {
            $shipping_methods = $shipping_zone['shipping_methods'];
            foreach ( $shipping_methods as $shipping_method ) {
                if ( $shipping_method instanceof WC_Shipping_Free_Shipping ) {
                    $free_shipping_methods[] = array(
                        'formatted_zone_location' => $shipping_zone['formatted_zone_location'],
                        'zone_id' => $shipping_zone['zone_id'],
                        'zone_name' => $shipping_zone['zone_name'],
                        'method_id' => $shipping_method->get_instance_id(),
                        'method_title' => $shipping_method->get_title(),
                        'min_amount' => $shipping_method->get_option('min_amount'),
                    );
                }
            }
        }

        return $free_shipping_methods;
    }

    function get_interval_cep($ceps_string, $zipcode)
    {
        $zipcode_formatted = $this->to_int($this->remove_mask_zipcode($zipcode));
        $ceps_array = explode('...', $ceps_string);
        $is_shipping_free_zipcode = false;

        if (count($ceps_array) < 2) {
            return false;
        }
        
        $start_zipcode_formatted = $this->to_int($this->remove_mask_zipcode($ceps_array[0]));
        $end_zipcode_formatted = $this->to_int($this->remove_mask_zipcode($ceps_array[1]));
        
        if ($zipcode_formatted >= $start_zipcode_formatted && $zipcode_formatted <= $end_zipcode_formatted) {
            return $is_shipping_free_zipcode = true;
        }
        

        return $is_shipping_free_zipcode;
    }

    /*
    *@string
    */
    function remove_mask_zipcode($zipcode_string)
    {
        return str_replace("-", "", $zipcode_string);
    }

    /*
    *@int
    */
    function to_int($zipcode_formatted_string)
    {
        return intval($zipcode_formatted_string);
    }

    /*
    *@boolean
    */
    function  is_actual_page_contain($pageName){
        $current_url = home_url($_SERVER['REQUEST_URI']);
        if(strpos($current_url, $pageName)) {
            return 1;
        }
        else {
            return 0;
        }
    }

    function get_slug_cart_page() {
        $cart_page_id = wc_get_page_id( 'cart' );

// Obtenha o slug da página do carrinho
$cart_page_slug = get_post_field( 'post_name', $cart_page_id );

        return $cart_page_slug;
    }

    function get_slug_checkout_page(){
        $checkout_page_id = get_option( 'woocommerce_checkout_page_id' );

        $checkout_page_slug = get_post_field( 'post_name', $checkout_page_id );

        return $checkout_page_slug;
    }

    function seu_plugin_enqueue_styles() {
        wp_enqueue_style( 'seu-plugin-style', plugins_url( '../assets/css/style.css', __FILE__ ) );
    }

}

new WC_FSA();