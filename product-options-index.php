<?php
/**
 * Plugin Name: Product Options Index for Woocommerce
 * Description: View Woocommerce Product most important options easily
 * Plugin URI:  https://moztik.com/poi
 * Version:     1.0.2
 * Author:      Stephen Lee
 * Author URI:  https://moztik.com/
 * Text Domain: product-options-index
 * Domain Path: /languages
 * License: GPLv3 or later
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 * Domain Path: /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die();
}

class Product_Options_Index {

	protected static $instance = null;
	public $plugin_slug = 'product-options-index';
	public $action_name;

	public static function get_instance() {
		if (null == self::$instance) {
			self::$instance = new self;
		}

		return self::$instance;
	}


	/**
	 * Initialize
	 * 
	 * @return
	 */
	public function __construct() {
		// set localisation
		$this->load_plugin_textdomain();

		// check wordpress version
		$wp_version = get_bloginfo('version');

		define( 'POI_VERSION', '1.0.2' );

		if (version_compare($wp_version, '4.0', '<')) {
			add_action('admin_notices', array($this, 'add_notice'));
			return;
		}

		add_action('admin_enqueue_scripts', array($this, 'add_assets'), 999);

		$this->filters();
	}


	/**
	 * Load localisation files
	 *
	 */
	public function load_plugin_textdomain() {
		$locale = apply_filters('plugin_locale', get_locale(), $this->plugin_slug);
	}


	/**
	 * Add notice
	 */
	public function add_notice() {
		echo '<div id="message" class="error"><p>' . esc_html__('Options Index for Woocommerce requires WordPress 4.0 or higher.', $this->plugin_slug) . '</p></div>';
	}


	/**
	 * Add assets to backend (admin) or frontend
	 * 
	 * @return
	 */
	public function add_assets() {
		//Do offline for Material Icons
		//wp_enqueue_style('material-icons', '//fonts.googleapis.com/icon?family=Material+Icons');
		wp_enqueue_style('material-icons', plugins_url('css/material-icons.css', __FILE__), array(), '3.0.1' );

		wp_enqueue_style('poi-css', plugins_url('css/poi.css', __FILE__), array(), POI_VERSION );
	}


	/**
	 * Initiate required filters.
	 *
	 * @since  1.0
	 */
	private function filters() {
		add_filter( 'manage_edit-product_columns', array( &$this, 'poi_column' ), 10, 1 );
		add_action('manage_posts_custom_column', array($this, 'poi_column_content'), 2);
	}


	function poi_column( $columns ) {
		//add column
		return array_slice( $columns, 0, 9, true ) + array( 'poi_column' => 'Options' ) + array_slice( $columns, 9, NULL, true );
	}


	function poi_column_content( $column ) {

		global $post, $woocommerce;

		if ( $column == 'poi_column' ) {
			$product_id = $post->ID;
			// output variable
			$output = '';

			// Get product object
			$product = wc_get_product( $product_id );

			// Get Product Variations - WC_Product_Attribute Object for future version
			$product_attributes = $product->get_attributes();

			switch ($product->get_catalog_visibility()) {
				case "hidden":
					$catalog_visibility = 'visibility_off';
					$catalog_tt = __('Hidden product', 'woocommerce');
					$catalog_cl = 'red600';
					break;
				case "search":
					$catalog_visibility = 'visibility';
					$catalog_tt = __('Visible on search', 'woocommerce');
					$catalog_cl = 'orange600';
					break;
				case "catalog":
					$catalog_visibility = 'visibility';
					$catalog_tt = __('Visible on shop', 'woocommerce');
					$catalog_cl = 'green600';
					break;
				default:
					$catalog_visibility = 'visibility';
					$catalog_tt = __('Visible on search and shop', 'woocommerce');
					$catalog_cl = 'defset';
					break;
			}

			switch ($product->get_type()) {
				case "variable":
					$type    = 'track_changes';
					$type_tt = __('Variable type', 'woocommerce');
					$type_cl = 'orange600';
					break;
				case "grouped":
					$type    = 'group_work';
					$type_tt = __('Grouped product', 'woocommerce');
					$type_cl = 'blue600';
					break;
				case "external":
					$type    = 'merge_type';
					$type_tt = __('External/Affiliate product', 'woocommerce');
					$type_cl = 'red600';
					break;
				case "subscription":
					$type    = 'card_membership';
					$type_tt = __('Simple subscription', 'woocommerce');
					$type_cl = 'subscrip';
					break;
				case "variable-subscription":
					$type    = 'card_membership';
					$type_tt = __('Variable subscription', 'woocommerce');
					$type_cl = 'vsubscrip';
					break;
				case "booking":
					$type    = 'local_activity';
					$type_tt = __('Bookable product', 'woocommerce');
					$type_cl = 'bookable';
					break;
				default:
					$type    = 'devices';
					$type_tt = __('Simple product', 'woocommerce');
					$type_cl = 'simple';
					break;
			}

			if ( $product->get_reviews_allowed() ) {
				$reviews    = 'speaker_notes';
				$reviews_tt = 'Review open';
				$reviews_cl = 'blue600';
				$reviews_co = $product->get_review_count() > 0 ? '<span class="review-count">'. $product->get_review_count() .'</span>' : '';
			} else {
				$reviews    = 'speaker_notes_off';
				$reviews_tt = 'Review closed';
				$reviews_cl = 'defset';
				$reviews_co = $product->get_review_count() > 0 ? '<span class="review-count closed">'. $product->get_review_count() .'</span>' : '';
			}
			
			//Rating
			if ( $product->get_average_rating() ) {
				$ratings   = 'star';
				$rating_cl = 'orange600';
				$rating    = $product->get_average_rating();
				$rating    = round($rating, 0);
				$rating_tt = 'Rated average '. $rating .' based on '. $product->get_rating_count() .' customer rating';
			} else {
				$ratings   = 'star_outline';
				$rating_cl = 'defset';
				$rating_tt = 'No rating';
			}

			echo '<span class="material-icons md-24 '. $catalog_cl .' tips" data-tip="'. $catalog_tt .'">'. $catalog_visibility .'</span>';
			echo '<span class="material-icons md-24 '. $type_cl .' tips" data-tip="'. $type_tt .'">'. $type .'</span>';
			echo '<span class="review-poi"><span class="material-icons md-24 '. $reviews_cl .' tips" data-tip="'. $reviews_tt .'">'. $reviews .'</span>'. $reviews_co .'</span>';
			echo '<span class="review-poi"><span class="material-icons md-24 '. $rating_cl .' tips" data-tip="'. $rating_tt .'">'. $ratings .'</span><span class="rating-avg">'. $rating .'</span></span>';
		}
	}
}

add_action('plugins_loaded', array('Product_Options_Index', 'get_instance'));
