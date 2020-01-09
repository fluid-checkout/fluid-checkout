<?php
/**
 * Plugin Updater
 *
 * Allow auto update of plugins hosted on BitBucket.
 */
if ( ! class_exists( 'Fluidweb_PluginUpdater_Bitbucket' ) ) {
	class Fluidweb_PluginUpdater_Bitbucket {
		private $slug;
		private $pluginData;
		private $repo;
		private $pluginFile;
		private $bitbucketAPIResult;
		private $bitbucketUsername;
		private $bitbucketPassword;
		private $allow_beta_updates;
	
	
	
		/**
		 * Construct a new instance of plugin updater
		 *
		 * @param		String		$pluginFile				Path to plugin file
		 * @param		String		$repo					URL to BitBucket repository
		 * @param		String		$bbUsername				BitBucket user name
		 * @param		String		$bbPassword				BitBucket password
		 * @param		Boolean		$allowBetaUpdates		Allow update to beta versions
		 */
		function __construct( $pluginFile, $repo, $bbUsername, $bbPassword, $allowBetaUpdates ) {
			add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'setTransient' ) );
			add_filter( 'plugins_api', array( $this, 'setPluginInfo' ), 10, 3 );
			add_filter( 'upgrader_post_install', array( $this, 'postInstall' ), 10, 3 );
			add_filter( 'http_request_args', array( $this, 'addAuthRequestArgs' ), 10, 2);
	
			$this->pluginFile = $pluginFile;
			$this->repo = $repo;
			$this->bitbucketUsername = $bbUsername;
			$this->bitbucketPassword = $bbPassword;
			$this->allow_beta_updates = $allowBetaUpdates;
		}
	
	
	
		/**
		 * Get information regarding the current plugin version
		 */
		private function initPluginData() {
			$this->slug = plugin_basename( $this->pluginFile );
			$this->pluginData = get_plugin_data( $this->pluginFile );
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
		 * Get information regarding plugin releases from repository
		 */
		private function getRepoReleaseInfo() {
			// Call http request only once
			if ( ! empty( $this->bitbucketAPIResult ) ) { return; }
	
			$url = sprintf( 'https://api.bitbucket.org/2.0/repositories/%s/refs/tags?sort=-target.date', $this->repo );

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
		 * Push in plugin version information to get the update notification
		 */
		public function setTransient( $transient ) {    
			// Get plugin & git release information
			$this->initPluginData();
			$this->getRepoReleaseInfo();
			
			// Nothing found.
			if ( empty( $this->bitbucketAPIResult ) ) { return $transient; }
			
			$repo_version = ltrim( $this->bitbucketAPIResult->name, 'v' );
			
			// Check the versions if we need to do an update ( $repo_version > current version )
			$doUpdate = version_compare( $repo_version, $this->pluginData["Version"] );
	
			// Update the transient to include our updated plugin data
			if ( $doUpdate == 1 ) {
				$package = sprintf('https://bitbucket.org/%s/get/%s.zip', 
					$this->repo, 
					$this->bitbucketAPIResult->name
				);
			
				$obj = new \stdClass();
				$obj->slug = $this->slug;
				$obj->new_version = $repo_version;
				$obj->url = $this->pluginData["PluginURI"];
				$obj->package = $package;
				
				$transient->response[ $this->slug ] = $obj;
			}
	
			return $transient;
		}
	
	
	
		/**
		 * Get plugin readme-me file contents
		 */
		public function getReadmeFile() {
			$sha = 'HEAD';
	
			$url = sprintf( 'https://bitbucket.org/%s/raw/%s/readme.txt', 
				$this->repo, 
				$sha
			);
	
			$url = sprintf( 'https://api.bitbucket.org/2.0/repositories/%s/src/HEAD/readme.txt', $this->repo);
	
			$response = $this->callRepositoryAPI( $url );
	
			$decode = json_decode( $response );
	
			// No file found or other error.
			if ( $decode ) { return false; }
	
			return $response;
		}
	
	
	
		/**
		 * Add repository authentication request arguments when request targets the repository
		 */
		public function addAuthRequestArgs( $args, $url ) {
			if ( preg_match( '/bitbucket.org(.+)' . str_replace( '/', '\/', $this->repo ) . '/', $url ) ) {
				if ( empty($args['headers'] ) ) { $args['headers'] = array(); }
				$args['headers']['Authorization'] = 'Basic ' . base64_encode( $this->bitbucketUsername . ':' . $this->bitbucketPassword );
			}
			return $args;
		}
	
	
	
		/**
		 * Push in plugin version information to display in the details lightbox
		 */
		public function setPluginInfo( $res, $action, $args ) {
			$this->initPluginData();
	
			if ( $action == 'plugin_information' && $args->slug == $this->slug ) {
				$res = new \stdClass();
				$res->name = $this->pluginData['Name'];
				$res->slug = $this->slug;
	
				$changelog = 'No readme file present in repo.';
	
				$readme = $this->getReadmeFile();
				// TODO: See if parsedown is really necessary
				// if ($readme) {
				//   $Parsedown = new \Parsedown();
				//   $changelog = $Parsedown->text($readme);
				// }
	
				$res->sections = [
					'changelog' => $changelog,
				];
			}
	
			return $res;
		}
	
		
	
		/**
		 * Perform additional actions to successfully install our plugin
		 */
		public function postInstall( $true, $hook_extra, $result ) {
			// Get plugin information
			$this->initPluginData();
	
			// Remember if our plugin was previously activated
			$wasActivated = is_plugin_active( $this->slug );
	
			global $wp_filesystem;
			$pluginFolder = WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . dirname( $this->slug );
			$wp_filesystem->move( $result['destination'], $pluginFolder );
			$result['destination'] = $pluginFolder;
	
			// Re-activate plugin if needed
			if ( $wasActivated ) { $activate = activate_plugin( $this->slug ); }
	
			return $result;
		}
	}
}
