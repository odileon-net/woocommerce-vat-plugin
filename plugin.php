<?php
/**
 * Plugin Name: Odileon - WooCommerce VAT field plugin
 * Plugin URI: https://www.odileon.net
 * Description: Add VAT (BTW/TVA) Functionality to WooCommerce
 * Author: Vandekerckhove Jelle
 * Author URI: https://www.odileon.net
 * Version: 1.0
 * Text Domain: woocommerce-vat-field-plugin
 */

defined( 'ABSPATH' ) || exit;

class My_WooCommerce_VAT_Field_Plugin {
    public function __construct() {
        add_filter( 'woocommerce_default_address_fields', array( $this, 'add_vat_checkout_field' ), 99999 );
        add_filter( 'woocommerce_customer_meta_fields', array( $this, 'odl_admin_address_field' ) );
        add_filter( 'woocommerce_order_get_formatted_billing_address', array( $this, 'order_details_billing_add_vat_field' ), 10, 3 );
        add_filter( 'woocommerce_order_get_formatted_shipping_address', array( $this, 'order_details_shipping_add_vat_field' ), 10, 3 );
        add_filter( 'woocommerce_admin_billing_fields', array( $this, 'order_edit_shipping_info_form_add_vat_field' ), 10, 1 );
        add_action( 'woocommerce_admin_order_data_after_order_details', array( $this, "add_edit_order_css" ) );
        add_action( 'admin_menu', array( $this, 'add_reusable_blocks_admin_menu' ) );
        
        add_action( 'woocommerce_save_account_details', array( $this, 'save_billing_vat_number' ) );
        add_action( 'woocommerce_edit_account_form', array( $this, 'display_billing_vat_number_field' ) ); 
    }
  
    public function add_edit_order_css() {
        echo '<style>
        #order_data .order_data_column ._billing_company_field {
            width: 48%;
            clear: left;
        }
        #order_data .order_data_column ._billing_vat_field {
            float: right;
            clear: right;
        }
        </style>';
    }
    
    public function order_edit_shipping_info_form_add_vat_field( $fields ) {
        $fields['company']['class'] = array( 'form-row-wide' );
        return $this->array_insert_value_at_index( $fields, ['vat' => $this->get_vat_field()], 3 );
    }
    
    public function order_details_billing_add_vat_field( $address, $raw_address, $order ) {
        $a = $raw_address['company'] . '<br>';
        if ( $order->get_meta( '_billing_vat' ) !== '' ) {
            $a .= $order->get_meta( '_billing_vat' ) . '<br>';
        }
        $a .= $raw_address['first_name'] . ' ' . $raw_address['last_name'] . '<br>';
        $a .= $raw_address['address_1'] . '<br>';
        if ( $raw_address['address_2'] !== '' ) {
            $a .= $raw_address['address_2'] . '<br>';
        }
        $a .= $raw_address['postcode'] . ' ' . $raw_address['city'] . '<br>';
        if ( $raw_address['state'] !== '' ) {
            $a .= $raw_address['state'] . '<br>';
        }
        
        return $a;
    }
    
    public function order_details_shipping_add_vat_field( $address, $raw_address, $order ) {
        $a = $raw_address['company'] . '<br>';
        if ( $order->get_meta( '_shipping_vat' ) !== '' ) {
            $a .= $order->get_meta( '_shipping_vat' ) . '<br>';
        }
        $a .= $raw_address['first_name'] . ' ' . $raw_address['last_name'] . '<br>';
        $a .= $raw_address['address_1'] . '<br>';
        if ( $raw_address['address_2'] !== '' ) {
            $a .= $raw_address['address_2'] . '<br>';
        }
        $a .= $raw_address['postcode'] . ' ' . $raw_address['city'] . '<br>';
        if ( $raw_address['state'] !== '' ) {
            $a .= $raw_address['state'] . '<br>';
        }
        
        return $a;
    }
    
    public function odl_admin_address_field( $admin_fields ) {
        $admin_fields['billing']['fields'] = $this->array_insert_value_at_index( $admin_fields['billing']['fields'], ['billing_vat' => $this->get_admin_vat_field()], 3 );
        $admin_fields['shipping']['fields'] = $this->array_insert_value_at_index( $admin_fields['shipping']['fields'], ['shipping_vat' => $this->get_admin_vat_field()], 4 );
        
        return $admin_fields;
    }
    
    public function get_admin_vat_field() {
        $field = $this->get_vat_field();
        
        $field['class'] = 'regular-text';
        
        return $field;
    }
    
    public function get_vat_field() {
        return [
            'placeholder' => __( 'BTW', 'woocommerce-vat-field' ),
            'label'       => __( 'BTW', 'woocommerce-vat-field' ),
            'required'    => false,
            'class'       => array( 'form-row-wide' ),
            'clear'       => true,
            'priority'    => 31,
            'autocomplete' => "null",
        ];
    }
    
    public function add_vat_checkout_field( $address_fields ) {
        return $this->array_insert_value_at_index( $address_fields, ['vat' => $this->get_vat_field()], 2 );
    }
    
    public function array_insert_value_at_index( $array, $value, $pos ) {
        return array_merge( array_slice( $array, 0, $pos ), $value, array_slice( $array, $pos ) );
    }

    public function save_billing_vat_number( $user_id ) {
        if ( isset( $_POST['billing_vat'] ) ) {
            $vat_number = sanitize_text_field( $_POST['billing_vat'] );
            update_user_meta( $user_id, 'billing_vat', $vat_number );
        }
    }

    public function display_billing_vat_number_field() {
        $user_id    = get_current_user_id();
        $vat_number = get_user_meta( $user_id, 'billing_vat', true );
        ?>
        <div class="woocommerce-address-fields">
            <fieldset>
                <legend><?php _e( 'BTW', 'woocommerce-vat-field' ); ?></legend>
                <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
                    <label for="billing_vat"><?php _e( 'BTW', 'woocommerce-vat-field' ); ?></label>
                    <input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="billing_vat" id="billing_vat" value="<?php echo esc_attr( $vat_number ); ?>" />
                </p>
            </fieldset>
        </div>
        <?php
    }
}

new My_WooCommerce_VAT_Field_Plugin();
