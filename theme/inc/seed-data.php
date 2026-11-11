<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function frey_seed_sample_floor_plans(): void {
	$existing = get_posts(
		array(
			'post_type'      => 'floor_plan',
			'posts_per_page' => 1,
			'post_status'    => 'any',
			'fields'         => 'ids',
		)
	);

	if ( ! empty( $existing ) ) {
		return;
	}

	$units = array(
		array(
			'title'          => 'The Burnham Studio — S1',
			'bedrooms'       => 0,
			'bathrooms'      => 1,
			'sqft'           => 515,
			'starting_price' => 1950,
			'available_date' => '2026-11-15',
		),
		array(
			'title'          => 'The Sullivan Alcove Studio — S2',
			'bedrooms'       => 0,
			'bathrooms'      => 1,
			'sqft'           => 590,
			'starting_price' => 2180,
			'available_date' => '2026-12-01',
		),
		array(
			'title'          => 'The Fulton 1BD — A1',
			'bedrooms'       => 1,
			'bathrooms'      => 1,
			'sqft'           => 740,
			'starting_price' => 2550,
			'available_date' => '2026-11-20',
		),
		array(
			'title'          => 'The Kinzie 1BD + Den — A2',
			'bedrooms'       => 1,
			'bathrooms'      => 1.5,
			'sqft'           => 865,
			'starting_price' => 2890,
			'available_date' => '2026-12-15',
		),
		array(
			'title'          => 'The Dearborn Corner 2BD — B1',
			'bedrooms'       => 2,
			'bathrooms'      => 2,
			'sqft'           => 1120,
			'starting_price' => 3450,
			'available_date' => '2026-11-18',
		),
		array(
			'title'          => 'The Wacker Riverfront 2BD — B2',
			'bedrooms'       => 2,
			'bathrooms'      => 2,
			'sqft'           => 1280,
			'starting_price' => 3890,
			'available_date' => '2027-01-10',
		),
		array(
			'title'          => 'The Adler Terrace 3BD — C1',
			'bedrooms'       => 3,
			'bathrooms'      => 2.5,
			'sqft'           => 1640,
			'starting_price' => 4750,
			'available_date' => '2026-12-05',
		),
		array(
			'title'          => 'The Lakeshore Penthouse 3BD — PH1',
			'bedrooms'       => 3,
			'bathrooms'      => 3,
			'sqft'           => 2100,
			'starting_price' => 6200,
			'available_date' => '2027-02-01',
		),
	);

	foreach ( $units as $unit ) {
		$post_id = wp_insert_post(
			array(
				'post_type'   => 'floor_plan',
				'post_title'  => $unit['title'],
				'post_status' => 'publish',
			)
		);

		if ( ! is_wp_error( $post_id ) && $post_id > 0 ) {
			update_post_meta( $post_id, 'bedrooms', $unit['bedrooms'] );
			update_post_meta( $post_id, 'bathrooms', $unit['bathrooms'] );
			update_post_meta( $post_id, 'sqft', $unit['sqft'] );
			update_post_meta( $post_id, 'starting_price', $unit['starting_price'] );
			update_post_meta( $post_id, 'available_date', $unit['available_date'] );
		}
	}
}
add_action( 'after_switch_theme', 'frey_seed_sample_floor_plans' );
add_action( 'init', 'frey_seed_sample_floor_plans', 20 );
