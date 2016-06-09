<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class FormidableToDocAction extends FrmFormAction {

	protected $form_default = array( 'wrk_name' => '' );

	public function __construct() {
		$action_ops = array(
			'classes'  => 'dashicons dashicons-media-text for_to_doc_icon',
			'limit'    => 99,
			'active'   => true,
			'priority' => 50,
			'event'    => [ 'create', 'update' ],
		);

		$this->FrmFormAction( 'formidable_to_document', ForDocManager::t( 'F. to Doc' ), $action_ops );
	}

	/**
	 * @return array  WP_Post
	 */
	public function get_documents_templates() {
		$accepted_mimes         = array(
			'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
			'application/msword'
		);
		$query_attachments_args = array(
			'post_type'      => 'attachment',
			'post_mime_type' => $accepted_mimes,
			'post_status'    => 'inherit',
		);

		$query_attachments = new WP_Query( $query_attachments_args );
		$files             = array();
		foreach ( $query_attachments->posts as $file ) {
//			$files[]= wp_get_attachment_url( $file->ID );
			$files[] = $file;
		}

		return $files;
	}

	/**
	 * Get the HTML for your action settings
	 */
	public function form( $form_action, $args = array() ) {
		extract( $args );
		$form           = $args['form'];
		$fields         = $args['values']['fields'];
		$action_control = $this;
		if ( $form->status === 'published' ) {
			?>
			<input type="hidden" value="<?php echo esc_attr( $form_action->post_content['form_id'] ); ?>" name="<?php echo $action_control->get_field_name( 'form_id' ) ?>">
			<h3><?= ForDocManager::t( 'Field with pattern to replace in template document' ) ?></h3>
			<hr/>
			<table class="form-table frm-no-margin">
				<tbody>
				<tr>
					<th>
						<label> <b><?= ForDocManager::t( ' Form reference: ' ); ?></b></label>
					</th>
					<td><?= ForDocManager::t( '<b>Pattern: </b>' ) . '${form_reference}'; ?></td>
				</tr>
				<tr>
					<th>
						<label> <b><?= ForDocManager::t( ' Create/Update at: ' ); ?></b></label>
					</th>
					<td><?= ForDocManager::t( '<b>Pattern: </b>' ) . '${generation_time}'; ?></td>
				</tr>
				<tr>
					<td colspan="2">
						<hr/>
					</td>
				</tr>
				<?php
				foreach ( $fields as $field ) {
					$key                        = $field['field_key'];
					$this->form_default[ $key ] = '';
					if(!in_array($field['type'], ForDocManager::getUnUsedFields())) {
						switch ( $field['type'] ) {
							case 'date':
								?>
								<tr>
									<th>
										<label> <b><?= $field['name'] ?></b></label>
									</th>
									<td>
										<?= ForDocManager::t( '<b>Pattern: </b>' ) . '${' . $key . '}'. ForDocManager::t( ' for full or ')
										    . '${' . $key . '_date} ' . '${' . $key . '_month} ' . '${' . $key . '_year} '. ForDocManager::t( ' for segmented date.'); ?>
									</td>
								</tr>
								<?php
								break;
							case 'radio':
							case 'checkbox':
							case 'select':
							case 'scale':
								?>
								<tr>
									<th>
										<label> <b><?= $field['name'] ?></b></label>
									</th>
									<td>
										<?= ForDocManager::t( '<b>Pattern: </b>' ) . '${' . $key . '}'; ?><br/>
										<?php foreach ( $field['options'] as $option ) { ?>
											<br/><b><?= ForDocManager::t( ' Text to replace for ' ); ?></b><?= $option ?>:<br/>
											<textarea id="<?= $action_control->get_field_name( $key . '_' . $option ) ?>" name="<?= $action_control->get_field_name( $key . '_' . $option ) ?>" class="large-text ftd_edito frm_formidable_to_document"><?= esc_attr( $form_action->post_content[ $key . '_' . $option ] ) ?></textarea>
										<?php } ?>
									</td>
								</tr>
								<?php
								break;
							default:
								?>
								<tr>
									<th>
										<label> <b><?= $field['name'] ?></b></label>
									</th>
									<td>
										<?= ForDocManager::t( '<b>Pattern: </b>' ) . '${' . $key . '}'; ?>
									</td>
								</tr>
								<?php
								break;
						}
						?>
						<tr>
							<td colspan="2">
								<hr/>
							</td>
						</tr>
					<?php
					}
				}
				?>
				</tbody>
			</table>
			<script type='text/javascript' src='<?= site_url(); ?>/wp-content/plugins/formidable_to_doc/js/tinymce/tinymce.min.js'></script>
			<script type='text/javascript' src='<?= site_url(); ?>/wp-includes/js/jquery/jquery.form.min.js'></script>
			<input type="hidden" value="<?= esc_attr( $form_action->post_content['template_attachment_id'] ); ?>" name="<?= $action_control->get_field_name( 'template_attachment_id' ) ?>" id="template_attachment_id_frm">
			<input type="hidden" value="<?= esc_attr( $form_action->post_content['template_attachment_url'] ); ?>" name="<?= $action_control->get_field_name( 'template_attachment_url' ) ?>" id="template_attachment_url_frm">
			<?php
			$file_template_id = $form_action->post_content['template_attachment_id'];
			if ( isset( $file_template_id ) && ! empty( $file_template_id ) ) {
				$uploaded_file_template_container_display = '';
				$upload_file_template_container_display   = 'style="display:none"';
			} else {
				$uploaded_file_template_container_display = 'style="display:none"';
				$upload_file_template_container_display   = '';
			}
			?>
			<div id="uploaded_file_template_container" <?= $uploaded_file_template_container_display; ?>>
				<h3><?= ForDocManager::t( 'Uploaded document template' ) ?></h3>

				<form name="delete-template" method="POST" id="delete-template" action="<?= admin_url( 'admin-ajax.php' ); ?>">
					<?php wp_nonce_field( 'delete_template_file_nonce', 'security-delete' ); ?>
					<input type="hidden" name="action" value="delete_template_file">
					<input type="hidden" value="<?= esc_attr( $form_action->post_content['template_attachment_id'] ); ?>" name="template_attachment_file_id" id="template_attachment_file_id">

					<div style="display: table-caption; position: relative;">
						<a id="template_attachment_url_link" href="<?= esc_attr( $form_action->post_content['template_attachment_url'] ); ?>">
							<img width="48" height="64" src="<?= site_url(); ?>/wp-includes/images/media/document.png" class="attachment-thumbnail size-thumbnail" alt="<?= ForDocManager::t( 'Template document' ) ?>">
						</a>
						<input type="submit" style="margin-top: 5px;" class="button-primary" value="<?= ForDocManager::t( 'Delete template' ) ?>">
						<img style="display: none" id="upload_progress_2" src="<?= site_url(); ?>/wp-content/plugins/formidable/images/ajax_loader.gif" alt="<?= ForDocManager::t( 'Uploading' ) ?>">
					</div>
				</form>
			</div>

			<div id="upload_file_template_container" <?= $upload_file_template_container_display; ?>>
				<h3><?= ForDocManager::t( 'Upload document template (Remember include the patterns) ' ) ?></h3>

				<form name="upload-template" method="POST" id="upload-template">
					<div style="display: table-caption; position: relative;">
						<?php wp_nonce_field( 'upload_template_file_nonce', 'security-upload' ); ?>
						<input type="hidden" name="action" value="upload_template_file">
						<label for="file_upload"><p><?= ForDocManager::t( 'Select file template' ) ?></p></label>
						<input type="file" name="file_upload" id="file_upload">
						<input type="submit" class="button-primary" value="<?= ForDocManager::t( 'Upload template' ) ?>">
						<img style="display: none" id="upload_progress_1" src="<?= site_url(); ?>/wp-content/plugins/formidable/images/ajax_loader.gif" alt="<?= ForDocManager::t( 'Uploading' ) ?>">
					</div>
				</form>
			</div>
		<?php
		} else {
			echo ForDocManager::t( 'The form need to published.' );
		}
		$language    = substr( get_bloginfo( 'language' ), 0, 2 );
		$base_upload = wp_upload_dir();
		?>
		<script>
			var ajax_url = "<?= admin_url('admin-ajax.php'); ?>";
			tinymce.init({
				selector: '.ftd_edito',
				automatic_uploads: true,
				language: '<?= $language ?>',
				relative_urls: false,
				remove_script_host: false,
				force_p_newlines : false,
				forced_root_block : '',
				toolbar: "insertfile undo redo | styleselect | bold italic | forecolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image | code",
				plugins: 'image imagetools code textcolor colorpicker',
				imagetools_toolbar: "rotateleft rotateright | flipv fliph | editimage imageoptions",
				imagetools_cors_hosts: ['localhost', 'peritosdeaccidentes.com']
			});
		</script>
		<script type='text/javascript' src='<?= site_url(); ?>/wp-content/plugins/formidable_to_doc/js/for_to_doc.js'></script>
	<?php
	}

	/**
	 * Add the default values for your options here
	 */
	function get_defaults() {
		$result = array(
			'form_id'                 => $this->get_field_name( 'form_id' ),
			'template_attachment_id'  => '',
			'template_attachment_url' => '',
		);

		if ( $this->form_id != null ) {
			$result['form_id'] = $this->form_id;
		}

		global $frm_field;
		$fields = $frm_field->getAll( array( 'fi.form_id' => $result['form_id'] ), 'field_order' );
		foreach ( $fields as $field ) {
			$key = $field->field_key;
			switch ( $field->type ) {
				case 'radio':
				case 'checkbox':
				case 'select':
				case 'scale':
					foreach ( $field->options as $option ) {
						$result[ $key . '_' . $option ] = '';
					}
					break;
				default:
					$result[ $key ] = '';
					break;
			}
		}

		return $result;
	}
}