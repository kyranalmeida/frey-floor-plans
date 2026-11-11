<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$today_iso = wp_date( 'Y-m-d' );

$bedroom_options = array(
	0 => __( 'Studio', 'frey-theme' ),
	1 => __( '1 Bed', 'frey-theme' ),
	2 => __( '2 Bed', 'frey-theme' ),
	3 => __( '3 Bed', 'frey-theme' ),
);
?>

<section
	class="fp-module"
	aria-labelledby="fp-module-heading"
	data-fp-endpoint="<?php echo esc_url( rest_url( 'theme/v1/floor-plans' ) ); ?>"
>
	<header class="fp-header">
		<p class="fp-eyebrow"><?php echo esc_html__( 'Residences & Availability', 'frey-theme' ); ?></p>
		<h1 id="fp-module-heading" class="fp-title">
			<?php echo esc_html__( 'Explore Floor Plans', 'frey-theme' ); ?>
		</h1>
	</header>

	<form id="fp-filter-form" class="fp-controls" role="search" aria-label="<?php echo esc_attr__( 'Filter floor plans', 'frey-theme' ); ?>" novalidate>
		<fieldset class="fp-control-group fp-control-group--beds">
			<legend class="fp-label"><?php echo esc_html__( 'Bedrooms', 'frey-theme' ); ?></legend>
			<div class="fp-pill-group" role="group" aria-label="<?php echo esc_attr__( 'Select one or more bedroom sizes', 'frey-theme' ); ?>">
				<?php foreach ( $bedroom_options as $bed_value => $bed_label ) : ?>
					<button
						type="button"
						class="fp-pill"
						data-bed-value="<?php echo esc_attr( (string) $bed_value ); ?>"
						aria-pressed="false"
					>
						<?php echo esc_html( $bed_label ); ?>
					</button>
				<?php endforeach; ?>
			</div>
		</fieldset>

		<div class="fp-control-group">
			<label for="fp-max-price" class="fp-label">
				<?php echo esc_html__( 'Max Monthly Rent ($)', 'frey-theme' ); ?>
			</label>
			<div class="fp-input-wrapper">
				<span class="fp-input-prefix" aria-hidden="true">$</span>
				<input
					type="number"
					id="fp-max-price"
					name="max_price"
					class="fp-input fp-input--price"
					placeholder="<?php echo esc_attr__( 'e.g. 3200', 'frey-theme' ); ?>"
					min="0"
					step="50"
					inputmode="numeric"
				/>
			</div>
		</div>

		<div class="fp-control-group">
			<label for="fp-move-in-date" class="fp-label">
				<?php echo esc_html__( 'Move-In By', 'frey-theme' ); ?>
			</label>
			<input
				type="date"
				id="fp-move-in-date"
				name="move_in_date"
				class="fp-input"
				min="<?php echo esc_attr( $today_iso ); ?>"
			/>
		</div>

		<div class="fp-control-group fp-control-group--actions">
			<button type="button" id="fp-clear-inline" class="fp-btn-ghost" hidden>
				<?php echo esc_html__( 'Clear All', 'frey-theme' ); ?>
			</button>
		</div>
	</form>

	<div class="fp-status-bar">
		<div
			id="fp-live-region"
			class="fp-live-region"
			role="status"
			aria-live="polite"
			aria-atomic="true"
		>
			<?php echo esc_html__( 'Loading available floor plans...', 'frey-theme' ); ?>
		</div>
	</div>

	<div id="fp-loading-state" class="fp-loading-grid" aria-hidden="true">
		<?php for ( $i = 0; $i < 6; $i++ ) : ?>
			<div class="fp-skeleton-card"></div>
		<?php endfor; ?>
	</div>

	<div
		id="fp-results-grid"
		class="fp-grid"
		aria-busy="true"
	></div>

	<div id="fp-empty-state" class="fp-empty-state" hidden>
		<h2 class="fp-empty-title">
			<?php echo esc_html__( 'No matching floor plans found', 'frey-theme' ); ?>
		</h2>
		<p class="fp-empty-copy">
			<?php echo esc_html__( 'No residences match your exact combination of bedrooms, budget, and move-in date.', 'frey-theme' ); ?>
		</p>
		<button type="button" id="fp-reset-btn" class="fp-btn-primary">
			<?php echo esc_html__( 'Reset Filters', 'frey-theme' ); ?>
		</button>
	</div>
</section>
