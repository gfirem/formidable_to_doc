<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<h1><?= ForDocManager::t( "Formidable to Documents Configurations" ) ?></h1>

<div class="card pressthis">

	<div id="tab-container" class="tab-container">
		<ul class='etabs'>
			<li class='tab'><a href="#tabs-licence-data"><?= ForDocManager::t( "Licence" ) ?></a></li>
			<li class='tab'><a href="#tab-data"><?= ForDocManager::t( "Configuration" ) ?></a></li>
		</ul>
		<div id="tabs-licence-data">
			<h2><?= ForDocManager::t( "F. to Doc. Licence" ) ?></h2>

			<form enctype="multipart/form-data" method="post" name="ftd_data" id="ftd_data">
				<table class="form-table">
					<tbody>
					<tr class="form-field form-required">
						<th valign="top" scope="row"><label for="licence"><?= ForDocManager::t( "Licence:" ) ?></label></th>
						<td><input type="text" aria-required="true" size="40" value="Octavio PDA" id="licence" name="licence">
						</td>
					</tr>
					<tr class="form-field form-required">
						<th valign="top" scope="row"><label for="status"><?= ForDocManager::t( "Status:" ) ?></label></th>
						<td><?php $val = ( $status ) ? ForDocManager::t( "Activated" ) : ForDocManager::t( "Deactivated" );
							echo "$val"; ?></td>
					</tr>
					<tr class="form-field">
						<th valign="top" scope="row">
						</th>
						<td style="text-align:left">
							<input type="submit" style="text-align:left" value="<?= ForDocManager::t( "Save" ) ?>" class="button button-primary" id="ftd_data_submit" name="ftd_data_submit">
						</td>
					</tr>
					</tbody>
				</table>
				<input type="hidden" id="ftd_action" name="ftd_action" value="save_data_license">
				<input type="hidden" value="save_data" id="action_update_config" name="action_update_config">
			</form>
		</div>
		<div id="tab-data">
			<h2><?= ForDocManager::t( "Google Drive Configuration" ) ?></h2>

			<form enctype="multipart/form-data" method="post" name="ftd_data" id="ftd_data">
				<table class="form-table">
					<tbody>
					<tr class="form-field form-required">
						<th valign="top" scope="row"><label for="client_id"><?= ForDocManager::t( "Client ID:" ) ?></label></th>
						<td><input type="text" aria-required="true" size="40" value="<?= $clientId ?>" id="client_id" name="client_id">
						</td>
					</tr>
					</tbody>
					<tbody>
					<tr class="form-field form-required">
						<th valign="top" scope="row"><label for="client_secret"><?= ForDocManager::t( "Client Secret:" ) ?></label></th>
						<td><input type="text" aria-required="true" size="40" value="<?= $clientSecret ?>" id="client_secret" name="client_secret">
						</td>
					</tr>
					<tr class="form-field form-required">
						<th valign="top" scope="row"><label for="client_return_url"><?= ForDocManager::t( "Client Return Url:" ) ?></label></th>
						<td><input type="text" readonly="readonly" aria-required="true" size="40" value="<?= admin_url( 'admin.php?page=formidable-to-document' ); ?>" id="client_return_url" name="client_return_url">
						</td>
					</tr>
					<tr class="form-field">
						<th valign="top" scope="row"><label for="api"><?= ForDocManager::t( "Status:" ) ?></label></th>
						<td>
							<?php
							if ( $connected ) {
								?><a class='logout' href='<?= admin_url( 'admin.php?page=formidable-to-document&logout#tab-data' ); ?>'><?= ForDocManager::t( "Logout!" ) ?></a><?php
							} else {
								?><a class='login' href='<?= $authUrl ?>'><?= ForDocManager::t( "Connect with google!" ) ?></a><?php
							}
							?>
						</td>
					</tr>
					<tr class="form-field">
						<th valign="top" scope="row">
						</th>
						<td style="text-align:left">
							<input type="submit" style="text-align:left" value="<?= ForDocManager::t( "Save" ) ?>" class="button button-primary" id="clickbank_data_submit" name="clickbank_data_submit">
						</td>
					</tr>
					</tbody>
				</table>
				<input type="hidden" id="ftd_action" name="ftd_action" value="save_data_configuration">
				<input type="hidden" value="save_data" id="action_update_config" name="action_update_config">
			</form>
		</div>
	</div>

	<script>
		jQuery(document).ready(function ($) {
			var $form = $('#ftd_data');

			jQuery('#tab-container').easytabs();

			$form.validate({
				lang: '<?= ForDocAdmin::getCurrentLanguageCode()?>',
				rules: {
					client_id: {
						required: true
					},
					client_secret: {
						required: true
					}
				},
				submitHandler: function (form) {
					form.submit();
				}
			});

		});
	</script>
</div>