<?php
/**
 * BC_Admin_Labels_Page class.
 *
 * @package Brightcove_Video_Connect
 */

/**
 * The Labels Page class.
 *
 * Class BC_Admin_Labels_Page
 */
class BC_Admin_Labels_Page {

	/**
	 * BC_Labels object
	 *
	 * @var BC_Labels
	 */
	protected $bc_labels;

	/**
	 * Constructor class
	 *
	 * BC_Admin_Labels_Page constructor.
	 */
	public function __construct() {
		$this->bc_labels = new BC_Labels();

		add_action( 'brightcove_admin_labels_page', array( $this, 'render_labels_page' ) );
		add_action( 'brightcove_admin_edit_label_page', array( $this, 'render_edit_label_page' ) );
	}

	/**
	 * Renders html of the edit labels page
	 */
	public function render_edit_label_page() {
		$label_name = isset( $_GET['update_label'] ) ? $_GET['update_label'] : ''; // phpcs:ignore WordPress.Security.NonceVerification
		?>
		<div class="wrap">
			<h2>
				<?php
				printf( '<img src="%s" class="brightcove-admin-icon"/>', esc_url( plugins_url( 'images/menu-icon.svg', dirname( __DIR__ ) ) ) );
				esc_html_e( 'Edit Label', 'brightcove' );
				?>
			</h2>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="validate">
				<?php wp_nonce_field( 'brightcove-edit-label', 'brightcove-edit-label-nonce' ); ?>
				<input type="hidden" name="action" value="brightcove-edit-label">
				<input type="hidden" name="label-path" value="<?php echo esc_attr( $label_name ); ?>">
				<table class="form-table">
					<tbody>
						<tr class="form-field form-required term-name-wrap">
							<th scope="row"><label for="name"><?php esc_html_e( 'Label', 'brightcove' ); ?></label></th>
							<td>
								<input name="label-update" id="name" type="text" value="<?php echo esc_attr( basename( trim( $label_name, '/' ) ) ); ?>" size="40" aria-required="true">
								<p class="description"><?php esc_html_e( 'Enter the new label name.', 'brightcove' ); ?></p>
							</td>
						</tr>
					</tbody>
				</table>
				<p class="submit">
					<input type="submit" class="button button-primary" value="Update">
				</p>
			</form>
		</div>
		<?php
	}

	/**
	 * Generates an HTML table with all configured sources
	 */
	public function render_labels_page() {
		$maybe_refresh = isset( $_GET['refresh_labels'] ) && (bool) $_GET['refresh_labels']; // phpcs:ignore
		$labels        = $this->bc_labels->fetch_all( $maybe_refresh );
		?>
		<div class="wrap">
			<h2>
				<img class="bc-page-icon" src="<?php echo esc_url( BRIGHTCOVE_URL . 'images/menu-icon.svg' ); ?>">
				<?php esc_html_e( 'Brightcove Labels', 'brightcove' ); ?>
			</h2>
		</div>

		<div class="wrap">
			<div id="col-container" class="wp-clearfix">
				<div id="col-left">
					<div class="col-wrap">
						<div class="form-wrap">
							<h2><?php esc_html_e( 'Add New Label', 'brightcove' ); ?></h2>
							<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="validate">
								<?php wp_nonce_field( 'brightcove-add-label', 'brightcove-add-label-nonce' ); ?>
								<input type="hidden" name="action" value="brightcove-add-label">
								<div class="form-field form-required">
									<label for="label-name"><?php esc_html_e( 'Label Name', 'brightcove' ); ?></label>
									<input name="label-name" id="label-name" type="text" value="" size="40" aria-required="true">
									<p><?php esc_html_e( 'The name of the label', 'brightcove' ); ?></p>
								</div>
								<div class="form-field">
									<label for="label-path"><?php esc_html_e( 'Parent Label', 'brightcove' ); ?></label>
									<input name="label-path" id="label-path" type="text" value="" size="40">
									<p><?php esc_html_e( 'Type the hierarchy you want your label to have. Example "/animals/mammals/" Leave blank if you do not wish to add a hierarchy.', 'brightcove' ); ?></p>
								</div>
								<p class="submit">
									<input type="submit" name="submit" class="button button-primary" value="Add New label">
								</p>
							</form>
								<p>
									<a href="<?php echo esc_url( add_query_arg( array( 'refresh_labels' => true ) ) ); ?>"><input type="submit" name="submit" class="button action" value="Refresh Labels"></a>
								</p>
						</div>
					</div>
				</div><!-- /col-left -->

				<div id="col-right">
					<div class="col-wrap">
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="validate">
							<?php wp_nonce_field( 'brightcove-delete-label', 'brightcove-delete-label-nonce' ); ?>
							<input type="hidden" name="action" value="brightcove-delete-label">
							<div class="tablenav top">
								<div class="alignleft actions bulkactions">
									<label for="bulk-action-selector-top" class="screen-reader-text"><?php esc_html_e( 'Select bulk action' ); ?></label>
									<select name="test" id="bulk-action-selector-top">
										<option value="-1"><?php esc_html_e( 'Bulk actions' ); ?></option>
										<option value="delete"><?php esc_html_e( 'Delete' ); ?></option>
									</select>
									<input type="submit" name="submit" class="button action" value="Apply"<span class="spinner"></span>
								</div>
							</div>
							<table class="wp-list-table widefat fixed striped table-view-list">
								<thead>
								<tr>
									<td id="cb" class="manage-column column-cb check-column brightcove-labels-column"><label class="screen-reader-text" for="cb-select-all-1"><?php esc_html_e( 'Select All' ); ?></label><input id="cb-select-all-1" type="checkbox"></td><th scope="col" id="name" class="manage-column column-name column-primary"><span><?php esc_html_e( 'Name' ); ?></span></th></tr>
								</thead>
								<tbody id="the-list">
								<?php
								if ( $labels ) {
									foreach ( $labels as $index => $label ) :
										?>
										<tr class="level-0">
											<th scope="row" class="check-column">
												<label class="screen-reader-text" for="cb-select-<?php echo esc_attr( $index ); ?>"></label>
												<input type="checkbox" name="delete_labels[]" value="<?php echo esc_attr( $label ); ?>" id="cb-select-<?php echo esc_attr( $index ); ?>">
											</th>
											<td class="name column-name has-row-actions column-primary">
											<strong><?php echo esc_html( $label ); ?></strong>
											<br>
											<div class="hidden" id="inline_1">
												<div class="name"><?php echo esc_html( $label ); ?></div>
												<div class="slug"><?php echo esc_html( $label ); ?></div>
											</div>
											<div class="row-actions">
												<span class="edit"><a href="<?php echo esc_url( admin_url( 'admin.php?page=page-brightcove-edit-label' ) . '&update_label=' . $label ); ?>" aria-label="Edit “Uncategorized”"><?php echo esc_html_e( 'Edit' ); ?></a></span>
											</div>
											</td>
										</tr>
										<?php
									endforeach;
								}
								?>
								</tbody>
								<tfoot>
								<tr>
									<td class="manage-column column-cb check-column"><label class="screen-reader-text" for="cb-select-all-2"><?php esc_html_e( 'Select All' ); ?></label><input id="cb-select-all-2" type="checkbox"></td>
									<th scope="col" class="manage-column column-name column-primary brightcove-labels-column"><span><?php esc_html_e( 'Name' ); ?></span></th>
								</tr>
								</tfoot>
							</table>
							<div class="tablenav bottom">
								<div class="alignleft actions bulkactions">
									<label for="bulk-action-selector-bottom" class="screen-reader-text"><?php esc_html_e( 'Select bulk action' ); ?></label><select name="action2" id="bulk-action-selector-bottom">
										<option value="-1">Bulk actions</option>
										<option value="delete">Delete</option>
									</select>
									<input type="submit" name="submit" class="button action" value="Apply"<span class="spinner"></span>
								</div>
							</div>
						</form>
					</div>
				</div><!-- /col-right -->
			</div>

		</div>

		<?php
	}
}
