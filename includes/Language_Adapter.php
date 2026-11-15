<?php
/**
 * Language adapter contract.
 *
 * @package Soderlind\Plugin\LoupeSearchMultilingual
 */

namespace Soderlind\Plugin\LoupeSearchMultilingual;

defined( 'ABSPATH' ) || exit;

/**
 * What the add-on needs to know from a multilingual plugin.
 *
 * Language codes are the plugin's own (Polylang slugs, WPML codes).
 */
interface Language_Adapter {

	/**
	 * Languages of the site.
	 *
	 * @return array<string,array{locale:string,name:string}> Language code => WordPress locale and display name.
	 */
	public function languages(): array;

	/**
	 * Default language code, or '' when none is set up.
	 */
	public function default_language(): string;

	/**
	 * Whether posts of this type are translated (have a language).
	 *
	 * @param string $post_type Post type.
	 */
	public function is_translated_post_type( string $post_type ): bool;

	/**
	 * Language code of a post, or null when it has none.
	 *
	 * @param \WP_Post $post Post.
	 */
	public function post_language( \WP_Post $post ): ?string;

	/**
	 * Language of the current request, or null when none (all languages, WP-CLI, cron).
	 */
	public function current_language(): ?string;

	/**
	 * Call $callback( int $post_id ) when a post's language is set or changed
	 * outside a regular post save.
	 *
	 * @param callable $callback Receives the post ID.
	 */
	public function on_post_language_change( callable $callback ): void;
}
