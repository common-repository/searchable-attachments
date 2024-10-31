<?php
defined( 'WPINC' ) OR exit;

/**
 * Created by PhpStorm.
 * User: Dan
 * Date: 12/22/2015
 * Time: 3:45 AM
 */
abstract class SA_AbstractFileFilter {

	/**
	 * @var SA_AbstractFileFilter[] The singleton instances, keyed by class name.
	 */
	protected static $instances = array();

	/**
	 * Blocks external instantiation. File filters only require a singleton instance.
	 */
	protected function __construct() {
	}

	/**
	 * Initialize the thumber class for use in thumbnail generation.
	 *
	 * @return SA_AbstractFileFilter The instance for the calling class.
	 */
	public static function init() {
		$class = get_called_class();
		if ( ! isset( self::$instances[$class] ) ) {
			try {
				self::$instances[$class] = new static();
				add_action( 'sa_file_filters', array( $class, 'fileFilterFilter' ), 0 );
			} catch ( Exception $e ) {
				// TODO
			}
		}

		return isset( self::$instances[$class] ) ? self::$instances[$class] : null;
	}

	/**
	 * Adds the singleton instance for the calling class as a file filter.
	 *
	 * @param $thumbers SA_AbstractFileFilter[] The file filters being filtered.
	 * @return SA_AbstractFileFilter[] The filtered file filters.
 */
	public static function fileFilterFilter( $thumbers ) {
		$thumbers[] = static::$instances[get_called_class()];
		return $thumbers;
	}

	/**
	 * Takes an attachment ID and returns the attachment file contents filtered
	 * to include only plain text.
	 *
	 * @param $id int The attachment ID to be filtered.
	 * @return string The filtered plaintext file contents.
	 */
	public abstract function filter( $id );

	/**
	 * @return string[] The extensions supported by this filter.
	 */
	protected abstract function getExtensions();

	/**
	 * @return int An integer from 0 to 100. Higher priorities will be attempted before lower priority thumbers.
	 */
	public abstract function getPriority();

	/**
	 * @param int $ID The attachment ID to retrieve thumbnail from.
	 * @return bool Whether the attachment is supported by this thumber.
	 */
	public function supportsAttachment( $ID ) {
		return in_array( self::getAttachmentExt( $ID ), $this->getExtensions() );
	}

	/**
	 * Used in sorting an array of filters by priority.
	 *
	 * @param $t1 SA_AbstractFileFilter First thumber.
	 * @param $t2 SA_AbstractFileFilter Second thumber.
	 * @return int Negative if $t1 has a higher priority, positive if $t1 has a lower priority.
	 */
	public static function cmpFilterByPriority( $t1, $t2 ) {
		return $t2->getPriority() - $t1->getPriority();
	}

	/**
	 * @param $ID int The attachment ID.
	 * @return bool|string The attachment extension on success, false on failure.
	 */
	protected static function getAttachmentExt( $ID ) {
		return self::getExt( get_attached_file( $ID ) );
	}

	/**
	 * @param $ID int The attachment ID.
	 * @return string|null The attachment content on success, null on failure.
	 */
	protected static function getAttacchmentContents( $ID ) {
		$ret = null;

		if ( ( $path = get_attached_file( $ID ) ) && ( $data = @file_get_contents( $path ) ) ) {
			$ret = $data;
		}

		return $ret;
	}

	/**
	 * Formerly achieved with wp_check_filetype(), but it was only returning
	 * valid results if the active user had permission to upload the given filetype.
	 *
	 * @param string $filename Name of the file to get extension from.
	 *
	 * @return bool|string Returns the file extension on success, false on failure.
	 */
	protected static function getExt( $filename ) {
		if ( $ext = pathinfo( $filename, PATHINFO_EXTENSION ) ) {
			$res = preg_grep( '/^(?:.*\|)?' . $ext . '(?:\|.*)?$/i', self::getAllExts() );
			$res = reset( $res );
			if ( $res === false ) {
				$ext = false;
			}
		}

		if ( ! $ext && ( $info = getimagesize( $filename ) ) && ( $ext = image_type_to_extension( $info[2], false ) ) ) {
			return $ext;
		}

		return $ext;
	}

	/**
	 * Addresses issues with getting a complete list of supported MIME types as
	 * described in this issue: https://core.trac.wordpress.org/ticket/32544
	 * @return string[] Contains all MIME types supported by WordPress, including custom types added by plugins.
	 */
	protected static function getAllExts() {
		return array_keys( array_merge( wp_get_mime_types(), get_allowed_mime_types() ) );
	}
}