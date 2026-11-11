<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function frey_register_floor_plan_cpt(): void {
	$labels = array(
		'name'               => _x( 'Floor Plans', 'Post Type General Name', 'frey-theme' ),
		'singular_name'      => _x( 'Floor Plan', 'Post Type Singular Name', 'frey-theme' ),
		'menu_name'          => __( 'Floor Plans', 'frey-theme' ),
		'add_new_item'       => __( 'Add New Floor Plan', 'frey-theme' ),
		'edit_item'          => __( 'Edit Floor Plan', 'frey-theme' ),
		'view_item'          => __( 'View Floor Plan', 'frey-theme' ),
		'all_items'          => __( 'All Floor Plans', 'frey-theme' ),
		'search_items'       => __( 'Search Floor Plans', 'frey-theme' ),
		'not_found'          => __( 'No floor plans found.', 'frey-theme' ),
		'not_found_in_trash' => __( 'No floor plans found in Trash.', 'frey-theme' ),
	);

	$args = array(
		'label'               => __( 'Floor Plan', 'frey-theme' ),
		'labels'              => $labels,
		'supports'            => array( 'title', 'thumbnail', 'custom-fields' ),
		'public'              => true,
		'show_ui'             => true,
		'show_in_menu'        => true,
		'menu_position'       => 20,
		'menu_icon'           => 'dashicons-building',
		'show_in_rest'        => true,
		'has_archive'         => false,
		'exclude_from_search' => false,
		'publicly_queryable'  => true,
		'capability_type'     => 'post',
	);

	register_post_type( 'floor_plan', $args );
}
add_action( 'init', 'frey_register_floor_plan_cpt' );
