<?php
namespace DBisso\Service\AssetLoader;

use Symfony\Component\Yaml\Yaml;

/**
 * Add functionality to the front end.
 */
class AssetLoader {
	private $manifest_path;

	private $conditions = array();

	public function __construct( $manifest_path ) {
		if ( ! is_file( $manifest_path ) ) {
			throw new \InvalidArgumentException( sprintf( __( 'Asset manifest cannot be found at %s', 'dbisso-asset-loader' ), $manifest_path ) );
		}

		$this->manifest_path = $manifest_path;

		$this->conditions = array(
			'scripts' => array(),
			'styles' => array()
		);
	}

	private function get_manifest() {
		$manifest = Yaml::parse( $this->manifest_path );

		if ( $manifest ) {
			$manifest['base_urls'] = array_merge(
				array(
					'scripts' => trailingslashit( get_template_directory_uri() ),
					'styles' => trailingslashit( get_template_directory_uri() ),
				),
				is_array( $manifest['base_urls'] ) ? $manifest['base_urls'] : array()
			);
		}

		return $manifest;
	}


	public function load() {
		$manifest = $this->get_manifest();

		foreach ( $manifest['scripts'] as $handle => $script ) {
			$script = new ScriptAsset( $handle, $script );
			$script->set_base_url( $manifest['base_urls']['scripts'] );

			if ( false === $this->check_conditions( 'script', $handle, $script ) ) {
				continue;
			}

			// Middleware
			$script = $this->version_with_mtime( $script );
			$script = $this->place_in_footer( $script );
			$script = $this->set_theme_script_path( $script );
			$script = $this->cachebust_file_name( $script );

			wp_enqueue_script(
				$handle,
				$script['src'],
				$script['deps'],
				$script['version'],
				$script['footer']
			);

			if ( ! empty( $script['data'] ) ) {
				foreach ( $script['data'] as $key => $value ) {
					wp_script_add_data( $handle, $key, $value );
				}
			}
		}

		foreach ( $manifest['styles'] as $handle => $style ) {
			$style = new StyleAsset( $handle, $style );
			$style->set_base_url( $manifest['base_urls']['styles'] );

			if ( false === $this->check_conditions( 'style', $handle, $script ) ) {
				continue;
			}

			$style = $this->version_with_mtime( $style );
			$style = $this->set_theme_style_path( $style );
			$style = $this->cachebust_file_name( $style );

			wp_enqueue_style(
				$handle,
				$style['src'],
				$style['deps'],
				$style['version'],
				$style['media']
			);

			if ( ! empty( $style['data'] ) ) {
				foreach ( $style['data'] as $key => $value ) {
					wp_style_add_data( $handle, $key, $value );
				}
			}
		}
	}

	public function add_script_condition( $handle, $condition ) {
		if ( ! is_callable( $condition ) ) {
			throw new \Exception( __( 'Script conditions must be callable.', 'dbisso-asset-loader' ) );
		}

		$this->add_condition( 'script', $handle, $condition );
	}

	public function add_style_condition( $handle, $condition ) {
		if ( ! is_callable( $condition ) ) {
			throw new \Exception( __( 'Style conditions must be callable.', 'dbisso-asset-loader' ) );
		}

		$this->add_condition( 'style', $handle, $condition );
	}

	private function add_condition( $type, $handle, $condition ) {
		if ( ! is_array( $this->conditions[ $type ][ $handle ] ) ) {
			$this->conditions[ $type ][ $handle ] = array();
		}

		$this->conditions[ $type ][ $handle ][] = $condition;
	}

	private function get_conditions( $type, $handle ) {
		if ( isset( $this->conditions[ $type ][ $handle ] ) && is_array( $this->conditions[ $type ][ $handle ] ) ) {
			return $this->conditions[ $type ][ $handle ];
		}

		return array();
	}

	private function check_conditions( $type, $handle, AssetInterface $asset ) {
		foreach ( $this->get_conditions( $type, $handle ) as $condition ) {
			if ( false === $condition( $asset ) ) {
				return false;
			}
		}

		return true;
	}

	private function get_file_path( AssetInterface $asset ) {
		$file_path = null;

		if ( $asset instanceof ScriptAsset ) {
			$file_path = trailingslashit( get_template_directory() ) . "js/{$asset['src']}";
		}

		if ( $asset instanceof StyleAsset ) {
			$file_path = trailingslashit( get_template_directory() ) . "{$asset['src']}";
		}

		return $file_path;
	}

	/**
	 * Defaults the version parameter for scripts to the script file's mtime.
	 *
	 * @param  array  $scripts The script to enqueue
	 */
	private function version_with_mtime( AssetInterface $asset ) {
		// Version our added scripts by the file mod time
		if ( defined( 'SCRIPT_VERSION_MTIME' ) && true === SCRIPT_VERSION_MTIME ) {
			if ( is_null( $asset['version'] ) ) {
				$file_path = $this->get_file_path( $asset );

				if ( file_exists( $file_path ) ) {
					// Use file mod time for timestamp
					$asset['version'] = filemtime( $file_path );
				}
			}

			if ( ! is_null( $asset['version'] ) ) {
				// Replace . in version strings with _
				$asset['version'] = strtr( $asset['version'], '.', '_' );
			}
		}

		return $asset;
	}

	/**
	 * Places the script in the footer by default.
	 *
	 * @param  array  $script A script
	 * @return array
	 */
	private function place_in_footer( AssetInterface $script ) {
		// Place in footer by default
		if ( is_null( $script['footer'] ) ) {
			$script['footer'] = true;
		}

		return $script;
	}

	/**
	 * Sets non-remote scripts to load from the stylesheet directory.
	 *
	 * @param array $script A script
	 * @return array
	 */
	private function set_theme_script_path( AssetInterface $script ) {
		$is_remote = preg_match( '~^(https?:)?//~', $script['src'] ) !== 0;

		$script['src'] = $is_remote ? $script['src'] : get_stylesheet_directory_uri() . '/js/' . $script['src'];

		return $script;
	}

	/**
	 * Sets non-remote styles to load from the stylesheet directory.
	 *
	 * @param array $script A script
	 * @return array
	 */
	private function set_theme_style_path( AssetInterface $style ) {
		$is_remote = preg_match( '~^(https?:)?//~', $style['src'] ) !== 0;

		$style['src'] = $is_remote ? $style['src'] : trailingslashit( get_stylesheet_directory_uri() ) . $style['src'];

		return $style;
	}

	/**
	 * Replaces query arg based cachebusting with filename-based cachebusting.
	 * Eg: /js/myscript.js?v=2.42 becomes /js/myscript.2_42.js
	 *
	 * Requires CACHEBUST_FILENAME env var to be set to 'on' as rewrite rules
	 * need to be set up correctly on the server side for this to work.
	 *
	 * @param  string $src    The script source URL
	 * @param  script $handle The script handle
	 * @return string         The modified source URL
	 */
	private function cachebust_file_name( AssetInterface $asset ) {
		// If filename-based cachebusting is enabled in the .htaccess file
		// then remove the version from the query string and insert it before
		// the file extension.
		if ( function_exists( 'getenv' ) && 'on' === getenv( 'CACHEBUST_FILENAME' ) ) {
			if ( $asset['version'] ) {
				$asset['version'] = strtr( $asset['version'], '.', '_' );

				$parsed_url = parse_url( $asset['src'] );
				if ( $parsed_url['query'] ) {
					unset( $parsed_url['query'] );
				}

				$extension = pathinfo( $asset['src'], PATHINFO_EXTENSION );

				$parsed_url['path'] = preg_replace( "/\.{$extension}$/", ".{$asset['version']}.{$extension}", $parsed_url['path'] );

				$asset['src'] = $parsed_url['scheme'] . "://{$parsed_url['host']}{$parsed_url['path']}";

				$asset['version'] = null;
			}
		}

		return $asset;
	}
}