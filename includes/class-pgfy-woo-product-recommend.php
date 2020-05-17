<?php
/**
 * The core plugin class
 *
 * @since      1.0.0
 * @author     Pluginsify
 */

class Pgfy_Woo_Product_Recommend
{
	/**
	 * Inctance of class
	 *
	 * @var instance
	 */
	static protected $instance;

	/**
	 * Array of all products recommend data.
	 *
	 * @var instance
	 */
	static protected $pr_meta = array();

	/**
	 * Plugin root file __FILE__
	 *
	 * @var string
	 */
	static protected $__FILE__;

	/**
	 * Pro Plugin __FILE__
	 * Root file of plugin pro version
	 * @var string
	 * @since      1.0.0
	 */
	static protected $__FILE__PRO__ = WP_PLUGIN_DIR . '/woocommerce-product-recommend-pro/woocommerce-product-recommend-pro.php';

	/**
	 * Plugin setting id used to save setting data in option table
	 *
	 * @var string
	 * @since      1.0.0
	 */
	static protected $setting_id = 'pgfy_wpr_settings';

	/**
	 * Class constructor, initialize everything
	 * @since      1.0.0
	 */
	private function __construct($__FILE__)
	{
		self::$__FILE__ = $__FILE__;

		register_activation_hook(self::$__FILE__, array($this, 'on_activation'));
		register_deactivation_hook(self::$__FILE__, array($this, 'on_deactivation'));
		add_action('plugins_loaded', array($this, 'on_plugins_loaded'));
	}

	/**
	 * Action after plugin active.
	 * enable ajax add to cart and disabel redirec to cart page.
	 * 
	 * @since      1.0.0
	 */
	public function on_activation()
	{
		update_option('woocommerce_enable_ajax_add_to_cart', 'yes');
		update_option('woocommerce_cart_redirect_after_add', 'no');
	}

	/**
	 * Action after plugin deactive
	 *
	 * @since      1.0.0
	 * @return void
	 */
	public function on_deactivation()
	{
		// do nothing
	}

	/**
	 * Setup plugin once all other plugins are loaded.
	 *
	 * @since      1.0.0
	 * @return void
	 */
	public function on_plugins_loaded()
	{
		$this->load_textdomain();

		if (!$this->has_satisfied_dependencies()) {
			add_action('admin_notices', array($this, 'render_dependencies_notice'));
			return;
		}

		// before going to action 
		// used this hook in pro plugin
		do_action('wpr_before_action');

		$this->includes();
		$this->hooks();
	}

	/**
	 * Load Localization files.
	 *
	 * @since      1.0.0    
	 * @return void
	 */
	public function load_textdomain()
	{
		load_plugin_textdomain('woocommerce-product-recommend', false, dirname(plugin_basename(self::$__FILE__)) . '/languages');
	}


	/**
	 * Returns true if all dependencies for the plugin are loaded.
	 *
	 * @since      1.0.0   
	 * @return bool
	 */
	protected function has_satisfied_dependencies()
	{
		$dependency_errors = $this->get_dependency_errors();
		return 0 === count($dependency_errors);
	}

	/**
	 * Get an array of dependency error messages.
	 *
	 * @since      1.0.0   
	 * @return array all dependency error message. 
	 */
	protected function get_dependency_errors()
	{
		$errors                      = array();
		$wordpress_version           = get_bloginfo('version');
		$minimum_wordpress_version   = $this->get_min_wp();
		$minimum_woocommerce_version = $this->get_min_wc();
		$minium_php_verion 			 = $this->get_min_php();

		$wordpress_minimum_met       = version_compare($wordpress_version, $minimum_wordpress_version, '>=');
		$woocommerce_minimum_met     = class_exists('WooCommerce') && version_compare(WC_VERSION, $minimum_woocommerce_version, '>=');
		$php_minimum_met     = version_compare(phpversion(), $minium_php_verion, '>=');

		if (!$woocommerce_minimum_met) {
			
			$errors[] = sprintf(
				/* translators: 1. link of plugin, 2. plugin version. */
				__('The WooCommerce Product Recommond Pro plugin requires <a href="%1$s">WooCommerce</a> %2$s or greater to be installed and active.', 'woocommerce-product-recommend'),
				'https://wordpress.org/plugins/woocommerce/',
				$minimum_woocommerce_version
			);
		}

		if (!$wordpress_minimum_met) {
			$errors[] = sprintf(
				/* translators: 1. link of wordpress, 2. version of WordPress. */
				__('The WooCommerce Product Recommond Pro plugin requires <a href="%1$s">WordPress</a> %2$s or greater to be installed and active.', 'woocommerce-product-recommend'),
				'https://wordpress.org/',
				$minimum_wordpress_version
			);
		}

		if (!$php_minimum_met) {
			$errors[] = sprintf(
				/* translators: 1. version of php */
				__('The WooCommerce Product Recommond Pro plugin requires <strong>php verion %s</strong> or greater. Please update your server php version.', 'woocommerce-product-recommend'),
				$minium_php_verion
			);
		}

		return $errors;
	}

	/**
	 * Notify users about plugin dependency
	 * 
	 * @since      1.0.0
	 * @return void
	 */
	public function render_dependencies_notice()
	{
		$message = $this->get_dependency_errors();
		printf('<div class="error"><p>%s</p></div>', implode(' ', $message));
	}

	/**
	 * Include addon features with this plugin
	 * 
	 * @since      1.0.0
	 * @return void
	 */
	public function includes()
	{
		// handle all admin ajax request
		$this->admin_ajax();
	}

	/**
	 * Handle All selection panel Ajax Request
	 * @since      1.0.0
	 * @return void
	 */
	public function admin_ajax()
	{
		if (!class_exists('Pgfy_Wpr_Admin_Ajax')) {
			include_once($this->get_path('includes/class-pgfy-wpr-admin-ajax.php'));
		}
		new Pgfy_Wpr_Admin_Ajax();
	}

	/**
	 * Add all actions hook.
	 * 
	 * @since      1.0.0
	 * @return void
	 */
	public function hooks()
	{
		add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts')); // back-end scripts
		add_action('wp_enqueue_scripts', array($this, 'wp_enqueue_scripts')); // front-end scripts

		add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
		add_action('save_post', array($this, 'on_save_post'));

		add_action('wp_ajax_fetch_modal_products', array($this, 'fetch_modal_products'));
		add_action('wp_ajax_nopriv_fetch_modal_products', array($this, 'fetch_modal_products'));

		add_action('wp_ajax_pgfy_ajax_add_to_cart', array($this, 'ajax_add_to_cart'));
		add_action('wp_ajax_nopriv_pgfy_ajax_add_to_cart', array($this, 'ajax_add_to_cart'));

		add_action('wp_ajax_pgfy_get_cart_items', array($this, 'get_cart_items'));
		add_action('wp_ajax_nopriv_pgfy_get_cart_items', array($this, 'get_cart_items'));

		add_action('after_setup_theme', array($this, 'include_templates')); // include modal template

		add_filter('nonce_user_logged_out', array($this, 'nonce_fix'), 100, 2);
	}

	/**
	 * Enqueue all admin scripts and styles
	 * 
	 * @since      1.0.0
	 * @return void
	 */
	public function admin_enqueue_scripts()
	{
		if (!$this->is_pro_activated()) {
			wp_enqueue_script('selection-panel-script', $this->get_url('assets/js/panel.js'), array('lodash', 'wp-element', 'wp-components', 'wp-polyfill', 'wp-i18n', 'jquery'), false, true);
			wp_localize_script('selection-panel-script', 'ajax_url', admin_url('admin-ajax.php'));
			wp_enqueue_style('selection-panel-style', $this->get_url('assets/css/panel.css'));
		}
	}

	/**
	 * Enqueue all front end scripts and styles
	 * 
	 * @since      1.0.0
	 * @return void
	 */
	public function wp_enqueue_scripts()
	{

		$settings = $this->get_settings();
		$display_type = ($this->is_pro_activated() && !empty($settings['display_type'])) ? $settings['display_type'] : 'grid';

		wp_enqueue_script('wpr-modal', $this->get_url('assets/js/modal.js'), array('jquery'), false, true);
		wp_localize_script('wpr-modal', 'pgfy_ajax_modal', array(
			'url' => admin_url('admin-ajax.php'),
			'nonce' => wp_create_nonce('pgfy-ajax-modal'),
			'display_type' => $display_type
		));

		if (is_product()) {
			wp_enqueue_script('wpr-ajax-add-to-cart', $this->get_url('assets/js/ajax-add-to-cart.js'), array('jquery', 'wp-i18n'), false, true);
			wp_localize_script('wpr-ajax-add-to-cart', 'pgfy_ajax', array(
				'url' => admin_url('admin-ajax.php'),
				'nonce' => wp_create_nonce('pgfy-add-to-cart')
			));
		}

		if (!$this->is_pro_activated()) {
			wp_enqueue_style('wpr-modal', $this->get_url('assets/css/modal.css'));
		}
	}


	/**
	 * Add Meta Box
	 * 
	 * @since      1.0.0
	 * @return void
	 */
	public function add_meta_boxes()
	{
		add_meta_box(
			'plfy_prodcut_selection',
			__('Recommend Products', 'woocommerce-product-recommend'),
			array($this, 'product_selection'),
			array('product')
		);
	}

	/**
	 * Include Products Recommend Panel
	 * 
	 * @since      1.0.0
	 * @return void
	 */

	public function product_selection($post)
	{
		include_once($this->get_path('includes/option-select-products.php'));
	}

	/**
	 * Plugin file URL
	 * 
	 * @since      1.0.0
	 * @return     URL link of file.
	 * @param      stirng File name with folder path.
	 */
	public function get_url($file = '')
	{
		return plugin_dir_url(self::$__FILE__) . $file;
	}

	/**
	 * Get file path of plugin file
	 * 
	 * @since      1.0.0
	 * @param      stirng relative path of plugin file
	 * @return     string full path of plugin file
	 */
	public function get_path($file_path)
	{
		return plugin_dir_path(self::$__FILE__) . $file_path;
	}

	/**
	 * Get template path from theme or plugin
	 * 
	 * @since      1.0.0
	 * @param      stirng File name with folder path.
	 * @return     string Full of template from theme or pro plguin or plugin.
	 */
	public function get_templates_path($file_path)
	{
		//from theme
		$theme_tmpl = get_stylesheet_directory() . '/wpr/' . $file_path;
		if (file_exists($theme_tmpl)) {
			return $theme_tmpl;
		}

		//from pro version
		if ($this->is_pro_activated()) {
			return plugin_dir_path(self::$__FILE__PRO__) . $file_path;
		}

		//from plugin
		return plugin_dir_path(self::$__FILE__) . $file_path;
	}

	/**
	 * Get plugin slug
	 * 
	 * @since      1.0.0
	 * @return     string slug of plugin
	 */
	public function get_slug()
	{
		return basename(self::$__FILE__, '.php');
	}

	/**
	 * Save post callback function
	 * 
	 * @since      1.0.0
	 * @return  void;
	 */
	public function on_save_post($id)
	{
		if (isset($_POST['_pgfy_pr_data'])) {
			update_post_meta($id, '_pgfy_pr_data', $_POST['_pgfy_pr_data']);
		}
	}

	/**
	 * Ajax call back to query modal products
	 * 
	 * @since      1.0.0
	 * @return  void;
	 */

	public function fetch_modal_products()
	{
		include($this->get_templates_path('templates/template-recommend-products.php'));
	}

	/**
	 * Ajax callback to add to cart for singe product page
	 * 
	 * @since      1.0.0
	 * @return  json responsve with json data
	 */
	public function ajax_add_to_cart()
	{

		if ($_REQUEST['data'] && $_REQUEST['nonce'] && wp_verify_nonce($_REQUEST['nonce'], 'pgfy-add-to-cart')) {
			if (!class_exists('Pgfy_Ajax_Add_To_Cart')) {
				include($this->get_path('includes/class-pgfy-ajax-add-to-cart.php'));
			}

			new Pgfy_Ajax_Add_To_Cart($_REQUEST['data']);
		} else {
			wp_send_json_error(array('message' => 'Bad request'), 400);
		}
	}

	/**
	 * Ajax callback to get items already added to cart
	 * 
	 * @since      1.0.0
	 * @return  json response with json data of cart products
	 */
	public function get_cart_items()
	{
		$products_ids_array = array();
		foreach (WC()->cart->get_cart() as $cart_item) {
			$products_ids_array[] = $cart_item['product_id'];
		}
		wp_send_json($products_ids_array);
	}

	/**
	 * Include modal templates
	 * @since      1.0.0
	 * @return  void;
	 */
	public function include_templates()
	{
		// modal in shop / archives page
		if (apply_filters('wc_pr_show_in_product_archives', true)) {
			add_action('woocommerce_after_shop_loop_item', array($this, 'product_archive_modal'));
		}

		// modal in single product page
		if (apply_filters('wc_pr_show_in_singe_product', true)) {
			add_action('wp_footer', array($this, 'product_single_modal'));
		}

		// modal in WooCommerce Gutenberg products block
		if (apply_filters('wc_pr_show_in_gutenberg_product_block', true)) {
			add_filter('woocommerce_blocks_product_grid_item_html', array($this, 'product_gutenberg_block'), 10, 3);
		}
	}

	/**
	 * Get recommend product heading
	 *
	 * @param int $product_id
	 * @return string heading of recommend product
	 * @since   1.0.0
	 */
	public function get_recommend_products_heading($product_id)
	{
		$pr_data = $this->get_pr_data($product_id);
		$heading = (!!$pr_data && isset($pr_data['heading'])) ? $pr_data['heading'] : '';
		return $heading;
	}

	/**
	 * Check selection method menual or not 
	 * @return bool menually selected or not
	 * @since   1.0.0
	 */
	public function is_menually_selection($product_id)
	{
		$pr_data = $this->get_pr_data($product_id);
		return (!empty($pr_data['type']) && $pr_data['type'] === 'menual-selection');
	}

	/**
	 * Check selection method dynamic or not 
	 * @return bool dynamic selected or not
	 * @since   1.0.0
	 */
	public function is_dynamic_selection($product_id)
	{
		$pr_data = $this->get_pr_data($product_id);
		return (!empty($pr_data['type']) && $pr_data['type'] === 'dynamic-selection');
	}

	/**
	 * Array of recommend product ids
	 *
	 * @param int $product_id
	 * @return array array of recommend products ids
	 * @since      1.0.0
	 */
	public function get_recommend_products_id($product_id)
	{
		$data = $this->get_pr_data($product_id);
		$recommended_products_ids = array();

		if ($this->is_menually_selection($product_id)) {
			$recommended_products_ids = !empty($data['products']) ? $data['products'] : array();
		} elseif ($this->is_dynamic_selection($product_id)) {
			$args = array(
				'post_type' => 'product',
				'post__not_in' => array($product_id)
			);

			if (!empty($data['number'])) {
				$args['posts_per_page'] = (int) $data['number'];
			}

			if (!empty($data['categories']) && !empty($data['tags'])) {

				$categories = $data['categories'];
				$categories = array_map(function ($category) {
					return (int) $category;
				}, $categories);

				$tags = $data['tags'];
				$tags = array_map(function ($tag) {
					return (int) $tag;
				}, $tags);

				$args['tax_query'] = array(
					'relation' => 'AND',
					array(
						'taxonomy' => 'product_cat',
						'field'    => 'term_id',
						'terms'    => $categories
					),
					array(
						'taxonomy' => 'product_tag',
						'field'    => 'term_id',
						'terms'    => $tags
					),
				);
			} else if (!empty($data['categories'])) {

				$categories = $data['categories'];
				$categories = array_map(function ($category) {
					return (int) $category;
				}, $categories);

				$args['tax_query'] = array(
					array(
						'taxonomy' => 'product_cat',
						'field'    => 'term_id',
						'terms'    => $categories
					)
				);
			} else if (!empty($data['tags'])) {
				$tags = $data['tags'];
				$tags = array_map(function ($tag) {
					return (int) $tag;
				}, $tags);

				$args['tax_query'] = array(
					array(
						'taxonomy' => 'product_tag',
						'field'    => 'term_id',
						'terms'    => $tags
					)
				);
			}

			$orderby = 'date';
			$meta_key = '';
			$order = 'desc';

			switch ($data['orderby']) {

				case 'newest':
					$orderby = 'date';
					$meta_key = '';
					$order = 'desc';
					break;

				case 'oldest':
					$orderby = 'date';
					$meta_key = '';
					$order = 'asc';
					break;

				case 'rand':
					$orderby = 'rand';
					$meta_key = '';
					$order = 'desc';
					break;

				case 'popularity':
					$orderby = 'meta_value_num';
					$meta_key = 'total_sales';
					$order = 'desc';
					break;

				case 'rating':
					$orderby = 'meta_value_num';
					$meta_key = '_wc_average_rating';
					$order = 'desc';
					break;

				case 'lowprice':
					$orderby = 'meta_value_num';
					$meta_key = '_regular_price';
					$order = 'asc';
					break;

				case 'heighprice':
					$orderby = 'meta_value_num';
					$meta_key = '_regular_price';
					$order = 'desc';
					break;

				case 'title':
					$orderby = 'title';
					$meta_key = '';
					$order = 'asc';
					break;
			}

			if (!empty($orderby)) {
				$args['orderby'] = $orderby;
			}

			if (!empty($meta_key)) {
				$args['meta_key'] = $meta_key;
			}

			if (!empty($order)) {
				$args['order'] = $order;
			}

			if (!empty($data['sale'])) {
				$args['post__in'] = array_merge(array(0), wc_get_product_ids_on_sale());
			}

			$posts = get_posts($args);

			$recommended_products_ids = array_map(function ($post) {
				return $post->ID;
			}, $posts);
		}

		return $recommended_products_ids;
	}



	/**
	 * Add modal to archive / shop page prodcuts
	 * @since      1.0.0
	 * @return void;
	 */
	public function product_archive_modal()
	{
		global $product;
		$product_id = $product->get_id();

		if ($this->is_pro_activated() || $this->is_menually_selection($product_id)) : // free version only support menual selection

			$modal_heading = $this->get_recommend_products_heading($product_id);
			$recommended_products_ids = $this->get_recommend_products_id($product_id);

			if (!empty($recommended_products_ids)) {
				add_action('wp_footer', function () use ($product_id, $modal_heading, $recommended_products_ids) {
					include($this->get_templates_path('templates/template-modal.php'));
				});
			}
		endif;
	}

	/**
	 * Add modal to single product page
	 * @since      1.0.0
	 * @return void;
	 */
	public function product_single_modal()
	{
		if (!is_product()) {
			return false;
		}

		global $product;
		$product_id = $product->get_id();


		if ($this->is_pro_activated() || $this->is_menually_selection($product_id)) : // free version only support menual selection
			$modal_heading = $this->get_recommend_products_heading($product_id);
			$recommended_products_ids = $this->get_recommend_products_id($product_id);

			if (!empty($recommended_products_ids)) {
				include($this->get_templates_path('templates/template-modal.php'));
			}
		endif;
	}

	/**
	 * Add modal to Guterberg block product
	 * @since      1.0.0
	 */
	public function product_gutenberg_block($html, $data, $product)
	{
		$product_id = $product->get_id();

		if ($this->is_pro_activated() || $this->is_menually_selection($product_id)) : // free version only support menu selection
			$modal_heading = $this->get_recommend_products_heading($product_id);
			$recommended_products_ids = $this->get_recommend_products_id($product_id);

			if (!empty($recommended_products_ids)) {
				add_action('wp_footer', function () use ($product_id, $modal_heading, $recommended_products_ids) {
					include($this->get_templates_path('templates/template-modal.php'));
				});
			}
		endif;

		return $html;
	}

	public function nonce_fix($uid = 0, $action = '')
	{
		$nonce_actions = array('pgfy-ajax-modal', 'pgfy-add-to-cart');
		if (in_array($action, $nonce_actions)) {
			return 0;
		}

		return $uid;
	}

	/**
	 * Require php version
	 * 
	 * @since      1.0.0
	 * @return string min require php version
	 */
	public function get_min_php()
	{
		$file_info = get_file_data(self::$__FILE__, array(
			'min_php' => 'Requires PHP',
		));
		return $file_info['min_php'];
	}

	/**
	 * Require WooCommerce Version
	 * 
	 * @since      1.0.0
	 * @return string min require WooCommerce version
	 */
	public function get_min_wc()
	{
		$file_info = get_file_data(self::$__FILE__, array(
			'min_wc' => 'WC requires at least',
		));
		return $file_info['min_wc'];
	}

	/**
	 * Require WordPress Version
	 * 
	 * @since      1.0.0
	 * @return string min require WordPress version
	 */
	public function get_min_wp()
	{
		$file_info = get_file_data(self::$__FILE__, array(
			'min_wc' => 'Requires at least',
		));
		return $file_info['min_wc'];
	}

	/**
	 * Get settings id
	 * 
	 * @since      1.0.0
	 * @return string Settings id
	 */
	public function get_settings_id()
	{
		return self::$setting_id;
	}

	/**
	 * Get settings
	 * 
	 * @since      1.0.0
	 *
	 * @return  array Value of the plugins settings.
	 */
	public function get_settings()
	{
		return  get_option($this->get_settings_id(), true);
	}

	/**
	 * Check Pro version of the plugin already installed or not 
	 * 
	 * @since      1.0.0
	 */
	public function is_pro_activated()
	{
		return class_exists('Pgfy_Woo_Product_Recommend_Pro');
	}

	/**
	 * Get prodcut recomemnd meta
	 * @since      1.0.0
	 * @return object post meta of _pgfy_pr_data
	 */
	public static function get_pr_data($id)
	{
		if (!isset(self::$pr_meta[$id])) {
			self::$pr_meta[$id] = get_post_meta($id, '_pgfy_pr_data', true);
		}

		return self::$pr_meta[$id];
	}

	/**
	 * Get class instance.
	 * @since      1.0.0
	 * @return instance of base class.
	 */
	public static function init($__FILE__)
	{
		if (is_null(self::$instance))
			self::$instance = new self($__FILE__);

		return self::$instance;
	}
}
