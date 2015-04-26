<?php
namespace DBisso\Service\AssetLoader;

abstract class AbstractAsset implements AssetInterface, \ArrayAccess {
	protected $handle;

	protected $src;

	protected $deps = array();

	protected $version;

	protected $data = array();

	protected $base_url;

	public function __construct( $handle, array $asset ) {
		$this->handle  = $handle;
		$this->src     = $asset['src'];
		$this->deps    = $asset['deps'];
		$this->version = $asset['version'];
		$this->data    = $asset['data'];
	}

	public function get_handle() {
		return $this->handle;
	}

	public function get_src() {
		return $this->src;
	}

	public function get_deps() {
		if ( $this->deps ) {
			return $this->deps;
		}

		return array();
	}

	public function get_version() {
		return $this->version;
	}

	public function get_data() {
		if ( is_array( $this->data ) ) {
			return $this->data;
		}

		return array();
	}

	public function get_base_url() {
		return $this->base_url;
	}

	public function set_base_url( $url ) {
		$this->base_url = $url;
	}


	public function offsetSet( $offset, $value ) {
		$this->$offset = $value;
		// throw new \Exception( 'Assets properties cannot be changed' );
	}

	public function offsetExists($offset) {
		return isset( $this->$offset );
	}

	public function offsetUnset($offset) {
		$this->$offset = null;
		// throw new \Exception( 'Assets properties cannot be removed' );
	}

	public function offsetGet($offset) {
		return isset( $this->$offset ) ? $this->$offset : null;
	}
}