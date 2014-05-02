<div class="wrap">
	<?php screen_icon(); ?>
	<h2><?php _e( 'Custom Social Share Buttons', 'cssb' ); ?></h2>

	<form method="post" action="options.php">
		<?php do_settings_sections( $cssb_options_page ); ?>
		<?php submit_button(); ?>
	</form>
</div>