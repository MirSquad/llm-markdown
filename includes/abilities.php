<?php
/**
 * WordPress Abilities API integration for LLM Markdown.
 * Requires WP 6.9+ (Abilities API). Does nothing on older versions.
 *
 * Read abilities are always registered.
 * Write abilities are only registered when "Enable write abilities" is on
 * in Settings > LLM Markdown.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Bail silently on WordPress versions that don't have the Abilities API.
if ( ! function_exists( 'wp_register_ability' ) ) {
	return;
}

// -------------------------------------------------------------------------
// Register category.
// -------------------------------------------------------------------------
add_action( 'wp_abilities_api_categories_init', 'llmmd_register_ability_category' );
function llmmd_register_ability_category() {
	wp_register_ability_category( 'llm-markdown', array(
		'label'       => __( 'LLM Markdown', 'llm-markdown' ),
		'description' => __( 'Inspect LLM Markdown settings and trigger content regeneration.', 'llm-markdown' ),
	) );
}

// -------------------------------------------------------------------------
// Register abilities.
// -------------------------------------------------------------------------
add_action( 'wp_abilities_api_init', 'llmmd_register_abilities' );
function llmmd_register_abilities() {

	// --- get-settings (always available) ---------------------------------

	wp_register_ability( 'llm-markdown/get-settings', array(
		'label'       => __( 'Get Settings', 'llm-markdown' ),
		'description' => __( 'Retrieve LLM Markdown settings: enabled post types and content root selector.', 'llm-markdown' ),
		'category'    => 'llm-markdown',
		'output_schema' => array(
			'type'       => 'object',
			'properties' => array(
				'enabled_post_types' => array(
					'type'  => 'array',
					'items' => array( 'type' => 'string' ),
					'description' => 'Post types for which markdown files are generated.',
				),
				'root_selector' => array(
					'type'        => 'string',
					'description' => 'CSS selector used to extract content. Empty string means full post content.',
				),
			),
		),
		'permission_callback' => fn() => current_user_can( 'manage_options' ),
		'execute_callback'    => function( $input = null ) {
			return array(
				'enabled_post_types' => llmmd_get_enabled_post_types(),
				'root_selector'      => llmmd_get_root_selector(),
			);
		},
		'meta' => array(
			'mcp'         => array( 'public' => true ),
			'annotations'  => array(
				'readonly'    => true,
				'destructive' => false,
				'idempotent'  => true,
			),
		),
	) );

	// --- Write abilities (gated by option) --------------------------------

	if ( ! get_option( 'llmmd_write_abilities', false ) ) {
		return;
	}

	wp_register_ability( 'llm-markdown/regenerate-files', array(
		'label'       => __( 'Regenerate Markdown Files', 'llm-markdown' ),
		'description' => __( 'Regenerate cached markdown for all published posts across all enabled post types. On large sites this may take several seconds.', 'llm-markdown' ),
		'category'    => 'llm-markdown',
		'output_schema' => array(
			'type'       => 'object',
			'properties' => array(
				'success' => array( 'type' => 'boolean' ),
				'message' => array( 'type' => 'string' ),
			),
		),
		'permission_callback' => fn() => current_user_can( 'manage_options' ),
		'execute_callback'    => function( $input = null ) {
			llmmd_bulk_generate();
			delete_transient( 'llmmd_llms_txt' );
			return array(
				'success' => true,
				'message' => __( 'Markdown files regenerated for all published content.', 'llm-markdown' ),
			);
		},
		'meta' => array(
			'mcp'         => array( 'public' => true ),
			'annotations'  => array(
				'readonly'    => false,
				'destructive' => false,
				'idempotent'  => true,
			),
		),
	) );
}
