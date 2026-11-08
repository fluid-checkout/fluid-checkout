<?php
defined( 'ABSPATH' ) || exit;

/**
 * Export REST API reference data for developer documentation (`fluid-checkout/v1`).
 *
 * Load this file only from the docs export CLI or PHPUnit export tests.
 * Do not require it from load_rest_api() or other production bootstrap.
 */
class FluidCheckout_RestApi_Documentation {

	/**
	 * Build the full documentation export document.
	 *
	 * @return  array
	 */
	public static function export() {
		$resources = array();

		/**
		 * Filter endpoint classes included in the REST API documentation export.
		 *
		 * Register callbacks only from export-only files (for example
		 * `inc/rest-api/export-documentation.php`), never from production bootstrap.
		 *
		 * @param string[] $endpoint_classes Fully qualified endpoint class names.
		 */
		$endpoint_classes = apply_filters( 'fc_rest_api_documentation_endpoint_classes', array() );
		if ( is_array( $endpoint_classes ) ) {
			// Iterate registered endpoint classes
			foreach ( $endpoint_classes as $class_name ) {
				// Skip invalid class names
				if ( ! is_string( $class_name ) || ! class_exists( $class_name ) ) {
					continue;
				}

				// Skip classes that are not fluid-checkout/v1 endpoints
				if ( ! is_subclass_of( $class_name, 'FluidCheckout_REST_API_V1_Endpoint' ) ) {
					continue;
				}

				// Skip classes without get_routes()
				if ( ! method_exists( $class_name, 'get_routes' ) ) {
					continue;
				}

				$resource = self::export_resource( $class_name );

				// Maybe append a valid resource
				if ( ! empty( $resource ) ) {
					$resources[] = $resource;
				}
			}
		}

		return array(
			'namespace'     => FluidCheckout_REST_API_V1_Endpoint::ROUTE_NAMESPACE,
			'baseUrl'       => '{site}/wp-json/' . FluidCheckout_REST_API_V1_Endpoint::ROUTE_NAMESPACE,
			'permissions'   => self::get_permissions_documentation(),
			'responseShape' => self::get_response_shape_documentation(),
			'errors'        => self::export_errors(),
			'resources'     => $resources,
		);
	}



	/**
	 * Export one endpoint class as a documentation resource.
	 *
	 * @param   string  $class_name  Endpoint class name.
	 *
	 * @return  array
	 */
	public static function export_resource( $class_name ) {
		$reflection = new ReflectionClass( $class_name );

		$resource_id = '';
		// Maybe use the endpoint documentation resource id
		if ( method_exists( $class_name, 'get_documentation_resource_id' ) ) {
			$resource_id = call_user_func( array( $class_name, 'get_documentation_resource_id' ) );
		}

		$resource_id = is_string( $resource_id ) ? sanitize_title( $resource_id ) : '';
		// Bail if the resource id is empty
		if ( '' === $resource_id ) {
			return array();
		}

		$title = $resource_id;
		// Maybe use the endpoint documentation title
		if ( method_exists( $class_name, 'get_documentation_title' ) ) {
			$maybe_title = call_user_func( array( $class_name, 'get_documentation_title' ) );
			// Maybe replace the default title
			if ( is_string( $maybe_title ) && '' !== trim( $maybe_title ) ) {
				$title = trim( $maybe_title );
			}
		}

		$docblock     = self::parse_docblock_summary( $reflection->getDocComment() );
		$description  = $docblock[ 'description' ];

		$error_ids = array();
		// Maybe collect resource wire error ids
		if ( method_exists( $class_name, 'get_documentation_error_ids' ) ) {
			$maybe_ids = call_user_func( array( $class_name, 'get_documentation_error_ids' ) );
			// Maybe keep a clean list of wire codes
			if ( is_array( $maybe_ids ) ) {
				$error_ids = array_values( array_filter( array_map( 'strval', $maybe_ids ) ) );
			}
		}

		$routes = call_user_func( array( $class_name, 'get_routes' ) );
		// Bail if routes are not an array
		if ( ! is_array( $routes ) ) {
			$routes = array();
		}

		$exported_routes = array();
		// Iterate routes from get_routes()
		foreach ( $routes as $route ) {
			// Skip invalid route definitions
			if ( ! is_array( $route ) ) {
				continue;
			}

			$exported_route = self::export_route( $route );
			// Maybe append a valid exported route
			if ( ! empty( $exported_route ) ) {
				$exported_routes[] = $exported_route;
			}
		}

		return array(
			'id'          => $resource_id,
			'title'       => $title,
			'description' => $description,
			'source'      => array(
				'class' => $class_name,
				'file'  => self::relative_source_path( $reflection->getFileName() ),
			),
			'errorIds'    => $error_ids,
			'routes'      => $exported_routes,
		);
	}



	/**
	 * Export a single route definition from get_routes().
	 *
	 * @param   array  $route  Route array.
	 *
	 * @return  array
	 */
	protected static function export_route( $route ) {
		// Bail if required route keys are missing
		if ( empty( $route[ 'endpoint' ] ) || empty( $route[ 'methods' ] ) || empty( $route[ 'callback' ] ) ) {
			return array();
		}

		$handler = self::callback_method_name( $route[ 'callback' ] );
		$args    = isset( $route[ 'args' ] ) && is_array( $route[ 'args' ] ) ? self::export_route_args( $route[ 'args' ] ) : array();

		$exported = array(
			'methods' => (string) $route[ 'methods' ],
			'path'    => self::humanize_route_path( (string) $route[ 'endpoint' ] ),
			'handler' => $handler,
			'args'    => $args,
		);

		// Maybe include documentation notes for the route
		if ( ! empty( $route[ 'documentation_notes' ] ) && is_string( $route[ 'documentation_notes' ] ) ) {
			$exported[ 'notes' ] = trim( $route[ 'documentation_notes' ] );
		}

		return $exported;
	}



	/**
	 * Export REST arg schemas for documentation (no callbacks).
	 *
	 * @param   array  $args  Route args.
	 *
	 * @return  array
	 */
	protected static function export_route_args( $args ) {
		$exported = array();

		// Iterate registered arg schemas
		foreach ( $args as $name => $schema ) {
			// Skip invalid arg definitions
			if ( ! is_string( $name ) || ! is_array( $schema ) ) {
				continue;
			}

			$entry = array(
				'type'     => isset( $schema[ 'type' ] ) ? (string) $schema[ 'type' ] : 'string',
				'required' => ! empty( $schema[ 'required' ] ),
			);

			// Maybe include the arg description
			if ( ! empty( $schema[ 'description' ] ) && is_string( $schema[ 'description' ] ) ) {
				$entry[ 'description' ] = $schema[ 'description' ];
			}

			$exported[ $name ] = $entry;
		}

		return $exported;
	}



	/**
	 * Resolve handler method name from a route callback.
	 *
	 * @param   mixed  $callback  Route callback.
	 *
	 * @return  string
	 */
	protected static function callback_method_name( $callback ) {
		// Maybe return the method name from a class callback
		if ( is_array( $callback ) && isset( $callback[1] ) && is_string( $callback[1] ) ) {
			return $callback[1];
		}

		// Maybe return a string callback as-is
		if ( is_string( $callback ) ) {
			return $callback;
		}

		return '';
	}



	/**
	 * Convert WP route regex segments to readable path placeholders.
	 *
	 * @param   string  $endpoint  Route endpoint path.
	 *
	 * @return  string
	 */
	protected static function humanize_route_path( $endpoint ) {
		$path = $endpoint;

		// Replace named capture groups with {name} placeholders
		if ( preg_match_all( '/\(\?P<([a-zA-Z0-9_]+)>[^)]+\)/', $path, $matches, PREG_SET_ORDER ) ) {
			// Iterate matched path segments
			foreach ( $matches as $match ) {
				$path = str_replace( $match[0], '{' . $match[1] . '}', $path );
			}
		}

		return $path;
	}



	/**
	 * Export merged error catalog (wire codes only, English messages at export time).
	 *
	 * @return  array<string, array{message: string, status: int}>
	 */
	protected static function export_errors() {
		$definitions = FluidCheckout_RestApi_Errors::get_definitions();
		$exported    = array();

		// Iterate catalog definitions and keep wire codes only
		foreach ( $definitions as $code => $definition ) {
			// Skip non-array definitions
			if ( ! is_array( $definition ) ) {
				continue;
			}

			// Skip internal or non-wire codes
			if ( ! is_string( $code ) || 0 !== strpos( $code, 'fc_api_' ) ) {
				continue;
			}

			$exported[ $code ] = array(
				'message' => isset( $definition[ 'message' ] ) ? (string) $definition[ 'message' ] : '',
				'status'  => isset( $definition[ 'status' ] ) ? (int) $definition[ 'status' ] : 400,
			);
		}

		return $exported;
	}



	/**
	 * Permissions summary for documentation.
	 *
	 * @return  string
	 */
	protected static function get_permissions_documentation() {
		return __(
			'Requests require a logged-in WordPress user. Omit user_id to use the current user\'s data, or pass user_id to target another account when the caller can edit that user (edit_user capability). Unauthenticated requests return fc_api_forbidden. An invalid user_id returns fc_api_user_not_found.',
			'fluid-checkout'
		);
	}



	/**
	 * Response envelope summary for documentation.
	 *
	 * @return  array{successWithData: string, successWithoutData: string, errors: string}
	 */
	protected static function get_response_shape_documentation() {
		return array(
			'successWithData'    => __( 'HTTP 200 with { "data": { ... } } when the handler returns a payload.', 'fluid-checkout' ),
			'successWithoutData' => __( 'HTTP 200 with { "success": true } when there is no payload (for example DELETE).', 'fluid-checkout' ),
			'errors'             => __( 'Failures return WordPress REST error JSON from WP_Error (code, message, data.status). Wire codes use the fc_api_* prefix.', 'fluid-checkout' ),
		);
	}



	/**
	 * Parse a class docblock into a description string.
	 *
	 * @param   string|false  $doc_comment  Docblock text.
	 *
	 * @return  array{description: string}
	 */
	protected static function parse_docblock_summary( $doc_comment ) {
		// Bail if the docblock is empty
		if ( ! is_string( $doc_comment ) || '' === trim( $doc_comment ) ) {
			return array(
				'description' => '',
			);
		}

		$lines = preg_split( '/\r\n|\r|\n/', $doc_comment );
		$body  = array();

		// Iterate docblock lines and keep description text
		foreach ( $lines as $line ) {
			$line = trim( $line );
			$line = preg_replace( '/^\/\*+\s?/', '', $line );
			$line = preg_replace( '/\*+\/?$/', '', $line );
			$line = preg_replace( '/^\*\s?/', '', $line );

			// Skip empty lines and tags
			if ( '' === $line || 0 === strpos( $line, '@' ) ) {
				continue;
			}

			$body[] = $line;
		}

		return array(
			'description' => trim( implode( ' ', $body ) ),
		);
	}



	/**
	 * Normalize an absolute file path to a plugin-relative path when possible.
	 *
	 * @param   string|false  $absolute_path  Absolute file path.
	 *
	 * @return  string
	 */
	protected static function relative_source_path( $absolute_path ) {
		// Bail if the path is empty
		if ( ! is_string( $absolute_path ) || '' === $absolute_path ) {
			return '';
		}

		$normalized = wp_normalize_path( $absolute_path );
		$marker     = '/inc/rest-api/';

		$position = strpos( $normalized, $marker );
		// Maybe return the path from the rest-api marker
		if ( false !== $position ) {
			return ltrim( substr( $normalized, $position ), '/' );
		}

		return basename( $normalized );
	}

}
