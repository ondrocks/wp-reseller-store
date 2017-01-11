<?php

namespace Reseller_Store;

use stdClass;

if ( ! defined( 'ABSPATH' ) ) {

	exit;

}

final class Product {

	/**
	 * Product object.
	 *
	 * @since NEXT
	 *
	 * @var stdClass
	 */
	public $product;

	/**
	 * Array of required properties and validation callbacks.
	 *
	 * @since NEXT
	 *
	 * @var array
	 */
	private $properties = [
		'id'         => 'strlen',
		'categories' => 'is_array',
		'image'      => 'strlen',
		'term'       => 'strlen',
		'listPrice'  => 'strlen',
		'title'      => 'strlen',
		'content'    => 'strlen',
	];

	/**
	 * Class constructor.
	 *
	 * @since NEXT
	 *
	 * @param stdClass $product
	 */
	public function __construct( $product ) {

		$this->product = json_decode( json_encode( $product ) );

	}

	/**
	 * Check if the product object is valid.
	 *
	 * @since NEXT
	 *
	 * @return bool  Returns `true` if the product object is valid, otherwise `false`.
	 */
	public function is_valid() {

		if ( ! is_a( $this->product, 'stdClass' ) ) {

			return false;

		}

		foreach ( $this->properties as $property => $validator ) {

			if (
				// The product must have the property
				property_exists( $this->product, $property )
				&&
				// The property validator must be callable
				is_callable( $validator )
				&&
				// The property value must return truthy when ran through the validator
				$validator( $this->product->{$property} )
			) {

				return true;

			}

		}

		return false;

	}

	/**
	 * Check if a product has already been imported.
	 *
	 * @global wpdb $wpdb
	 * @since  NEXT
	 *
	 * @return int|false  Returns the post ID if it exists, otherwise `false`.
	 */
	public function exists() {

		global $wpdb;

		$post_id = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT `ID` FROM `{$wpdb->posts}` as p LEFT JOIN `{$wpdb->postmeta}` as pm ON ( p.`ID` = pm.`post_id` ) WHERE p.`post_type` = %s AND pm.`meta_key` = %s AND pm.`meta_value` = %s;",
				Post_Type::SLUG,
				Plugin::prefix( 'id' ),
				sanitize_title( $this->product->id ) // Product IDs are sanitized on import
			)
		);

		return ( $post_id > 0 ) ? $post_id : false;

	}

	/**
	 * Check if an product image has already been imported.
	 *
	 * @global wpdb $wpdb
	 * @since  NEXT
	 *
	 * @param  string $url
	 *
	 * @return int|false  Returns the attachment ID if it exists, otherwise `false`.
	 */
	public function image_exists() {

		global $wpdb;

		$attachment_id = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT `ID` FROM `{$wpdb->posts}` as p LEFT JOIN `{$wpdb->postmeta}` as pm ON ( p.`ID` = pm.`post_id` ) WHERE p.`post_type` = 'attachment' AND pm.`meta_key` = %s AND pm.`meta_value` = %s;",
				Plugin::prefix( 'image' ),
				esc_url_raw( $this->product->image ) // Image URLs are sanitized on import
			)
		);

		return ( $attachment_id > 0 ) ? $attachment_id : false;

	}

	/**
	 * Import the product.
	 *
	 * @since NEXT
	 *
	 * @param  int $post_id
	 *
	 * @return true|WP_Error  Returns `true` on success, `WP_Error` on failure.
	 */
	public function import( $post_id = 0 ) {

		$import = new Import( $this, $post_id );

		return $import->result();

	}

}