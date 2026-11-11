<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function frey_register_floor_plan_rest_routes(): void {
	register_rest_route(
		'theme/v1',
		'/floor-plans',
		array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => 'frey_get_filtered_floor_plans',
			'permission_callback' => '__return_true',
			'args'                => array(
				'bedrooms'     => array(
					'description'       => __( 'Comma-separated or array of bedroom counts (0 = Studio).', 'frey-theme' ),
					'required'          => false,
					'sanitize_callback' => 'frey_sanitize_bedrooms_param',
					'validate_callback' => 'frey_validate_bedrooms_param',
				),
				'max_price'    => array(
					'description'       => __( 'Maximum starting monthly rent.', 'frey-theme' ),
					'required'          => false,
					'sanitize_callback' => 'frey_sanitize_max_price_param',
					'validate_callback' => 'frey_validate_max_price_param',
				),
				'move_in_date' => array(
					'description'       => __( 'Move-in date in YYYY-MM-DD format.', 'frey-theme' ),
					'required'          => false,
					'sanitize_callback' => 'sanitize_text_field',
					'validate_callback' => 'frey_validate_move_in_date_param',
				),
			),
		)
	);
}
add_action( 'rest_api_init', 'frey_register_floor_plan_rest_routes' );

function frey_sanitize_bedrooms_param( $value ): array {
	if ( null === $value || '' === $value ) {
		return array();
	}

	$raw_items = is_array( $value ) ? $value : explode( ',', (string) $value );
	$sanitized = array();

	foreach ( $raw_items as $item ) {
		$trimmed = trim( (string) $item );
		if ( '' !== $trimmed && is_numeric( $trimmed ) ) {
			$sanitized[] = absint( $trimmed );
		}
	}

	return array_values( array_unique( $sanitized ) );
}

function frey_validate_bedrooms_param( $value ) {
	if ( null === $value || '' === $value ) {
		return true;
	}

	$raw_items = is_array( $value ) ? $value : explode( ',', (string) $value );

	foreach ( $raw_items as $item ) {
		$trimmed = trim( (string) $item );
		if ( '' === $trimmed ) {
			continue;
		}
		if ( ! ctype_digit( $trimmed ) ) {
			return new WP_Error(
				'rest_invalid_param',
				__( 'Bedroom values must be non-negative integers (0 for Studio).', 'frey-theme' ),
				array( 'status' => 400 )
			);
		}
	}

	return true;
}

function frey_sanitize_max_price_param( $value ): ?int {
	if ( null === $value || '' === trim( (string) $value ) ) {
		return null;
	}

	return absint( $value );
}

function frey_validate_max_price_param( $value ) {
	if ( null === $value || '' === trim( (string) $value ) ) {
		return true;
	}

	if ( ! is_numeric( $value ) || (int) $value < 0 ) {
		return new WP_Error(
			'rest_invalid_param',
			__( 'max_price must be a positive number.', 'frey-theme' ),
			array( 'status' => 400 )
		);
	}

	return true;
}

function frey_validate_move_in_date_param( $value ) {
	if ( null === $value || '' === trim( (string) $value ) ) {
		return true;
	}

	$date_str = trim( (string) $value );

	if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date_str ) ) {
		return new WP_Error(
			'rest_invalid_date',
			__( 'move_in_date must be formatted as YYYY-MM-DD.', 'frey-theme' ),
			array( 'status' => 400 )
		);
	}

	$dt = DateTime::createFromFormat( 'Y-m-d', $date_str );
	if ( ! $dt || $dt->format( 'Y-m-d' ) !== $date_str ) {
		return new WP_Error(
			'rest_invalid_date',
			__( 'move_in_date is not a valid calendar date.', 'frey-theme' ),
			array( 'status' => 400 )
		);
	}

	return true;
}

function frey_get_filtered_floor_plans( WP_REST_Request $request ): WP_REST_Response {
	$bedrooms     = $request->get_param( 'bedrooms' );
	$max_price    = $request->get_param( 'max_price' );
	$move_in_date = $request->get_param( 'move_in_date' );

	$meta_query = array(
		'relation' => 'AND',
	);

	if ( is_array( $bedrooms ) && count( $bedrooms ) > 0 ) {
		$meta_query[] = array(
			'key'     => 'bedrooms',
			'value'   => $bedrooms,
			'compare' => 'IN',
			'type'    => 'NUMERIC',
		);
	}

	if ( isset( $max_price ) && null !== $max_price && $max_price > 0 ) {
		$meta_query[] = array(
			'key'     => 'starting_price',
			'value'   => $max_price,
			'compare' => '<=',
			'type'    => 'NUMERIC',
		);
	}

	if ( isset( $move_in_date ) && '' !== $move_in_date ) {
		$meta_query[] = array(
			'key'     => 'available_date',
			'value'   => $move_in_date,
			'compare' => '<=',
			'type'    => 'DATE',
		);
	}

	$query_args = array(
		'post_type'              => 'floor_plan',
		'post_status'            => 'publish',
		'posts_per_page'         => 50,
		'meta_key'               => 'starting_price',
		'orderby'                => 'meta_value_num',
		'order'                  => 'ASC',
		'no_found_rows'          => false,
		'update_post_term_cache' => false,
	);

	if ( count( $meta_query ) > 1 ) {
		$query_args['meta_query'] = $meta_query;
	}

	$query = new WP_Query( $query_args );
	$items = array();

	if ( $query->have_posts() ) {
		foreach ( $query->posts as $post ) {
			$post_id = (int) $post->ID;

			$raw_beds  = get_post_meta( $post_id, 'bedrooms', true );
			$raw_price = get_post_meta( $post_id, 'starting_price', true );
			$raw_date  = (string) get_post_meta( $post_id, 'available_date', true );
			$raw_baths = get_post_meta( $post_id, 'bathrooms', true );
			$raw_sqft  = get_post_meta( $post_id, 'sqft', true );

			$beds_int  = ( '' !== $raw_beds && null !== $raw_beds ) ? absint( $raw_beds ) : 0;
			$price_int = absint( $raw_price );

			$formatted_date = $raw_date;
			$dt             = DateTime::createFromFormat( 'Y-m-d', $raw_date );
			if ( $dt && $dt->format( 'Y-m-d' ) === $raw_date ) {
				$formatted_date = ( $raw_date <= wp_date( 'Y-m-d' ) )
					? __( 'Available Now', 'frey-theme' )
					: $dt->format( 'M j, Y' );
			}

			$items[] = array(
				'id'                  => $post_id,
				'title'               => esc_html( get_the_title( $post_id ) ),
				'bedrooms'            => $beds_int,
				'bedroom_label'       => 0 === $beds_int
					? __( 'Studio', 'frey-theme' )
					: sprintf(
						_n( '%d Bed', '%d Beds', $beds_int, 'frey-theme' ),
						$beds_int
					),
				'bathrooms'           => '' !== $raw_baths ? (float) $raw_baths : 1.0,
				'sqft'                => '' !== $raw_sqft ? absint( $raw_sqft ) : 650,
				'starting_price'      => $price_int,
				'formatted_price'     => '$' . number_format_i18n( $price_int ),
				'available_date'      => esc_attr( $raw_date ),
				'formatted_available' => esc_html( $formatted_date ),
				'permalink'           => esc_url( (string) get_permalink( $post_id ) ),
				'thumbnail'           => esc_url( (string) get_the_post_thumbnail_url( $post_id, 'medium_large' ) ),
			);
		}
	}

	return new WP_REST_Response(
		array(
			'total'   => (int) $query->found_posts,
			'filters' => array(
				'bedrooms'     => is_array( $bedrooms ) ? $bedrooms : array(),
				'max_price'    => $max_price,
				'move_in_date' => $move_in_date ?? '',
			),
			'items'   => $items,
		),
		200
	);
}
