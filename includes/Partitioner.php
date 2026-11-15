<?php
/**
 * Maps languages to Loupe Search index partitions.
 *
 * @package Soderlind\Plugin\LoupeSearchMultilingual
 */

namespace Soderlind\Plugin\LoupeSearchMultilingual;

use Soderlind\Plugin\LoupeSearch\WP_Loupe_Factory;
use Soderlind\Plugin\LoupeSearch\WP_Loupe_Partitions;

defined( 'ABSPATH' ) || exit;

/**
 * Hooks Loupe Search's partition filters: one partition per language.
 */
final class Partitioner {

	/**
	 * Partition name => language code, default language first. Cached once non-empty.
	 *
	 * @var array<string,string>|null
	 */
	private ?array $partitions = null;

	public function __construct( private Language_Adapter $adapter ) {}

	public function register(): void {
		add_filter( 'loupe_search_partitions', [ $this, 'filter_partitions' ], 10, 2 );
		add_filter( 'loupe_search_post_partition', [ $this, 'filter_post_partition' ], 10, 2 );
		add_filter( 'loupe_search_partition_language', [ $this, 'filter_partition_language' ], 10, 3 );
		add_filter( 'loupe_search_partition_label', [ $this, 'filter_partition_label' ], 10, 3 );
		add_filter( 'loupe_search_query_partitions', [ $this, 'filter_query_partitions' ], 10, 2 );
	}

	/**
	 * One partition per language for translated post types.
	 *
	 * @param mixed  $partitions Partitions.
	 * @param string $post_type  Post type.
	 * @return mixed
	 */
	public function filter_partitions( $partitions, $post_type ) {
		if ( ! is_string( $post_type ) || ! $this->adapter->is_translated_post_type( $post_type ) ) {
			return $partitions;
		}
		$map = $this->partition_map();
		return empty( $map ) ? $partitions : array_keys( $map );
	}

	/**
	 * A post's partition is its language; posts without one go to the default language.
	 *
	 * @param mixed    $partition Partition.
	 * @param \WP_Post $post      Post.
	 * @return mixed
	 */
	public function filter_post_partition( $partition, $post ) {
		if ( ! $post instanceof \WP_Post || ! $this->adapter->is_translated_post_type( (string) $post->post_type ) ) {
			return $partition;
		}
		$language = $this->adapter->post_language( $post ) ?? $this->adapter->default_language();
		return '' === $language ? $partition : WP_Loupe_Partitions::sanitize( $language );
	}

	/**
	 * Stemming and stop words follow the language's locale.
	 *
	 * @param mixed  $language  Loupe language code.
	 * @param string $partition Partition.
	 * @param string $post_type Post type.
	 * @return mixed
	 */
	public function filter_partition_language( $language, $partition, $post_type ) {
		$locale = $this->language_info( (string) $partition )[ 'locale' ] ?? '';
		return '' === $locale ? $language : WP_Loupe_Factory::resolve_language( $locale );
	}

	/**
	 * Dashboard label: the language name.
	 *
	 * @param mixed  $label     Label.
	 * @param string $partition Partition.
	 * @param string $post_type Post type.
	 * @return mixed
	 */
	public function filter_partition_label( $label, $partition, $post_type ) {
		$name = $this->language_info( (string) $partition )[ 'name' ] ?? '';
		return '' === $name ? $label : $name;
	}

	/**
	 * Search only the current language's partition. Without a current language
	 * (all languages in the admin, WP-CLI, cron) every partition is searched.
	 *
	 * @param mixed  $partitions Partitions.
	 * @param string $post_type  Post type.
	 * @return mixed
	 */
	public function filter_query_partitions( $partitions, $post_type ) {
		$current = $this->adapter->current_language();
		if ( null === $current || ! is_array( $partitions ) ) {
			return $partitions;
		}
		$current = WP_Loupe_Partitions::sanitize( $current );
		return in_array( $current, $partitions, true ) ? [ $current ] : $partitions;
	}

	/**
	 * Partition name => language code, default language first.
	 *
	 * @return array<string,string>
	 */
	public function partition_map(): array {
		if ( null !== $this->partitions ) {
			return $this->partitions;
		}

		$codes   = array_keys( $this->adapter->languages() );
		$default = $this->adapter->default_language();
		if ( in_array( $default, $codes, true ) ) {
			$codes = array_merge( [ $default ], array_diff( $codes, [ $default ] ) );
		}

		$map = [];
		foreach ( $codes as $code ) {
			$partition = WP_Loupe_Partitions::sanitize( $code );
			if ( '' !== $partition && ! isset( $map[ $partition ] ) ) {
				$map[ $partition ] = (string) $code;
			}
		}

		// Some multilingual plugins load their languages late; retry until they do.
		if ( ! empty( $map ) ) {
			$this->partitions = $map;
		}
		return $map;
	}

	/**
	 * Locale and name of the language behind a partition.
	 *
	 * @param string $partition Partition.
	 * @return array{locale:string,name:string}|null
	 */
	private function language_info( string $partition ): ?array {
		$code = $this->partition_map()[ $partition ] ?? null;
		return null === $code ? null : ( $this->adapter->languages()[ $code ] ?? null );
	}
}
