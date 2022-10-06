<?php
/**
 * Plugin Name:       Editor Widgets
 * Description:       Proof of Concept for block based 'widget areas' within a FSE theme.
 * Requires at least: 5.9
 * Requires PHP:      7.0
 * Version:           0.1.0
 * Author:            Matt Watson
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       editor-widgets
 *
 * @package           editor-widgets
 */

/**
 * Registers the block using the metadata loaded from the `block.json` file.
 * Behind the scenes, it registers also all assets so they can be enqueued
 * through the block editor in the corresponding context.
 *
 * @see https://developer.wordpress.org/reference/functions/register_block_type/
 */
function editor_widgets_editor_widgets_block_init() {
	register_block_type(
		__DIR__ . '/build',
		array(
			'render_callback' => 'editor_widgets_editor_widgets_render_callback',
		)
	);
}
add_action( 'init', 'editor_widgets_editor_widgets_block_init' );

/**
 * Render callback function.
 *
 * @param array    $attributes The block attributes.
 * @param string   $content    The block content.
 * @param WP_Block $block      Block instance.
 *
 * @return string The rendered output.
 */
function editor_widgets_editor_widgets_render_callback( $attributes, $content, $block ) {
	ob_start();
	require plugin_dir_path( __FILE__ ) . 'build/template.php';
	return ob_get_clean();
}

/**
 * Widget Area Registration.
 *
 * Dynamically create widget areas depending on where the widget area blocks have
 * been inserted.
 *
 * @return void
 */
function widget_area_registration() {

	// PoC only. Because in our build we are using a network, and we only want the control
	// site to define widget areas, let's switch to the blog.
	switch_to_blog( 1 );

	// Get the widget areas from the options table.
	$widget_areas = get_option( 'editor-widgets', [] );

	restore_current_blog();

	// Arguments used in all register_sidebar() calls.
	$shared_args = array(
		'before_title'  => '<h2 class="widget-title subheading heading-size-3">',
		'after_title'   => '</h2>',
		'before_widget' => '<div class="widget %2$s"><div class="widget-content">',
		'after_widget'  => '</div></div>',
	);

	// Loop through the widget areas, and register the widget areas with 'register_sidebar'.
	foreach ( $widget_areas as $widget_area ) {
		if ( empty( $widget_area ) ) {
			continue;
		}
		register_sidebar(
			array_merge(
				$shared_args,
				[
					'name'        => $widget_area,
					'id'          => trim( sanitize_title( $widget_area ) ),
					// We should possibly capture the description and the ID in our widget block.
					// 'description' => __( 'Widgets in this area will be displayed in the first column in the footer.', 'twentytwenty' ),
				]
			)
		);
	}
}
add_action( 'widgets_init', 'widget_area_registration' );

/**
 * Save Widget Areas.
 *
 * Loop through all wp_templates and wp_template parts on save, and save
 * any widget areas in the site settings.
 *
 * @param  int     $post_id Post ID.
 * @param  WP_Post $post    Post Object.
 * @param  bool    $update  Updated.
 *
 * @return void
 */
function save_widget_areas( int $post_id, WP_Post $post, bool $update ) {
	// Validation needed.
	if ( $post->post_type !== 'wp_template' && $post->post_type !== 'wp_template_part' ) {
		return;
	}

	$template_query = new WP_Query([
		'post_type'      => [ 'wp_template', 'wp_template_part' ],
		'post_status'    => 'any',
		'posts_per_page' => -1, // This should never be -1.
	]);

	$widget_areas = [];

	// Loop through each template and pull out the blocks.
	// Doing it this way will account for removed widget areas as well as new ones.
	foreach ( $template_query->posts as $template_post ) {
		$blocks = parse_blocks( $template_post->post_content );
		$widget_areas = get_widget_areas( $blocks, $widget_areas );
	}

	// Save the widget areas.
	update_option( 'editor-widgets', $widget_areas );
}
add_action( 'save_post', 'save_widget_areas', 10, 3 );

/**
 * Get Widget Areas.
 *
 * Loop through all the inner blocks so that we get all the widget areas.
 *
 * @param  array $blocks       Blocks
 * @param  array $widget_areas Widget Areas.
 *
 * @return array
 */
function get_widget_areas( $blocks, $widget_areas ) {
	foreach ( $blocks as $block ) {
		if ( isset( $block['innerBlocks'] ) ) {
			$widget_areas = get_widget_areas( $block['innerBlocks'], $widget_areas );
		}

		if ( ! isset( $block['blockName'] ) || $block['blockName'] !== 'editor-widgets/editor-widgets' ) {
			continue;
		}

		if ( ! isset( $block['attrs']['widgetArea'] ) || empty( $block['attrs']['widgetArea'] ) ) {
			continue;
		}

		if ( in_array( $block['attrs']['widgetArea'], $widget_areas, true ) ) {
			continue;
		}

		$widget_areas[] = $block['attrs']['widgetArea'];
	}

	return $widget_areas;
}