<?php
namespace Soderlind\Plugin\LoupeSearchMultilingual\Tests;

use Soderlind\Plugin\LoupeSearchMultilingual\Language_Adapter;

/**
 * In-memory Language_Adapter.
 */
final class Fake_Adapter implements Language_Adapter {

	/** @var list<callable> */
	public array $listeners = [];

	/**
	 * @param array<string,array{locale:string,name:string}> $languages
	 * @param array<int,string>                              $post_languages
	 */
	public function __construct(
		public array $languages,
		public string $default,
		public array $post_languages = [],
		public ?string $current = null,
		public array $translated = [ 'post', 'page' ],
	) {}

	public function languages(): array {
		return $this->languages;
	}

	public function default_language(): string {
		return $this->default;
	}

	public function is_translated_post_type( string $post_type ): bool {
		return in_array( $post_type, $this->translated, true );
	}

	public function post_language( \WP_Post $post ): ?string {
		return $this->post_languages[ $post->ID ] ?? null;
	}

	public function current_language(): ?string {
		return $this->current;
	}

	public function on_post_language_change( callable $callback ): void {
		$this->listeners[] = $callback;
	}

	public function change_language( int $post_id ): void {
		foreach ( $this->listeners as $listener ) {
			$listener( $post_id );
		}
	}
}
