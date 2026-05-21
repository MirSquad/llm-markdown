<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'llmmd_settings' );
delete_option( 'llmmd_version' );
delete_transient( 'llmmd_llms_txt' );

global $wpdb;
$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->postmeta} WHERE meta_key = %s", '_llmmd_content' ) );
