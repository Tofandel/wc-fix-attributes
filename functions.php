<?php
/**
 * Plugin name: WC Fix Attributes
 * Version: 1.0
 * Author: Tofandel
 * Description: Fixes the slowliness of a select2 in woocommerce admin when having huge numbers of product variation terms
 */

/**
 * Filter to prevent displaying all of the attribute terms and only display the ones selected in the product
 */
add_filter( 'woocommerce_product_attribute_terms', function ( $args ) {
	global $post;

	if ( $post ) {
		$product = new WC_Product($post);

		$includes = [];
		$size = 0;

		foreach ($product->get_attributes() as $attribute) {
			/**
			 * @var WC_Product_Attribute $attribute
			 */
			$opts = $attribute->get_options();
			$includes = array_merge($includes, $opts);
			$size = max($size, sizeof($includes));

		}
		$args['include'] = $includes;

		$args['number'] = $size; //Will output only the selected terms
	}
	return $args;
} );

/**
 * Hack so we can get the term taxonomy (because WC forgot to print it somewhere)
 */
add_action( 'woocommerce_product_option_terms', function ( $attribute_taxonomy, $i, $attribute ) {
	echo '<span class="attribute_taxonomy_getter" data-taxonomy="' . esc_attr( $attribute->get_taxonomy() ) . '"></span>';
}, 10, 3 );

/**
 * Enqueue script
 */
add_action( 'admin_enqueue_scripts', function () {
	wp_enqueue_script( 'wc-fix-attributes', plugins_url( 'script.js', __FILE__ ) );

	wp_localize_script( 'wc-fix-attributes', 'WCFixAttributes', [
		'ajaxurl' => admin_url( 'admin-ajax.php' ),
		'nonce' => wp_create_nonce( 'wc_fix_search_terms' ),
	] );
} );

/**
 * Ajax search
 */
add_action( 'wp_ajax_wc_fix_search_terms', function () {
	// Permissions check.
	check_ajax_referer( 'wc_fix_search_terms' );

	if ( ! current_user_can( 'manage_product_terms' ) ) {
		wp_send_json_error( __( 'You do not have permission to read product attribute terms', 'woocommerce' ) );
	}


	if ( ! empty( $_REQUEST['taxonomy'] ) ) {
		$terms = get_terms( [ 'taxonomy' => $_REQUEST['taxonomy'], 'number' => 100, 'name__like' => $_REQUEST['term'] ] );

		$terms = array_map( function ( $term ) {
			return [ 'text' => $term->name, 'slug' => $term->slug, 'id' => $term->term_id ];
		}, $terms );

		wp_send_json( [ 'results' => $terms, 'success' => true ] );
	} else {
		wp_send_json_error();
	}
} );
