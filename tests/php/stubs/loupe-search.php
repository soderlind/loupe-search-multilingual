<?php
/**
 * Test doubles for the Loupe Search classes the add-on calls. They are static and
 * cannot be stubbed by Brain Monkey.
 */

namespace Soderlind\Plugin\LoupeSearch;

// phpcs:disable Generic.Files.OneObjectStructurePerFile

class WP_Loupe_Partitions {

	/** @var array<string,list<string>> Post type => partitions. */
	public static array $partitions = [];

	public static function all( string $post_type ): array {
		return self::$partitions[ $post_type ] ?? [ '' ];
	}

	public static function language( string $partition, string $post_type ): string {
		return apply_filters( 'loupe_search_partition_language', 'en', $partition, $post_type );
	}

	public static function sanitize( $partition ): string {
		return is_scalar( $partition ) ? (string) preg_replace( '/[^a-z0-9_-]/', '', strtolower( (string) $partition ) ) : '';
	}
}

class WP_Loupe_Factory {

	public static function resolve_language( string $locale ): string {
		return strtolower( (string) strtok( $locale, '_' ) );
	}
}

class WP_Loupe_Index_Notice {

	public static int $flushes = 0;

	public static function flush(): void {
		++self::$flushes;
	}
}

class WP_Loupe_Utils {

	/** @var list<string> */
	public static array $post_types = [ 'post' ];

	public static function get_indexed_post_types(): array {
		return self::$post_types;
	}
}

class WP_Loupe_Indexer {

	/** @var list<int> Post IDs passed to add(). */
	public static array $added = [];

	public function __construct( $post_types = null, bool $register_hooks = true ) {}

	public function add( int $post_id, \WP_Post $post, bool $update ): void {
		self::$added[] = $post_id;
	}
}
