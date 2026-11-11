<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'FREY_THEME_VERSION', '1.0.0' );
define( 'FREY_THEME_DIR', get_template_directory() );
define( 'FREY_THEME_URI', get_template_directory_uri() );

require_once FREY_THEME_DIR . '/inc/cpt-floor-plan.php';
require_once FREY_THEME_DIR . '/inc/acf-fields.php';
require_once FREY_THEME_DIR . '/inc/rest-api.php';
require_once FREY_THEME_DIR . '/inc/seed-data.php';

function frey_enqueue_floor_plan_assets(): void {
	wp_enqueue_style(
		'frey-floor-plan-filter',
		FREY_THEME_URI . '/assets/css/floor-plan-filter.css',
		array(),
		FREY_THEME_VERSION
	);

	wp_enqueue_script(
		'frey-floor-plan-filter',
		FREY_THEME_URI . '/assets/js/floor-plan-filter.js',
		array(),
		FREY_THEME_VERSION,
		array(
			'strategy'  => 'defer',
			'in_footer' => true,
		)
	);

	wp_localize_script(
		'frey-floor-plan-filter',
		'FreyFloorPlanConfig',
		array(
			'restUrl' => esc_url_raw( rest_url( 'theme/v1/floor-plans' ) ),
		)
	);
}
add_action( 'wp_enqueue_scripts', 'frey_enqueue_floor_plan_assets' );

function frey_theme_setup(): void {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption', 'style', 'script' ) );
}
add_action( 'after_setup_theme', 'frey_theme_setup' );
