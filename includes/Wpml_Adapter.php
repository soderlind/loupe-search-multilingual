<?php
/**
 * WPML adapter.
 *
 * @package Soderlind\Plugin\LoupeSearchMultilingual
 */

namespace Soderlind\Plugin\LoupeSearchMultilingual;

defined( 'ABSPATH' ) || exit;

/**
 * Reads languages through WPML's filter API.
 */
final class Wpml_Adapter implements Language_Adapter {

	/**
	 * Whether WPML is active.
	 */
	public static function is_available(): bool {
		return defined( 'ICL_SITEPRESS_VERSION' );
	}

	public function languages(): array {
		$active = apply_filters( 'wpml_active_languages', null, [ 'skip_missing' => 0 ] );

		$languages = [];
		foreach ( is_array( $active ) ? $active : [] as $code => $language ) {
			$code = (string) ( $language[ 'code' ] ?? $code );
			if ( '' === $code ) {
				continue;
			}
			$languages[ $code ] = [
				'locale' => (string) ( $language[ 'default_locale' ] ?? '' ),
				'name'   => (string) ( $language[ 'native_name' ] ?? $language[ 'translated_name' ] ?? $code ),
			];
		}
		return $languages;
	}

	public function default_language(): string {
		$language = apply_filters( 'wpml_default_language', null );
		return is_string( $language ) ? $language : '';
	}

	public function is_translated_post_type( string $post_type ): bool {
		return (bool) apply_filters( 'wpml_is_translated_post_type', null, $post_type );
	}

	public function post_language( \WP_Post $post ): ?string {
		$language = apply_filters(
			'wpml_element_language_code',
			null,
			[
				'element_id'   => (int) $post->ID,
				'element_type' => (string) $post->post_type,
			]
		);
		return is_string( $language ) && '' !== $language ? $language : null;
	}

	public function current_language(): ?string {
		$language = apply_filters( 'wpml_current_language', null );
		// "all" is WPML's "All languages" admin filter.
		return is_string( $language ) && '' !== $language && 'all' !== $language ? $language : null;
	}

	public function on_post_language_change( callable $callback ): void {
		// Fired by WPML when an element's language details are saved.
		add_action(
			'wpml_translation_update',
			static function ( $args ) use ( $callback ): void {
				if ( is_array( $args ) && ! empty( $args[ 'element_id' ] ) && str_starts_with( (string) ( $args[ 'element_type' ] ?? '' ), 'post_' ) ) {
					$callback( (int) $args[ 'element_id' ] );
				}
			}
		);
	}
}
