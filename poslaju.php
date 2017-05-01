<?php
 
/**
 * Plugin Name: Poslaju Shipping
 * Plugin URI: http://code.poslaju.com/tutorials/create-a-custom-shipping-method-for-woocommerce--cms-26098
 * Description: Poslaju Shipping Method for WooCommerce
 * Version: 1.0.0
 * Author: Bayu Permadi
 * License: GPL-3.0+
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */
 
if ( ! defined( 'WPINC' ) ) {
 
    die;
 
}
 
/*
 * Check if WooCommerce is active
 */
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
 
    function poslaju_shipping_method() {
        if ( ! class_exists( 'Poslaju_Shipping_Method' ) ) {
            class Poslaju_Shipping_Method extends WC_Shipping_Method {
                /**
                 * Constructor for your shipping class
                 *
                 * @access public
                 * @return void
                 */
                public function __construct($instance_id = 0) {
                    $this->id                 = 'poslaju'; 
                    $this->instance_id           = absint( $instance_id );
                    $this->method_title       = __( 'Poslaju Shipping', 'poslaju' );  
                    $this->method_description = __( 'Custom Shipping Method for Poslaju', 'poslaju' ); 
 
                    // Availability & Countries
                    $this->availability = 'including';
                    $this->countries = array(
                            'MY' // Malaysia
                        );
 
                    $this->init();
 
                    $this->enabled = isset( $this->settings['enabled'] ) ? $this->settings['enabled'] : 'yes';
                    $this->title = isset( $this->settings['title'] ) ? $this->settings['title'] : __( 'Poslaju Shipping', 'poslaju' );

                }
 
                /**
                 * Init your settings
                 *
                 * @access public
                 * @return void
                 */
                function init() {
                    // Load the settings API
                    $this->init_form_fields(); 
                    $this->init_settings(); 
 
                    // Save settings in admin if you have any defined
                    add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
                }
 
                /**
                 * Define settings field for this shipping
                 * @return void 
                 */
                function init_form_fields() { 
 
                    $this->form_fields = array(
 
                     'enabled' => array(
                          'title' => __( 'Enable', 'poslaju' ),
                          'type' => 'checkbox',
                          'description' => __( 'Enable this shipping.', 'poslaju' ),
                          'default' => 'yes'
                          ),
 
                     'title' => array(
                        'title' => __( 'Title', 'poslaju' ),
                          'type' => 'text',
                          'description' => __( 'Title to be display on site', 'poslaju' ),
                          'default' => __( 'Poslaju Shipping', 'poslaju' )
                          ),
 
                     'weight' => array(
                        'title' => __( 'Weight (kg)', 'poslaju' ),
                          'type' => 'number',
                          'description' => __( 'Maximum allowed weight', 'poslaju' ),
                          'default' => 100
                          ),
 
                     );
 
                }
 
                /**
                 * This function is used to calculate the shipping cost. Within this function we can check for weights, dimensions and other parameters.
                 *
                 * @access public
                 * @param mixed $package
                 * @return void
                 */
                public function calculate_shipping( $package = array() ) {
                    
                    $weight = 0;
                    $cost = 0;
                    $total_ship_cost = 0;
                    $state = $package["destination"]["state"];

                    $countries_obj   = new WC_Countries();
                    $default_country = $countries_obj->get_base_country();
                    $destination_states = $countries_obj->get_states( $default_country );

                    foreach ( $package['contents'] as $item_id => $values ) 
                    { 
                        $_product = $values['data']; 
                        $obj = json_decode($_product);
                        $post_id = $obj->id;
                        $vendor_id = get_post($post_id)->post_author;
                        $vendor_city = get_the_author_meta( '_vendor_city', $vendor_id );
                        //$weight = $weight + $_product->get_weight() * $values['quantity']; 
                        $weight = $_product->get_weight() * $values['quantity']; 
                        $weight = wc_get_weight( $weight, 'kg' );
                        if ($destination_states[$state] == $vendor_city) {
                          if(  $weight >= 0 && $weight <= 1 ) {
                              $cost = 4.5;
                          }
                          elseif(  $weight > 1 && $weight <= 2 ) {
                              $cost = 9;
                          } 
                          elseif( $weight > 2 && $weight <= 2.5 ) {
                              $cost = 9.5;
                          } 
                          else {
                              $additional_wght = $weight-2.5;
                              $additional_cost = ceil($additional_wght/0.5)*0.5;
                              $cost = 9.5+$additional_cost;
                          }
                        }
                        else {
                          if(  $weight >= 0 && $weight <= 1 ) {
 
                              $cost = 7;
       
                          }
                          elseif(  $weight > 1 && $weight <= 2 ) {
 
                              $cost = 14;
       
                          } 
                          elseif( $weight > 2 && $weight <= 2.5 ) {
       
                              $cost = 16;
       
                          } 
                          else {
                              $additional_wght = $weight-2.5;
                              $additional_cost = ceil($additional_wght/0.5)*1;
                              $cost = 16+$additional_cost;
       
                          }
                        }
                        $total_ship_cost = $total_ship_cost + $cost;
                        
                    }
 
                    //$zoneFromCountry = $countryZones[ $country ];
                    //$priceFromZone = $zonePrices[ $zoneFromCountry ];
 
                    //$cost += $priceFromZone;
 
                    $rate = array(
                        'id' => $this->id,
                        'label' => $this->title,
                        'cost' => $total_ship_cost
                    );
 
                    $this->add_rate( $rate );
                    
                }
            }
        }
    }
 
    add_action( 'woocommerce_shipping_init', 'poslaju_shipping_method' );
 
    function add_poslaju_shipping_method( $methods ) {
        $methods[] = 'Poslaju_Shipping_Method';
        return $methods;
    }
 
    add_filter( 'woocommerce_shipping_methods', 'add_poslaju_shipping_method' );
 
    function poslaju_validate_order( $posted )   {
 
        $packages = WC()->shipping->get_packages();
 
        $chosen_methods = WC()->session->get( 'chosen_shipping_methods' );
         
        if( is_array( $chosen_methods ) && in_array( 'poslaju', $chosen_methods ) ) {
             
            foreach ( $packages as $i => $package ) {
 
                if ( $chosen_methods[ $i ] != "poslaju" ) {
                             
                    continue;
                             
                }
 
                $Poslaju_Shipping_Method = new Poslaju_Shipping_Method();
                $weightLimit = (int) $Poslaju_Shipping_Method->settings['weight'];
                $weight = 0;
 
                foreach ( $package['contents'] as $item_id => $values ) 
                { 
                    $_product = $values['data']; 
                    $weight = $weight + $_product->get_weight() * $values['quantity']; 
                }
 
                $weight = wc_get_weight( $weight, 'kg' );
                
                if( $weight > $weightLimit ) {
 
                        $message = sprintf( __( 'Sorry, %d kg exceeds the maximum weight of %d kg for %s', 'poslaju' ), $weight, $weightLimit, $Poslaju_Shipping_Method->title );
                             
                        $messageType = "error";
 
                        if( ! wc_has_notice( $message, $messageType ) ) {
                         
                            wc_add_notice( $message, $messageType );
                      
                        }
                }
            }       
        } 
    }
 
    add_action( 'woocommerce_review_order_before_cart_contents', 'poslaju_validate_order' , 10 );
    add_action( 'woocommerce_after_checkout_validation', 'poslaju_validate_order' , 10 );
}