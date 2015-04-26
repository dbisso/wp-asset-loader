<?php
namespace DBisso\Service\AssetLoader;

/**
 * A stylesheet asset
 */
class StyleAsset extends AbstractAsset {
	/**
	 * Media query for the stylesheet.
	 *
	 * @var string
	 */
	private $media;

	public function __construct( $handle, array $asset ) {
		parent::__construct( $handle, $asset );
		$this->media = $asset['media'];
		$this->data  = $asset['data'];
	}

	public function get_media() {
		return $this->media;
	}

	public function get_data() {
		if ( $this->data ) {
			return $this->data;
		}

		return array();
	}
}