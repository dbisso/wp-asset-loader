<?php
namespace DBisso\Service\AssetLoader;

interface AssetInterface {
	public function __construct( $handle, array $asset );

	public function get_handle();

	public function get_src();

	public function get_deps();

	public function get_version();
}