<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ForDocBase {

	/**
	 * Function to get key of the Formidable field from field id
	 *
	 * @uses globals $frmdb, $wpdb
	 * @param $field_id - Integer with field ID
	 *
	 * @return null|string
	 */
	function get_key_from_id_field( $field_id ) {
		global $frmdb, $wpdb;
		if ( !empty($field_id) && is_numeric($field_id)) {
			$key_from_id = $wpdb->get_var($wpdb->prepare("SELECT field_key from $frmdb->fields WHERE id=%s", $field_id));
		}
		return $key_from_id;
	}

	/**
	 * Function to get key of the Formidable field from field id
	 *
	 * @uses globals $frmdb, $wpdb
	 * @param $field_id - Integer with field ID
	 *
	 * @return null|string
	 */
	function get_field_type( $field_id ) {
		global $frmdb, $wpdb;
		if ( !empty($field_id) && is_numeric($field_id)) {
			$type = $wpdb->get_var($wpdb->prepare("SELECT type from $frmdb->fields WHERE id=%s", $field_id));
		}
		return $type;
	}
}