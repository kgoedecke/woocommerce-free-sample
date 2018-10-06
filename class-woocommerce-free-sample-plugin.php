<?php
if ( ! class_exists( 'WooCommerce_Free_Sample_Plugin' ) ) {
	class WooCommerce_Free_Sample_Plugin {

		/**
		 * A reference to an instance of this class.
		 * 
		 * @since 1.0.0
		 * @var   object
		 */
		private static $instance;

		/**
		 * Returns an instance of this class.
		 * 
		 * @since  1.0.0
		 * @return object A single instance of this class.
		 */
		public static function get_instance() {

			if ( null == self::$instance ) {
				self::$instance = new WooCommerce_Free_Sample_Plugin();
			}

			return self::$instance;
		}

		/**
		 * Initializes the plugin by setting filters and administration functions.
		 * 
		 * @since 1.0.0
		 */
		private function __construct() {
			add_action( 'init', array( $this, 'load_plugin_textdomain' ) );

			add_action( 'template_redirect', array( $this, 'add_free_product_to_cart' ) );
			add_action( 'woocommerce_before_calculate_totals', array( $this, 'add_custom_price' ) );

			add_action( 'woocommerce_product_options_general_product_data', array( $this, 'create_free_item_field' ) );
			add_action( 'woocommerce_process_product_meta', array( $this, 'save_free_item_field' ) );
		}

		/**
		 * Load the plugin text domain for translation.
		 *
		 * @since 1.0.0
		 */
		public function load_plugin_textdomain() {
			load_plugin_textdomain(
				'woocommerce-free-sample',
				false,
				dirname( plugin_basename( __FILE__ ) ) . '/languages/'
			);
		}

		public function add_free_product_to_cart() {

			if ( ! is_admin() && is_user_logged_in() ) {

				$cart_items = WC()->cart->get_cart();
		
				if ( sizeof( $cart_items ) > 0 ) {
					$need_to_adds = array();
		
					foreach ( $cart_items as $cart_item_key => $values ) {
						$_product    = $values['data'];
						$_product_id = $_product->get_id();
						$quantity    = $values['quantity'];
		
						if ( ! isset( $need_to_adds[ $_product_id ] ) ) {
							$need_to_adds[ $_product_id ] = array( 'not_free_quantity' => $quantity );
						}
		
						if ( isset( $values['_wfs_custom_price'] ) ) {
							$need_to_adds[ $_product_id ] = array_merge( $need_to_adds[ $_product_id ], array( 'free_quantity' => $values['quantity'] ) );
							$need_to_adds[ $_product_id ] = array_merge( $need_to_adds[ $_product_id ], array( 'free_key' => $cart_item_key ) );
						}
					}
		
					if ( ! empty( $need_to_adds ) ) {
						foreach ( $need_to_adds as $id => $data ) {
							$_product     = wc_get_product( $id );
							$variation_id = 0;
		
							if ( $_product->is_type( 'variation' ) ) {
								$variation_id = $id;
								$id = $_product->get_parent_id();
							}
		
							$free_item = absint( get_post_meta( $id, '_wfs_free_item', true ) );
		
							if ( 0 === $free_item || '' === $free_item ) {
								continue;
							}
		
							if ( $data['not_free_quantity'] < $free_item ) {
		
								if ( isset( $data['free_key'] ) ) {
									WC()->cart->remove_cart_item( $data['free_key'] );
								}
		
								continue;
							}
		
							$free = floor( $data['not_free_quantity'] / $free_item );
		
							if ( ! isset( $data['free_quantity'] ) ) {
								WC()->cart->add_to_cart( $id, $free, $variation_id, array(), array( '_wfs_custom_price' => 0 ) );
		
							} else {
								$diff = $free - $data['free_quantity'];
		
								if ( $diff > 0 ) {
									WC()->cart->add_to_cart( $id, $diff, $variation_id, array(), array( '_wfs_custom_price' => 0 ) );
								} else {
									WC()->cart->set_quantity( $data['free_key'], $free );
								}
							}
						}
					}
				}
			}
		}

		public function add_custom_price( $cart_object ) {

			if ( ! empty( $cart_object ) ) {
				foreach ( $cart_object->cart_contents as $key => $value ) {
		
					if ( isset( $value['_wfs_custom_price'] ) ) {
						$value['data']->set_price( $value['_wfs_custom_price'] );
					}
				}
			}
		}

		public function create_free_item_field() {
			$args = array(
				'id'          => '_wfs_free_item',
				'label'       => esc_html__( 'Free item every X items', 'woocommerce-free-sample' ),
				'placeholder' => '10',
				'desc_tip'    => true,
				'description' => esc_html__( 'Enter a non-negative integer value (0 is skip)', 'woocommerce-free-sample' ),
			);
		
			woocommerce_wp_text_input( $args );
		}

		public function save_free_item_field( $post_id ) {
			$product   = wc_get_product( $post_id );
			$free_item = isset( $_POST['_wfs_free_item'] ) ? $_POST['_wfs_free_item'] : '';
		
			if ( '' !== $free_item ) {
				$free_item = absint( $free_item );
			}
		
			if ( 0 === $free_item ) {
				$free_item = '';
			}
		
			$product->update_meta_data( '_wfs_free_item', $free_item );
			$product->save();
		}
	}
}
