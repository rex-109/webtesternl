<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Plugin compatibility for Smush
 *
 * @since 3.17.1
 *
 * @link https://wordpress.org/plugins/wp-smushit/
 */
class ET_Builder_Plugin_Compat_Smush extends ET_Builder_Plugin_Compat_Base {
	/**
	 * Constructor
	 */
	public function __construct() {
		$this->plugin_id = 'wp-smushit/wp-smush.php';
		$this->init_hooks();
	}

	/**
	 * Hook methods to WordPress
	 *
	 * Latest plugin version: 1601
	 *
	 * @return void
	 */
	public function init_hooks() {
		// Bail if there's no version found
		if ( ! $this->get_plugin_version() ) {
			return;
		}

		$enabled = array(
			// phpcs:disable WordPress.Security.NonceVerification.NoNonceVerification
			'vb'  => et_()->array_get( $_GET, 'et_fb' ),
			'bfb' => et_()->array_get( $_GET, 'et_bfb' ),
			'tb'  => et_()->array_get( $_GET, 'et_tb' ),
			// phpcs:enable
		);

		add_filter( 'wp_smush_should_skip_parse', array( $this, 'maybe_skip_parse' ), 11 );

		if ( $enabled['vb'] || $enabled['bfb'] || $enabled['tb'] ) {
			// Plugin's `enqueue` function will cause a PHP notice unless
			// early exit is forced using the following custom filter
			add_filter( 'wp_smush_enqueue', '__return_false' );
			remove_action( 'wp_enqueue_scripts', array( WP_Smush::get_instance()->core()->mod->lazy, 'enqueue_assets' ) );
		}
	}

	/**
	 * Disable Smush page parsing in VB.
	 *
	 * @param boolean $skip
	 *
	 * @return boolean
	 */
	public function maybe_skip_parse( $skip ) {
		if ( et_core_is_fb_enabled() ) {
			return true;
		}

		return $skip;
	}
}

new ET_Builder_Plugin_Compat_Smush();
