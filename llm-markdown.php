<?php
/**
 * Plugin Name:       LLM Markdown
 * Plugin URI:        https://miriamschwab.me/plugins/llm-markdown
 * Description:       Serves markdown versions of site content at .md URLs for LLMs, with llms.txt site index.
 * Version:           1.2.2
 * Author:            Miriam Schwab
 * Author URI:        https://miriamschwab.me
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       llm-markdown
 * Domain Path:       /languages
 * Requires at least: 6.0
 * Requires PHP:      7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'LLMMD_VERSION', '1.2.2' );
define( 'LLMMD_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'LLMMD_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'LLMMD_PLUGIN_FILE', __FILE__ );

require_once LLMMD_PLUGIN_DIR . 'vendor/autoload.php';
require_once LLMMD_PLUGIN_DIR . 'includes/class-llmmd-converter.php';
require_once LLMMD_PLUGIN_DIR . 'includes/class-llmmd-server.php';
require_once LLMMD_PLUGIN_DIR . 'includes/class-llmmd-llmstxt.php';
require_once LLMMD_PLUGIN_DIR . 'includes/class-llmmd-admin.php';
require_once LLMMD_PLUGIN_DIR . 'includes/abilities.php';

add_action( 'init', 'llmmd_load_textdomain' );
function llmmd_load_textdomain() {
	load_plugin_textdomain( 'llm-markdown', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}

function llmmd_get_enabled_post_types() {
	$settings = get_option( 'llmmd_settings', [] );
	$defaults = [ 'post', 'page' ];
	return isset( $settings['post_types'] ) && is_array( $settings['post_types'] )
		? $settings['post_types']
		: $defaults;
}

function llmmd_get_root_selector() {
	$settings = get_option( 'llmmd_settings', [] );
	return isset( $settings['root_selector'] ) ? $settings['root_selector'] : '';
}

add_action( 'plugins_loaded', 'llmmd_check_version' );
function llmmd_check_version() {
	$stored = get_option( 'llmmd_version' );
	if ( $stored !== LLMMD_VERSION ) {
		delete_transient( 'llmmd_llms_txt' );
		update_option( 'llmmd_version', LLMMD_VERSION );
	}
}

LLMMD_Server::init();
LLMMD_LLMs_Txt::init();
LLMMD_Admin::init();

add_action( 'save_post', 'llmmd_on_save_post', 20, 2 );
function llmmd_on_save_post( $post_id, $post ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
		return;
	}
	if ( 'publish' !== $post->post_status ) {
		delete_post_meta( $post_id, '_llmmd_content' );
		return;
	}
	if ( ! in_array( $post->post_type, llmmd_get_enabled_post_types(), true ) ) {
		return;
	}
	$markdown = LLMMD_Converter::convert_post( $post_id );
	update_post_meta( $post_id, '_llmmd_content', $markdown );
	delete_transient( 'llmmd_llms_txt' );
}

add_action( 'transition_post_status', 'llmmd_on_status_change', 10, 3 );
function llmmd_on_status_change( $new_status, $old_status, $post ) {
	if ( $new_status !== $old_status && in_array( $post->post_type, llmmd_get_enabled_post_types(), true ) ) {
		if ( 'publish' === $old_status || 'publish' === $new_status ) {
			delete_transient( 'llmmd_llms_txt' );
		}
	}
}

add_action( 'wp_head', 'llmmd_alternate_link' );
function llmmd_alternate_link() {
	if ( is_front_page() && get_option( 'page_on_front' ) ) {
		$md_url = rtrim( home_url(), '/' ) . '/index.md';
		echo '<link rel="alternate" type="text/markdown" href="' . esc_url( $md_url ) . '">' . "\n";
		return;
	}
	if ( ! is_singular( llmmd_get_enabled_post_types() ) ) {
		return;
	}
	$url = rtrim( get_permalink(), '/' );
	$md_url = $url . '.md';
	echo '<link rel="alternate" type="text/markdown" href="' . esc_url( $md_url ) . '">' . "\n";
}

register_activation_hook( __FILE__, 'llmmd_activate' );
function llmmd_activate() {
	LLMMD_Server::add_rewrite_rules();
	flush_rewrite_rules();
	llmmd_bulk_generate();
}

register_deactivation_hook( __FILE__, 'llmmd_deactivate' );
function llmmd_deactivate() {
	flush_rewrite_rules();
}

function llmmd_bulk_generate() {
	$post_types = llmmd_get_enabled_post_types();
	if ( empty( $post_types ) ) {
		return;
	}
	/**
	 * Filters the maximum number of posts processed during bulk markdown generation.
	 * On large sites, set this to a reasonable limit (e.g. 500) to avoid timeouts.
	 * Default -1 processes all published posts.
	 *
	 * @param int $limit Posts per page. -1 for all.
	 */
	$limit = (int) apply_filters( 'llmmd_bulk_generate_limit', -1 );
	$posts = get_posts( [
		'post_type'      => $post_types,
		'post_status'    => 'publish',
		'posts_per_page' => $limit,
		'fields'         => 'ids',
	] );
	foreach ( $posts as $post_id ) {
		$markdown = LLMMD_Converter::convert_post( $post_id );
		update_post_meta( $post_id, '_llmmd_content', $markdown );
	}
}

add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'llmmd_action_links' );
function llmmd_action_links( $links ) {
	$settings_link = '<a href="' . esc_url( admin_url( 'options-general.php?page=llm-markdown' ) ) . '">' . esc_html__( 'Settings', 'llm-markdown' ) . '</a>';
	array_unshift( $links, $settings_link );
	return $links;
}

add_filter( 'plugin_row_meta', 'llmmd_plugin_row_meta', 10, 2 );
function llmmd_plugin_row_meta( $links, $file ) {
	if ( plugin_basename( LLMMD_PLUGIN_FILE ) !== $file ) {
		return $links;
	}
	foreach ( $links as $key => $link ) {
		if ( strpos( $link, 'plugin-install.php' ) !== false ) {
			unset( $links[ $key ] );
		}
	}
	$links[] = '<a href="' . esc_url( 'https://miriamschwab.me/plugins/llm-markdown' ) . '" target="_blank">' . esc_html__( 'Visit plugin site', 'llm-markdown' ) . '</a>';
	return $links;
}
