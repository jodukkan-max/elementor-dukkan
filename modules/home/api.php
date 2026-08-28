<?php
namespace Elementor\Modules\Home;

use Elementor\Includes\EditorAssetsAPI;
use Elementor\Modules\Home\Classes\Transformations_Manager;

class API {
	protected EditorAssetsAPI $editor_assets_api;

	public function __construct( EditorAssetsAPI $editor_assets_api ) {
		$this->editor_assets_api = $editor_assets_api;
	}

	public function get_home_screen_items( $force_request = false ): array {
		return [];
	}

	private function transform_home_screen_data( $json_data ): array {
		$transformers = new Transformations_Manager( $json_data );

		return $transformers->run_transformations();
	}
}
