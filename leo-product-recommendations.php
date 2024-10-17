<?php
/**
 * Plugin Name: Leo Product Recommendations for WooCommerce
 * Plugin URI: https://leocoder.com/leo-product-recommendations
 * Description: Recommend products smartly for boosting WooCommerce sales by nice-looking add to cart popup
 * Version: 2.8.0
 * Requires at least: 5.7
 * Requires PHP: 7.4
 * Author: Md Hasanuzzaman
 * Author URI: https://leocoder.com/
 * Text Domain: leo-product-recommendations
 * Domain Path: /languages
 * WC requires at least: 7.0
 * WC tested up to: 9.3.3
 * License: GPLv3 or later License
 * URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * HPOS compatibility declaration
 */
add_action('before_woocommerce_init', function(){
    if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
    }
});

use LoeCoder\Plugin\ProductRecommendations\Product_Recommendations;

if (!class_exists(Product_Recommendations::class)) {
   require plugin_dir_path(__FILE__) . 'includes/class-product-recommendations.php';
}

/**
 * Plugin execution
 * @since    1.0.0
 */
function leo_product_recommendations() {
    return Product_Recommendations::init(__FILE__);
}
leo_product_recommendations();
