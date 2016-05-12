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
		);

		$this->FrmFormAction( 'formidable_to_document', ForDocManager::t( 'F. to Doc' ), $action_ops );
	}

	/**
	 * @return array  WP_Post
	 */
	public function get_documents_templates(){
		$accepted_mimes  = array(
			'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
			'application/msword'
		);
		$query_attachments_args = array(
			'post_type' => 'attachment',
			'post_mime_type' => $accepted_mimes,
			'post_status' => 'inherit',
		);

		$query_attachments = new WP_Query( $query_attachments_args );
		$files = array();
		foreach ( $query_attachments->posts as $file) {
//			$files[]= wp_get_attachment_url( $file->ID );
			$files[]=  $file;
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

			$current_attachment_id = esc_attr( $form_action->post_content['template_attachment_id'] );
			$files = $this->get_documents_templates();
			?>
			<input type="hidden" value="<?php echo esc_attr( $form_action->post_content['form_id'] ); ?>" name="<?php echo $action_control->get_field_name( 'form_id' ) ?>">
			<p><?= ForDocManager::t( 'Select document template where yo replace patterns: ' ) ?></p>
			<select id="<?= $action_control->get_field_name( 'template_attachment_id' ) ?>" name="<?= $action_control->get_field_name( 'template_attachment_id' ) ?>">
				<?php
				foreach($files as $file){
					if($current_attachment_id == $file->ID){
						$selected = 'selected="selected"';
					}
					else{
						$selected = '';
					}
					echo '<option '.$selected.' name="template_attachment_id" value="'.$file->ID.'">'.$file->post_title.'</option>';
				}
				?>
			</select>
			<table class="form-table frm-no-margin">
				<tbody>
				<?php
				foreach ( $fields as $field ) {
					$key                        = $field['field_key'];
					$this->form_default[ $key ] = '';
					switch ( $field['type'] ) {
						case 'radio':
						case 'checkbox':
						case 'select':
						case 'scale':
							?>
							<tr>
								<th>
									<label> <b><?= $field['name'] ?></label>
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
						<td colspan="2"><hr/></td>
					</tr>
					<?php
				}
				?>
				</tbody>
			</table>
			<script type='text/javascript' src='<?= site_url(); ?>/wp-content/plugins/formidable_to_doc/js/tinymce/tinymce.min.js'></script>
		<?php
		} else {
			echo ForDocManager::t( 'The form need to published.' );
		}
		$language = substr( get_bloginfo( 'language' ), 0, 2 );
		?>
		<script>
			tinymce.init({
				selector: '.ftd_edito',
				language: '<?= $language ?>'
			});
		</script>
	<?php
	}

	/**
	 * Add the default values for your options here
	 */
	function get_defaults() {
		$result = array(
			'form_id' => $this->get_field_name( 'form_id' ),
			'template_attachment_id' => '',
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