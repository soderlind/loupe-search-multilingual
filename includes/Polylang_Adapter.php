<?php
/**
 * Polylang adapter.
 *
 * @package Soderlind\Plugin\LoupeSearchMultilingual
 */

namespace Soderlind\Plugin\LoupeSearchMultilingual;

defined( 'ABSPATH' ) || exit;

/**
 * Reads languages from Polylang's public API.
 */
final class Polylang_Adapter implements Language_Adapter {

	/**
	 * Whether Polylang is active.
	 */
	public static function is_available(): bool {
		return function_exists( 'pll_languages_list' ) && function_exists( 'pll_get_post_language' );
	}

	public function languages(): array {
		$slugs   = (array) pll_languages_list( [ 'fields' => 'slug' ] );
		$locales = (array) pll_languages_list( [ 'fields' => 'locale' ] );
		$names   = (array) pll_languages_list( [ 'fields' => 'name' ] );

		$languages = [];
		foreach ( array_values( $slugs ) as $i => $slug ) {
			$languages[ (string) $slug ] = [
				'locale' => (string) ( array_values( $locales )[ $i ] ?? '' ),
				'name'   => (string) ( array_values( $names )[ $i ] ?? $slug ),
			];
		}
		return $languages;
	}

	public function default_language(): string {
		$language = pll_default_language( 'slug' );
		return is_string( $language ) ? $language : '';
	}

	public function is_translated_post_type( string $post_type ): bool {
		return (bool) pll_is_translated_post_type( $post_type );
	}

	public function post_language( \WP_Post $post ): ?string {
		$language = pll_get_post_language( (int) $post->ID, 'slug' );
		return is_string( $language ) && '' !== $language ? $language : null;
	}

	public function current_language(): ?string {
		$language = pll_current_language( 'slug' );
		return is_string( $language ) && '' !== $language ? $language : null;
	}

	public function on_post_language_change( callable $callback ): void {
		// Polylang stores a post's language as a term of the "language" taxonomy.
		add_action(
			'set_object_terms',
			static function ( $object_id, $terms, $tt_ids, $taxonomy ) use ( $callback ): void {
				if ( 'language' === $taxonomy ) {
					$callback( (int) $object_id );
				}
			},
			10,
			4
		);
	}
}
