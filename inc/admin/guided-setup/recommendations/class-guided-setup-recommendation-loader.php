<?php
defined( 'ABSPATH' ) || exit;

/**
 * Load recommendation filter map files by type and value slug.
 */
class FluidCheckout_Guided_Setup_Recommendation_Loader {

	/**
	 * Base directory for filter map files.
	 *
	 * @var string
	 */
	protected $filters_dir = '';



	/**
	 * Constructor.
	 *
	 * @param string $filters_dir Absolute path to the filters directory.
	 */
	public function __construct( $filters_dir = '' ) {
		// Maybe use the default filters directory under this file
		if ( '' === $filters_dir ) {
			$filters_dir = trailingslashit( dirname( __FILE__ ) ) . 'filters';
		}

		$this->filters_dir = untrailingslashit( $filters_dir );
	}



	/**
	 * Get the absolute path for a filter map file.
	 *
	 * @param string $type  Filter type (folder name).
	 * @param string $value Value slug (file name without .php).
	 *
	 * @return string
	 */
	public function get_filter_file_path( $type, $value ) {
		$type  = $this->sanitize_slug( $type );
		$value = $this->sanitize_slug( $value );

		// Bail if type or value is empty after sanitize
		if ( '' === $type || '' === $value ) {
			return '';
		}

		return $this->filters_dir . '/' . $type . '/' . $value . '.php';
	}



	/**
	 * Load a sparse option map for a filter type and value.
	 *
	 * Missing files return an empty array (not an error).
	 *
	 * @param string $type  Filter type.
	 * @param string $value Value slug.
	 *
	 * @return array
	 */
	public function load_filter_map( $type, $value ) {
		$path = $this->get_filter_file_path( $type, $value );

		// Bail if path could not be built
		if ( '' === $path ) {
			return array();
		}

		// Bail if file does not exist
		if ( ! is_readable( $path ) ) {
			return array();
		}

		$map = include $path;

		// Bail if file did not return an array
		if ( ! is_array( $map ) ) {
			return array();
		}

		return $map;
	}



	/**
	 * Sanitize a filter type or value slug for filesystem use.
	 *
	 * @param string $slug Raw slug.
	 *
	 * @return string
	 */
	public function sanitize_slug( $slug ) {
		$slug = strtolower( (string) $slug );
		$slug = preg_replace( '/[^a-z0-9\-_]/', '', $slug );

		return is_string( $slug ) ? $slug : '';
	}

}
