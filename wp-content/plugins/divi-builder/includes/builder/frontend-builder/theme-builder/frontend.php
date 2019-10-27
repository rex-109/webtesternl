<?php
/**
 * Remove the admin bar from the VB when used from the Theme Builder.
 *
 * @since 4.0
 *
 * @return void
 */
function et_theme_builder_frontend_disable_admin_bar() {
	if ( et_builder_tb_enabled() ) {
		add_filter( 'show_admin_bar', '__return_false' );
	}
}
add_filter( 'wp', 'et_theme_builder_frontend_disable_admin_bar' );

/**
 * Add body classes depending on which areas are overridden by TB.
 *
 * @since 4.0
 *
 * @param array $classes
 *
 * @return string[]
 */
function et_theme_builder_frontend_add_body_classes( $classes ) {
	if ( et_builder_bfb_enabled() ) {
		// Do not add any classes in BFB.
		return $classes;
	}

	$layouts = et_theme_builder_get_template_layouts();

	if ( ! empty( $layouts ) ) {
		$classes[] = 'et-tb-has-template';

		if ( $layouts[ ET_THEME_BUILDER_HEADER_LAYOUT_POST_TYPE ]['override'] ) {
			$classes[] = 'et-tb-has-header';

			if ( ! $layouts[ ET_THEME_BUILDER_HEADER_LAYOUT_POST_TYPE ]['enabled'] ) {
				$classes[] = 'et-tb-header-disabled';
			}
		}

		if ( $layouts[ ET_THEME_BUILDER_BODY_LAYOUT_POST_TYPE ]['override'] ) {
			$classes[] = 'et-tb-has-body';

			if ( ! $layouts[ ET_THEME_BUILDER_BODY_LAYOUT_POST_TYPE ]['enabled'] ) {
				$classes[] = 'et-tb-body-disabled';
			}
		}

		if ( $layouts[ ET_THEME_BUILDER_FOOTER_LAYOUT_POST_TYPE ]['override'] ) {
			$classes[] = 'et-tb-has-footer';

			if ( ! $layouts[ ET_THEME_BUILDER_FOOTER_LAYOUT_POST_TYPE ]['enabled'] ) {
				$classes[] = 'et-tb-footer-disabled';
			}
		}
	}

	return $classes;
}
add_filter( 'body_class', 'et_theme_builder_frontend_add_body_classes', 9 );

/**
 * Conditionally override the template being loaded by wordpress based on what the user
 * has created in their Theme Builder.
 * The header and footer are always dealt with as a pair - if the header is replaced the footer is replaced a well.
 *
 * @since 4.0
 *
 * @param string $template
 *
 * @return string
 */
function et_theme_builder_frontend_override_template( $template ) {
	$layouts         = et_theme_builder_get_template_layouts();
	$has_layouts     = ! empty( $layouts );
	$override_header = $has_layouts && $layouts[ ET_THEME_BUILDER_HEADER_LAYOUT_POST_TYPE ]['override'];
	$override_body   = $has_layouts && $layouts[ ET_THEME_BUILDER_BODY_LAYOUT_POST_TYPE ]['override'];
	$override_footer = $has_layouts && $layouts[ ET_THEME_BUILDER_FOOTER_LAYOUT_POST_TYPE ]['override'];

	if ( ( $override_header || $override_body || $override_footer ) && et_core_is_fb_enabled() ) {
		// When cached assets/definitions do not exist, a VB/BFB page will generate them inline.
		// This would normally happen later but not in the case of TB because, due to early
		// `the_content()` calls, `maybe_rebuild_option_template()` would be invoked before
		// saving definitions/assets resulting in them including resolved option templates instead
		// of placeholders.
		$post_type = get_post_type();
		et_fb_get_dynamic_asset( 'helpers', $post_type );
		et_fb_get_dynamic_asset( 'definitions', $post_type );
	}

	if ( $override_header || $override_footer ) {
		add_action( 'get_header', 'et_theme_builder_frontend_override_header' );
		add_action( 'get_footer', 'et_theme_builder_frontend_override_footer' );
	}

	et_theme_builder_frontend_enqueue_styles( $layouts );

	if ( $override_body ) {
		return ET_THEME_BUILDER_DIR . 'frontend-body-template.php';
	}

	return $template;
}
// Priority of 98 so it can be overridden by BFB.
add_filter( 'template_include', 'et_theme_builder_frontend_override_template', 98 );

/**
 * Enqueue any necessary TB layout styles.
 *
 * @since 4.0
 *
 * @param array $layouts
 *
 * @return void
 */
function et_theme_builder_frontend_enqueue_styles( $layouts ) {
	if ( empty( $layouts ) ) {
		return;
	}

	if ( ! is_singular() || et_core_is_fb_enabled() ) {
		// Create styles managers so they can enqueue styles early enough.
		// What styles are created and how they are enqueued:
		// - In FE, singular post view:
		//   -> TB + Post Styles are combined into et-*-tb-{HEADER_ID}-tb-{BODY_ID}-tb-{FOOTER_ID}-{POST_ID}-*.css
		//
		// - In FE, non-singular post view:
		//   -> TB styles are separate with the usual filename: et-*-{LAYOUT_ID}-*.css
		//
		// - In FE, singular post view in VB so post-specific DC works:
		//   -> TB styles are separate with the current post ID prepended: et-*-tb-for-{POST_ID}-{LAYOUT_ID}-*.css

		if ( $layouts[ ET_THEME_BUILDER_HEADER_LAYOUT_POST_TYPE ]['override'] ) {
			ET_Builder_Element::setup_advanced_styles_manager( $layouts[ ET_THEME_BUILDER_HEADER_LAYOUT_POST_TYPE ]['id'] );
		}

		if ( $layouts[ ET_THEME_BUILDER_BODY_LAYOUT_POST_TYPE ]['override'] ) {
			ET_Builder_Element::setup_advanced_styles_manager( $layouts[ ET_THEME_BUILDER_BODY_LAYOUT_POST_TYPE ]['id'] );
		}

		if ( $layouts[ ET_THEME_BUILDER_FOOTER_LAYOUT_POST_TYPE ]['override'] ) {
			ET_Builder_Element::setup_advanced_styles_manager( $layouts[ ET_THEME_BUILDER_FOOTER_LAYOUT_POST_TYPE ]['id'] );
		}
	}
}

/**
 * Render a custom partial overriding the original one.
 *
 * @since 4.0
 *
 * @param string $partial
 * @param string $action
 * @param string $name
 *
 * @return void
 */
function et_theme_builder_frontend_override_partial( $partial, $name, $action = '' ) {
	require_once ET_THEME_BUILDER_DIR . "frontend-{$partial}-template.php";

	/**
	 * Slightly adjusted version of WordPress core code in order to mimic behavior.
	 *
	 * @link https://core.trac.wordpress.org/browser/tags/5.0.3/src/wp-includes/general-template.php#L33
	 */
	$templates = array();
	$name = (string) $name;
	if ( '' !== $name ) {
		$templates[] = "{$partial}-{$name}.php";
	}
	$templates[] = "{$partial}.php";

	// Buffer and discard the original partial forcing a require_once so it doesn't load again later.
	$buffered = ob_start();
	if ( $buffered ) {
		if ( ! empty( $action ) ) {
			// Skip any partial-specific actions so they don't run twice. It may be a good idea to re-add them later.
			remove_all_actions( $action );
		}

		locate_template( $templates, true, true );
		ob_end_clean();
	}
}

/**
 * Override the default header template.
 *
 * @since 4.0
 *
 * @param string $name
 *
 * @return void
 */
function et_theme_builder_frontend_override_header( $name ) {
	et_theme_builder_frontend_override_partial( 'header', $name, 'wp_head' );
}

/**
 * Override the default footer template.
 *
 * @since 4.0
 *
 * @param string $name
 *
 * @return void
 */
function et_theme_builder_frontend_override_footer( $name ) {
	et_theme_builder_frontend_override_partial( 'footer', $name );
}

/**
 * Output to the template builder head.
 *
 * @since 4.0
 *
 * @return void
 */
function et_theme_builder_frontend_add_head() {
	?>
	<meta charset="<?php bloginfo( 'charset' ); ?>" />
	<?php
	/**
	 * Fires in the head, before {@see wp_head()} is called. This action can be used to
	 * insert elements into the beginning of the head before any styles or scripts.
	 *
	 * @since 1.0
	 */
	do_action( 'et_head_meta' );
}
add_action( 'et_theme_builder_template_head', 'et_theme_builder_frontend_add_head' );

/**
 * Filter builder content wrapping as Theme Builder Layouts are wrapped collectively instead of individually.
 *
 * @since 4.0
 *
 * @param bool $wrap
 *
 * @return bool
 */
function et_theme_builder_frontend_filter_add_outer_content_wrap( $wrap ) {
	$layouts = et_theme_builder_get_template_layouts();

	$has_layouts     = ! empty( $layouts );
	$override_header = $has_layouts && $layouts[ ET_THEME_BUILDER_HEADER_LAYOUT_POST_TYPE ]['override'];
	$override_body   = $has_layouts && $layouts[ ET_THEME_BUILDER_BODY_LAYOUT_POST_TYPE ]['override'];
	$override_footer = $has_layouts && $layouts[ ET_THEME_BUILDER_FOOTER_LAYOUT_POST_TYPE ]['override'];

	// Theme Builder layouts must not be individually wrapped as they are wrapped
	// collectively, with the exception of the BFB.
	if ( ( $override_header || $override_body || $override_footer ) && ! et_builder_bfb_enabled() ) {
		$wrap = false;
	}

	return $wrap;
}
add_filter( 'et_builder_add_outer_content_wrap', 'et_theme_builder_frontend_filter_add_outer_content_wrap' );

/**
 * Render a template builder layout.
 *
 * Wrapper cases:
 * 1. Header/Footer are replaced.
 *   => Common is open and closed. Header/Footer do not get opened/closed because
 *      Common is opened before them.
 *
 * 2. Body is replaced.
 *   => Common is NOT opened/closed. Body is open/closed.
 *
 * 3. Header/Body/Footer are replaced.
 *   => Common is open and closed. Header/Body/Footer do not get opened/closed because
 *      Common is opened before them.
 *
 * @since 4.0
 *
 * @param string $layout_type
 * @param integer $layout_id
 *
 * @return void
 */
function et_theme_builder_frontend_render_layout( $layout_type, $layout_id ) {
	if ( $layout_id <= 0 ) {
		return;
	}

	$layout = get_post( $layout_id );

	if ( null === $layout || $layout->post_type !== $layout_type ) {
		return;
	}

	et_theme_builder_frontend_render_common_wrappers( $layout_type, true );

	ET_Builder_Element::begin_theme_builder_layout( $layout_id );

	ET_Post_Stack::replace( $layout );

	the_content();

	// Handle style output.
	if ( is_singular() && ! et_core_is_fb_enabled() ) {
		$result = ET_Builder_Element::setup_advanced_styles_manager( ET_Post_Stack::get_main_post_id() );
	} else {
		$result = ET_Builder_Element::setup_advanced_styles_manager( $layout->ID );
	}

	$manager = $result['manager'];

	if ( ! $manager->has_file() ) {
		$styles = ET_Builder_Element::get_style( false, $layout->ID ) . ET_Builder_Element::get_style( true, $layout->ID ) . et_pb_get_page_custom_css( $layout->ID );

		if ( $styles ) {
			$manager->set_data( $styles, 40 );

			// Output the styles inline in the footer on first render as we are already
			// past "head-late" where they will be enqueued once static files are generated.
			$manager->write_file_location = 'footer';
			$manager->set_output_location( 'footer' );
		}
	}

	ET_Post_Stack::restore();

	ET_Builder_Element::end_theme_builder_layout();

	et_theme_builder_frontend_render_common_wrappers( $layout_type, false );
}

/**
 * Render a header layout.
 *
 * @since 4.0
 *
 * @param integer $layout_id The layout id or 0.
 * @param boolean $layout_enabled
 * @param integer $template_id The template id or 0.
 *
 * @return void
 */
function et_theme_builder_frontend_render_header( $layout_id, $layout_enabled, $template_id ) {
	/**
	 * Fires before theme builder page wrappers are output.
	 * Example use case is to add opening wrapping html tags for the entire page.
	 *
	 * @since 4.0
	 *
	 * @param integer $layout_id The layout id or 0.
	 * @param bool $layout_enabled
	 * @param integer $template_id The template id or 0.
	 */
	do_action( 'et_theme_builder_template_before_page_wrappers', $layout_id, $layout_enabled, $template_id );

	et_theme_builder_frontend_render_common_wrappers( 'common', true );

	/**
	 * Fires before theme builder template header is output.
	 * Example use case is to add opening wrapping html tags for the header and/or the entire page.
	 *
	 * @since 4.0
	 *
	 * @param integer $layout_id The layout id or 0.
	 * @param bool $layout_enabled
	 * @param integer $template_id The template id or 0.
	 */
	do_action( 'et_theme_builder_template_before_header', $layout_id, $layout_enabled, $template_id );

	if ( $layout_enabled ) {
		et_theme_builder_frontend_render_layout( ET_THEME_BUILDER_HEADER_LAYOUT_POST_TYPE, $layout_id );
	}

	/**
	 * Fires after theme builder template header is output.
	 * Example use case is to add closing wrapping html tags for the header.
	 *
	 * @since 4.0
	 *
	 * @param integer $layout_id The layout id or 0.
	 * @param bool $layout_enabled
	 * @param integer $template_id The template id or 0.
	 */
	do_action( 'et_theme_builder_template_after_header', $layout_id, $layout_enabled, $template_id );
}

/**
 * Render a body layout.
 *
 * @since 4.0
 *
 * @param integer $layout_id The layout id or 0.
 * @param boolean $layout_enabled
 * @param integer $template_id The template id or 0.
 *
 * @return void
 */
function et_theme_builder_frontend_render_body( $layout_id, $layout_enabled, $template_id ) {
	/**
	 * Fires before theme builder template body is output.
	 * Example use case is to add opening wrapping html tags for the body.
	 *
	 * @since 4.0
	 *
	 * @param integer $layout_id The layout id or 0.
	 * @param bool $layout_enabled
	 * @param integer $template_id The template id or 0.
	 *
	 * @return void
	 */
	do_action( 'et_theme_builder_template_before_body', $layout_id, $layout_enabled, $template_id );

	if ( $layout_enabled ) {
		et_theme_builder_frontend_render_layout( ET_THEME_BUILDER_BODY_LAYOUT_POST_TYPE, $layout_id );
	}

	/**
	 * Fires after theme builder template body is output.
	 * Example use case is to add closing wrapping html tags for the body.
	 *
	 * @since 4.0
	 *
	 * @param integer $layout_id The layout id or 0.
	 * @param bool $layout_enabled
	 * @param integer $template_id The template id or 0.
	 *
	 * @return void
	 */
	do_action( 'et_theme_builder_template_after_body', $layout_id, $layout_enabled, $template_id );
}

/**
 * Render a footer layout.
 *
 * @since 4.0
 *
 * @param integer $layout_id The layout id or 0.
 * @param boolean $layout_enabled
 * @param integer $template_id The template id or 0.
 *
 * @return void
 */
function et_theme_builder_frontend_render_footer( $layout_id, $layout_enabled, $template_id ) {
	/**
	 * Fires before theme builder template footer is output.
	 * Example use case is to add opening wrapping html tags for the footer.
	 *
	 * @since 4.0
	 *
	 * @param integer $layout_id The layout id or 0.
	 * @param bool $layout_enabled
	 * @param integer $template_id The template id or 0.
	 *
	 * @return void
	 */
	do_action( 'et_theme_builder_template_before_footer', $layout_id, $layout_enabled, $template_id );

	if ( $layout_enabled ) {
		et_theme_builder_frontend_render_layout( ET_THEME_BUILDER_FOOTER_LAYOUT_POST_TYPE, $layout_id );
	}

	/**
	 * Fires after theme builder template footer is output.
	 * Example use case is to add closing wrapping html tags for the footer and/or the entire page.
	 *
	 * @since 4.0
	 *
	 * @param integer $layout_id The layout id or 0.
	 * @param bool $layout_enabled
	 * @param integer $template_id The template id or 0.
	 *
	 * @return void
	 */
	do_action( 'et_theme_builder_template_after_footer', $layout_id, $layout_enabled, $template_id );

	et_theme_builder_frontend_render_common_wrappers( 'common', false );

	/**
	 * Fires after theme builder page wrappers are output.
	 * Example use case is to add closing wrapping html tags for the entire page.
	 *
	 * @since 4.0
	 *
	 * @param integer $layout_id The layout id or 0.
	 * @param bool $layout_enabled
	 * @param integer $template_id The template id or 0.
	 */
	do_action( 'et_theme_builder_template_after_page_wrappers', $layout_id, $layout_enabled, $template_id );
}

/**
 * Open or close common builder wrappers (e.g. #et-boc) in order to avoid having triple wrappers - one for every layout.
 *
 * Useful
 *
 * @param $area
 * @param $open
 */
function et_theme_builder_frontend_render_common_wrappers( $area, $open ) {
	static $wrapper = '';

	if ( $open ) {
		// Open wrappers only if there are no other open wrappers already.
		if ( '' === $wrapper ) {
			$wrapper = $area;
			echo et_builder_get_builder_content_opening_wrapper();
		}
		return;
	}

	if ( '' === $wrapper || $area !== $wrapper ) {
		// Do not close wrappers if the opener does not match the current area.
		return;
	}

	echo et_builder_get_builder_content_closing_wrapper();
}

/**
 * Get the html representing the post content for the current post.
 *
 * @since 4.0
 *
 * @return string
 */
function et_theme_builder_frontend_render_post_content() {
	static $__prevent_recursion = false;

	if ( ET_Builder_Element::get_theme_builder_layout_type() !== ET_THEME_BUILDER_BODY_LAYOUT_POST_TYPE ) {
		// Prevent usage on non-body layouts.
		return '';
	}

	if ( ! is_singular() ) {
		// Do not output anything on non-singular pages.
		return '';
	}

	$main_query_post = ET_Post_Stack::get_main_post();

	if ( ! $main_query_post ) {
		// Bail if there is no current post.
		return '';
	}

	if ( true === $__prevent_recursion ) {
		// Failsafe just in case.
		return '';
	}

	$__prevent_recursion = true;
	$html     = '';
	$buffered = ob_start();

	if ( $buffered ) {
		ET_Post_Stack::replace( $main_query_post );

		ET_Builder_Element::begin_theme_builder_layout( get_the_ID() );

		the_content();

		ET_Builder_Element::end_theme_builder_layout();

		ET_Post_Stack::restore();

		$html = ob_get_clean();
	}

	$__prevent_recursion = false;

	return $html;
}
