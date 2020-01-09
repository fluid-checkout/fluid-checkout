<?php
/**
 * Theme Updater
 *
 * Allow auto update of themes hosted on BitBucket.
 */
if ( ! class_exists( 'Fluidweb_ThemeUpdater_Bitbucket' ) ) {
	class Fluidweb_ThemeUpdater_Bitbucket {
		private $slug;
		private $themeData;
		private $repo;
		private $bitbucketAPIResult;
		private $bitbucketUsername;
		private $bitbucketPassword;
		private $allow_beta_updates;



		/**
		 * Initialize class
		 */
		function __construct( $slug, $repo, $bbUsername, $bbPassword, $allowBetaUpdates ) {
			// Bail if user doesn't have the right permissions
			if ( ! current_user_can( 'update_themes' ) ) { return; }

			add_filter( 'pre_set_site_transient_update_themes', array( $this, 'setTransient' ) );
			add_filter( 'upgrader_post_install', array( $this, 'postInstall' ), 5, 3 );
			add_filter( 'upgrader_process_complete', array( $this, 'updatesComplete' ), 5, 2 );
			add_filter( 'http_request_args', array($this, 'addAuthRequestArgs'), 10, 2);

			$this->slug = $slug;
			$this->repo = $repo;
			$this->bitbucketUsername = $bbUsername;
			$this->bitbucketPassword = $bbPassword;
			$this->allow_beta_updates = $allowBetaUpdates;
		}



		/**
		 * Get information regarding the current theme version
		 */
		private function initThemeData() {
			$this->themeData = wp_get_theme( $this->slug );
		}



		/**
		 * Call repository API for data
		 */
		private function callRepositoryAPI( $url ) {
			$process = curl_init( $url );
			curl_setopt( $process, CURLOPT_USERPWD, sprintf( '%s:%s', $this->bitbucketUsername, $this->bitbucketPassword ) );
			curl_setopt( $process, CURLOPT_RETURNTRANSFER, TRUE );
			$response = curl_exec( $process );
			curl_close( $process );

			return $response;
		}



		/**
		 * Get information regarding theme releases from repository
		 */
		private function getRepoReleaseInfo() {
			// Only do this once
			if ( ! empty( $this->bitbucketAPIResult ) ) { return; }

			$url = sprintf('https://api.bitbucket.org/2.0/repositories/%s/refs/tags?sort=-target.date', $this->repo);

			// Filter OUT beta and development versions
			if ( strval( $this->allow_beta_updates ) !== 'true' ) { $url .= '&q=%28name%21%7E%22beta%22+AND+name%21%7E%22dev%22%29'; }

			$response = $this->callRepositoryAPI( $url );

			if ( $response ) {
				$data = json_decode( $response );
				if ( isset( $data, $data->values ) && is_array( $data->values ) ) {
					$tag = reset( $data->values );
					if ( isset( $tag->name ) ) { $this->bitbucketAPIResult = $tag; }
				}
			}
		}

		

		/**
		 * Push in theme version information to get the update notification
		 */
		public function setTransient( $transient ) {    
			// Get theme & git release information
			$this->initThemeData();
			$this->getRepoReleaseInfo();

			// Nothing found.
			if ( empty( $this->bitbucketAPIResult ) ) { return $transient; }

			$repo_version = ltrim( $this->bitbucketAPIResult->name, 'v' );

			// Check the versions if we need to do an update
			$doUpdate = version_compare( $repo_version, $this->themeData->get('Version') );

			// Update the transient to include our updated plugin data
			if ( $doUpdate == 1 ) {
				$package = sprintf( 'https://bitbucket.org/%s/get/%s.zip', 
					$this->repo, 
					$this->bitbucketAPIResult->name
				);
			
				$obj = array(
					'slug' => $this->slug,
					'new_version' => $repo_version,
					'url' => $this->themeData->get('ThemeURI'),
					'package' => $package,
				);
				$transient->response[ $this->slug ] = $obj;
			}

			return $transient;
		}



		/**
		 * Add repository authentication request arguments when request targets the repository
		 */
		public function addAuthRequestArgs( $args, $url ) {
			if ( preg_match( '/bitbucket.org(.+)' . str_replace( '/', '\/', $this->repo ) . '/', $url ) ) {
				if ( empty( $args['headers'] ) ) { $args['headers'] = array(); }
				$args['headers']['Authorization'] = 'Basic ' . base64_encode( $this->bitbucketUsername . ':' . $this->bitbucketPassword );
			}
			return $args;
		}



		/**
		 * Perform additional actions to successfully install our theme
		 */
		public function postInstall( $true, $hook_extra, $result ) {
			global $wp_filesystem;
			$themeFolder = get_theme_root() . DIRECTORY_SEPARATOR . $this->slug;
			$wp_filesystem->move( $result['destination'], $themeFolder );
			$result['destination'] = $themeFolder;
			$result['destination_name'] = $this->slug;

			return $result;
		}



		/**
		 * Perform additional actions after update completes
		 */
		public function updatesComplete( $upgrader, $hook_extra ) {
			switch_theme( $this->slug );
		}
	}
}