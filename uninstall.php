<?php
/**
 * Uninstall cleanup for Adev OTP Login.
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'adev_otp_settings' );
delete_option( 'adev_otp_logs' );

global $wpdb;
$wpdb->query(
	$wpdb->prepare(
		"DELETE FROM {$wpdb->usermeta} WHERE meta_key = %s",
		'_adev_otp_phone'
	)
);
$wpdb->query(
	$wpdb->prepare(
		"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
		$wpdb->esc_like( '_transient_adev_otp_' ) . '%'
	)
);
$wpdb->query(
	$wpdb->prepare(
		"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
		$wpdb->esc_like( '_transient_timeout_adev_otp_' ) . '%'
	)
);
