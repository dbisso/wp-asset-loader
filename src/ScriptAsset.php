<?php
namespace DBisso\Service\AssetLoader;

/**
 * A script asset.
 */
class ScriptAsset extends AbstractAsset {
	/**
	 * Should the script be placed in the footer.
	 *
	 * @var boolean
	 */
	protected $footer;

	public function __construct( $handle, array $asset ) {
		parent::__construct( $handle, $asset );
		$this->footer = $asset['footer'];
	}

	public function get_footer() {
		return $this->footer;
	}
}