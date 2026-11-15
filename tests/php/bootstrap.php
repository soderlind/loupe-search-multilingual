<?php
/**
 * PHPUnit bootstrap: unit tests with Brain Monkey, no WordPress.
 */

define( 'ABSPATH', __DIR__ . '/' );

require_once dirname( __DIR__, 2 ) . '/vendor/autoload.php';
require_once __DIR__ . '/stubs/loupe-search.php';

if ( ! class_exists( 'WP_Post' ) ) {
	// phpcs:ignore Generic.Files.OneObjectStructurePerFile
	final class WP_Post {
		public function __construct( public int $ID = 0, public string $post_type = 'post' ) {}
	}
}

if ( ! class_exists( 'WP_REST_Request' ) ) {
	// phpcs:ignore Generic.Files.OneObjectStructurePerFile
	final class WP_REST_Request {
		public function __construct( private string $route = '', private array $params = [] ) {}
		public function get_route(): string {
			return $this->route;
		}
		public function get_param( string $key ) {
			return $this->params[ $key ] ?? null;
		}
	}
}
