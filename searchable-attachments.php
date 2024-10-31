<?php
defined( 'WPINC' ) OR exit;

/*
  Plugin Name: Searchable Attachments
  Plugin URI: http://wordpress.org/extend/plugins/searchable-attachments/
  Description: Extend WordPress searches to include the contents of your attachments.
  Version: 0.2
  Author: Dan Rossiter
  Author URI: http://danrossiter.org/
  License: GPLv3
  Text Domain: searchable-attachments
 */

if ( version_compare( PHP_VERSION, '5.3', '<' ) ) {
	add_action( 'admin_notices', 'sa_php_lt_three' );
	function sa_php_lt_three() { ?>
		<div class="error"><p>
			<?php printf( __( 'Searchable Attachments requires PHP &ge; 5.3. Your server is running version %s.', 'searchable-attachments' ), PHP_VERSION ); ?>
		</p></div>
	<?php }

	return;
}

define( 'SA_PATH', plugin_dir_path( __FILE__ ) );

include_once SA_PATH . 'inc/filters/class-abstract-file-filter.php';

register_activation_hook( __FILE__, array( 'SearchableAttachments', 'activate' ) );
register_uninstall_hook( __FILE__, array( 'SearchableAttachments', 'uninstall' ) );

add_action( 'add_attachment', array( 'SearchableAttachments', 'setAttachmentContent' ) );

class SearchableAttachments {

	/**
	 * @const meta_key identifying the parsed content for the attachment.
	 */
	const PostMetaKey = 'post_content';

	/**
	 * @param $id int
	 */
	public static function setAttachmentContent( $id ) {
		$content = null;
		foreach ( self::getFileFilters() as $filter ) {
			if ( $content = $filter->filter( $id ) ) {
				break;
			}
		}

		if ( $content ) {
			add_post_meta( $id, self::PostMetaKey, $content, true );
		}
	}

	/**
	 * Activate Searchable Attachments.
	 */
	public static function activate() {
		error_log( "activating" );
		global $wpdb;
		$query = "SELECT DISTINCT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_wp_attached_file'";
		foreach ( $wpdb->get_col( $query ) as $id ) {
			self::setAttachmentContent( $id );
		}
	}

	/**
	 * Uninstall Searchable Attachments.
	 */
	public static function uninstall() {
		error_log( "uninstalling" );
		global $wpdb;
		$wpdb->delete( $wpdb->postmeta, array( 'meta_key' => self::PostMetaKey ) );
	}

	/**
	 * @var SA_AbstractFileFilter[]
	 */
	private static $filters;

	/**
	 * @return SA_AbstractFileFilter[]
	 */
	private static function getFileFilters(  ) {
		if ( !isset( self::$filters ) ) {
			self::$filters = apply_filters( 'sa_file_filters', array() );
			self::$filters = array_filter( self::$filters, array( __CLASS__, 'isFilter' ) );
			usort( self::$filters, array( 'SA_AbstractFileFilter', 'cmpFilterByPriority' ) );
		}

		return self::$filters;
	}

	/**
	 * @param $maybe_filter mixed The value to be tested.
	 *
	 * @return bool Whether the given value is a valid file filter.
	 */
	private static function isFilter( $maybe_filter ) {
		$ret = is_a( $maybe_filter, 'SA_AbstractFileFilter' );
		if ( !$ret ) {
			// TODO
		}

		return $ret;
	}
}

foreach ( glob( SA_PATH . 'inc/filters/*.php', GLOB_NOSORT ) as $file ) {
	/** @noinspection PhpIncludeInspection */
	include_once $file;
}