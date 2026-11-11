<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function frey_register_acf_floor_plan_fields(): void {
	if ( ! function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}

	acf_add_local_field_group(
		array(
			'key'      => 'group_floor_plan_specifications',
			'title'    => __( 'Floor Plan Specifications', 'frey-theme' ),
			'fields'   => array(
				array(
					'key'           => 'field_fp_bedrooms',
					'label'         => __( 'Bedrooms (0 for Studio)', 'frey-theme' ),
					'name'          => 'bedrooms',
					'type'          => 'number',
					'instructions'  => __( 'Enter 0 for Studio, 1 for 1 Bedroom, etc.', 'frey-theme' ),
					'required'      => 1,
					'default_value' => 0,
					'min'           => 0,
					'max'           => 5,
					'step'          => 1,
				),
				array(
					'key'           => 'field_fp_starting_price',
					'label'         => __( 'Starting Price ($/mo)', 'frey-theme' ),
					'name'          => 'starting_price',
					'type'          => 'number',
					'required'      => 1,
					'min'           => 0,
					'step'          => 10,
					'prepend'       => '$',
				),
				array(
					'key'            => 'field_fp_available_date',
					'label'          => __( 'Available Date', 'frey-theme' ),
					'name'           => 'available_date',
					'type'           => 'date_picker',
					'instructions'   => __( 'Stored in YYYY-MM-DD format for SQL DATE comparison.', 'frey-theme' ),
					'required'       => 1,
					'display_format' => 'F j, Y',
					'return_format'  => 'Y-m-d',
					'first_day'      => 0,
				),
				array(
					'key'           => 'field_fp_bathrooms',
					'label'         => __( 'Bathrooms', 'frey-theme' ),
					'name'          => 'bathrooms',
					'type'          => 'number',
					'default_value' => 1,
					'min'           => 1,
					'max'           => 5,
					'step'          => 0.5,
				),
				array(
					'key'     => 'field_fp_sqft',
					'label'   => __( 'Square Footage', 'frey-theme' ),
					'name'    => 'sqft',
					'type'    => 'number',
					'min'     => 200,
					'step'    => 1,
					'append'  => 'sq ft',
				),
			),
			'location' => array(
				array(
					array(
						'param'    => 'post_type',
						'operator' => '==',
						'value'    => 'floor_plan',
					),
				),
			),
			'style'    => 'default',
			'active'   => true,
		)
	);
}
add_action( 'acf/init', 'frey_register_acf_floor_plan_fields' );

function frey_normalize_acf_date_storage( $value, $post_id, array $field ) {
	if ( empty( $value ) || ! is_string( $value ) ) {
		return $value;
	}

	if ( preg_match( '/^(\d{4})(\d{2})(\d{2})$/', $value, $matches ) ) {
		return sprintf( '%s-%s-%s', $matches[1], $matches[2], $matches[3] );
	}

	return $value;
}
add_filter( 'acf/update_value/name=available_date', 'frey_normalize_acf_date_storage', 10, 3 );
