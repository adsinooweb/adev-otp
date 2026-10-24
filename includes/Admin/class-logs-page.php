<?php
/**
 * SMS send logs admin page.
 */

namespace Adev_OTP\Admin;

defined( 'ABSPATH' ) || exit;

class Logs_Page {

	/**
	 * Render logs table.
	 */
	public function render(): void {
		$logs = get_option( 'adev_otp_logs', array() );
		if ( ! is_array( $logs ) ) {
			$logs = array();
		}
		$logs = array_reverse( $logs );

		?>
		<div class="wrap adev-otp-admin">
			<h1><?php esc_html_e( 'SMS send logs', 'adev-otp-login' ); ?></h1>
			<p class="description"><?php esc_html_e( 'OTP codes are not stored. Mobile numbers are shown masked.', 'adev-otp-login' ); ?></p>
			<table class="widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Time', 'adev-otp-login' ); ?></th>
						<th><?php esc_html_e( 'Mobile', 'adev-otp-login' ); ?></th>
						<th><?php esc_html_e( 'Gateway', 'adev-otp-login' ); ?></th>
						<th><?php esc_html_e( 'Status', 'adev-otp-login' ); ?></th>
						<th><?php esc_html_e( 'Message', 'adev-otp-login' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $logs ) ) : ?>
						<tr><td colspan="5"><?php esc_html_e( 'No logs yet.', 'adev-otp-login' ); ?></td></tr>
					<?php else : ?>
						<?php foreach ( $logs as $row ) : ?>
							<tr>
								<td><?php echo esc_html( isset( $row['time'] ) ? $row['time'] : '' ); ?></td>
								<td dir="ltr"><?php echo esc_html( isset( $row['phone'] ) ? $row['phone'] : '' ); ?></td>
								<td><?php echo esc_html( isset( $row['gateway'] ) ? $row['gateway'] : '' ); ?></td>
								<td>
									<?php
									$ok = ! empty( $row['success'] );
									echo $ok
										? '<span class="adev-otp-badge adev-otp-badge--ok">' . esc_html__( 'Success', 'adev-otp-login' ) . '</span>'
										: '<span class="adev-otp-badge adev-otp-badge--err">' . esc_html__( 'Failed', 'adev-otp-login' ) . '</span>';
									?>
								</td>
								<td><?php echo esc_html( isset( $row['message'] ) ? $row['message'] : '' ); ?></td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
		<?php
	}
}
