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
