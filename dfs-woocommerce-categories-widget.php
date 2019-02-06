<?php

/**
 * Plugin Name: DFS WooCommerce Categories Widget
 * Description: Custom Woocommerce Categories Widget
 * Version: 1.0
 * Author: Denis Fedorov
 * Author URI: https://www.upwork.com/freelancers/~01ad0e773956a34ffd
 * License: GNU General Public License v3.0
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Text Domain: dfs-wc-cat-widget
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
	die;
}

// WooCommerce must be enabled
if (!class_exists('WooCommerce')) : 

	// unless we throw a notice
	function dfs_wc_cat_widget__admin_notice_woocommerce_required() {
		?>
		<div class="notice notice-error">
			<p><?php _e( 'DFS WooCommerce Categories Widget: WooCommerce is required to be installed and active.', 'dfs-wc-cat-widget'); ?></p>
		</div>
		<?php
	}
	add_action('admin_notices', 'dfs_wc_cat_widget__admin_notice_woocommerce_required');

else:

if (!class_exists('WC_Widget')) {
	require_once ABSPATH . 'wp-content/plugins/woocommerce/includes/abstracts/abstract-wc-widget.php';
}

class DFS_WC_Widget_Product_Categories extends WC_Widget {

	/**
	 * Category ancestors.
	 *
	 * @var array
	 */
	public $parent_cat;

	/**
	 * Current Category.
	 *
	 * @var object
	 */
	public $current_cat;

	/**
	 * Constructor
	 *
	 * @return void
	 */
	public function __construct() {
		$this->widget_cssclass    = 'woocommerce widget_product_categories';
		$this->widget_description = __( 'A custom list of product categories.', 'dfs-wc-cat-widget' );
		$this->widget_id          = 'dfs_woocommerce_product_categories';
		$this->widget_name        = __( 'DF Product Categories', 'dfs-wc-cat-widget' );
		
		$this->settings           = array(
			'title'	=> array(
				'type'	=> 'text',
				'std'	=> __( 'Product categories', 'dfs-wc-cat-widget' ),
				'label'	=> __( 'Title', 'dfs-wc-cat-widget' ),
			),
			'ulclass' => array(
				'type'	=> 'text',
				'std'	=> '',
				'label'	=> __( 'Ul classname', 'dfs-wc-cat-widget' ),
			),
			'orderby' => array(
				'type'	=> 'select',
				'std'	=> 'name',
				'label' => __( 'Order by', 'dfs-wc-cat-widget' ),
				'options' => array(
					'order'	=> __( 'Category order', 'dfs-wc-cat-widget' ),
					'name'	=> __( 'Name', 'dfs-wc-cat-widget' ),
				),
			),
			'count' => array(
				'type'  => 'checkbox',
				'std'   => 0,
				'label' => __( 'Show product counts', 'dfs-wc-cat-widget' ),
			),
			'hide_empty'         => array(
				'type'  => 'checkbox',
				'std'   => 0,
				'label' => __( 'Hide empty categories', 'dfs-wc-cat-widget' ),
			),
			'excludecurr'              => array(
				'type'  => 'checkbox',
				'std'   => 1,
				'label' => __( 'Exclude current category', 'dfs-wc-cat-widget' ),
			),
			'excludedefault'              => array(
				'type'  => 'checkbox',
				'std'   => 1,
				'label' => __( 'Exclude default category', 'dfs-wc-cat-widget' ),
			),
			'excludecategories'              => array(
				'type'  => 'textarea',
				'label' => __( 'Exclude categories by slug', 'dfs-wc-cat-widget' ),
				'desc'  => __( 'Multiple categories should be separated by comma', 'dfs-wc-cat-widget' )
			)
		);
		parent::__construct();
	}

	/**
	 * Outputs the HTML for this widget.
	 *
	 * @param array  An array of standard parameters for widgets in this theme
	 * @param array  An array of settings for this widget instance
	 * @return void Echoes it's output
	 */
	public function widget( $args, $instance ) {
		global $wp_query, $post;

		// Show widget only in product category
		if ( !is_tax( 'product_cat' ) ) {
			return;
		}

		$count              = isset( $instance['count'] ) ? $instance['count'] : $this->settings['count']['std'];
		$orderby            = isset( $instance['orderby'] ) ? $instance['orderby'] : $this->settings['orderby']['std'];
		$exclude_current    = isset( $instance['excludecurr'] ) ? $instance['excludecurr'] : $this->settings['excludecurr']['std'];
		$exclude_default    = isset( $instance['excludedefault'] ) ? $instance['excludedefault'] : $this->settings['excludedefault']['std'];
		$hide_empty         = isset( $instance['hide_empty'] ) ? $instance['hide_empty'] : $this->settings['hide_empty']['std'];
		$ul_class           = isset( $instance['ulclass'] ) ? $instance['ulclass'] : $this->settings['ulclass']['std'];
		$exclude_categories = isset( $instance['excludecategories'] ) ? $instance['excludecategories'] : $this->settings['excludecategories']['std'];

		
		if ($exclude_categories) {
			$exclude_categories = array_map(function($el){
				$cat = get_term_by('slug', trim($el), 'product_cat');
				return $cat->term_id;
			}, explode(',', $exclude_categories));
		}
		$default_product_cat = array();
		if ($exclude_default) {
			$default_product_cat = array((int)get_option('default_product_cat'));
			//print_r($default_product_cat);
		}
		$exclude_categories = array_merge($exclude_categories, $default_product_cat);


		$list_args = array(
			'show_option_all'    => '',
			'show_option_none'   => __('No categories'),
			'orderby'            => $orderby, // Optional
			'order'              => 'ASC',
			'style'              => 'list',
			'show_count'         => $count, // Optional
			'hide_empty'         => $hide_empty, // Optional
			'use_desc_for_title' => 1,
			'child_of'           => 0,
			'feed'               => '',
			'title_li'           => '',
			'feed_type'          => '',
			'feed_image'         => '',
			'exclude'            => (!empty($exclude_categories) ? $exclude_categories : ''),
			'exclude_tree'       => '',
			'include'            => '',
			'hierarchical'       => true,
			'number'             => NULL,
			'echo'               => 1,
			'depth'              => 1,
			'current_category'   => 0,
			'pad_counts'         => 0,
			'taxonomy'           => 'product_cat',
			'walker'             => 'WC_Product_Cat_List_Walker',
			'hide_title_if_empty'=> false,
			'separator'          => '<br />',
		);
		$_args = array();


		// if we are in product category
		// echo 'we are in product category';	
		$this->current_cat = $wp_query->queried_object;
		$this->parent_cat  = $this->current_cat->parent;
		
		// echo '<br>current cat id: ' . $this->current_cat->term_id;
		// echo '<br>parent cat id: ' . $this->parent_cat;

		$children = get_term_children($this->current_cat->term_id, 'product_cat');
		if ($children) {
			// echo '<br>has children ('.count($children).')';

			$_args = array(
				'child_of' => $this->current_cat->term_id
			);
			
		} elseif ($this->parent_cat) {
			// echo '<br>has no children but has parent';
			
			$parent_cat_id = $this->parent_cat;
			$current_cat_id = $this->current_cat->term_id;
			$this_level = true;
			do {
				// try to find siblings
				$parent_cat_obj = get_term_by( 'id', $parent_cat_id, 'product_cat');
				
				$siblings = get_term_children($parent_cat_id, 'product_cat');

				if( $this_level ) {
					// excluding itself
					$exclude = $current_cat_id;
				}
				
				$current_cat_id = $parent_cat_id;
				$this_level = false;
			} while (!$siblings && ($parent_cat_id = $parent_cat_obj->parent));
			
			if($siblings) {
				if($exclude && $exclude_current)
					unset($siblings[array_search($exclude, $siblings)]);
				$_args = array(
					'include' => implode( ',', $siblings )
				);
			};

		} else {
			// echo '<br>has neihter children nor parent';
			if($exclude_categories) {
				$exclude_categories[] = $this->current_cat->term_id;
				$_args = array(
					'exclude' => $exclude_categories,
				);
			} else {
				$_args = array(
					'exclude' => array( $this->current_cat->term_id ),
				);
			}
		}

	
		if( !empty( $_args ) ) {
			$list_args = array_merge( $list_args, $_args );
		}
		//print_r($list_args);


		// output
		$this->widget_start( $args, $instance );
		
		if( !empty( $ul_class ) ) {
			echo "<ul class=\"product-categories df-product-categories {$ul_class}\">";
		} else {
			echo '<ul class="product-categories df-product-categories">';
		}
			wp_list_categories( $list_args );
		echo '</ul>';

		$this->widget_end( $args );
	}
}

function dfs_wc_cat_widget__register() {
	register_widget( 'DFS_WC_Widget_Product_Categories' );
}
add_action( 'widgets_init', 'dfs_wc_cat_widget__register' );

endif;
