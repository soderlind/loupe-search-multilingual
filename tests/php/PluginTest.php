<?php
namespace Soderlind\Plugin\LoupeSearchMultilingual\Tests;

use Brain\Monkey\Actions;
use Brain\Monkey\Filters;
use Brain\Monkey\Functions;
use Soderlind\Plugin\LoupeSearch\WP_Loupe_Index_Notice;
use Soderlind\Plugin\LoupeSearch\WP_Loupe_Indexer;
use Soderlind\Plugin\LoupeSearch\WP_Loupe_Partitions;
use Soderlind\Plugin\LoupeSearchMultilingual\Plugin;

final class PluginTest extends TestCase {

	/** @var array<string,callable> */
	private array $hooks = [];

	private Fake_Adapter $fake;

	protected function setUp(): void {
		parent::setUp();
		$this->hooks = [];
		$this->fake  = $this->adapter();

		foreach ( [ 'wp_after_insert_post', 'shutdown', 'admin_init' ] as $hook ) {
			Actions\expectAdded( $hook )->zeroOrMoreTimes()->whenHappen(
				function ( $callback ) use ( $hook ) {
					$this->hooks[ $hook ] = $callback;
				}
			);
		}
		Filters\expectApplied( 'loupe_search_multilingual_adapter' )->andReturn( $this->fake );

		$posts = [
			1 => new \WP_Post( 1 ),
			2 => new \WP_Post( 2 ),
			3 => new \WP_Post( 3, 'attachment' ),
		];
		Functions\when( 'get_post' )->alias( static fn ( $id ) => $posts[ $id ] ?? null );

		Plugin::boot();
	}

	public function test_posts_whose_language_changed_without_a_save_are_reindexed_once(): void {
		$this->fake->change_language( 1 );
		$this->fake->change_language( 1 );
		$this->fake->change_language( 2 );
		$this->fake->change_language( 3 );
		// Post 2 was saved; Loupe Search indexed it already.
		( $this->hooks['wp_after_insert_post'] )( 2 );

		( $this->hooks['shutdown'] )();
		( $this->hooks['shutdown'] )();

		$this->assertSame( [ 1 ], WP_Loupe_Indexer::$added );
	}

	public function test_layout_change_refreshes_the_index_notice(): void {
		WP_Loupe_Partitions::$partitions = [ 'post' => [ 'en', 'nb' ] ];

		( $this->hooks['admin_init'] )();
		( $this->hooks['admin_init'] )();
		$this->assertSame( 1, WP_Loupe_Index_Notice::$flushes );

		WP_Loupe_Partitions::$partitions = [ 'post' => [ 'en', 'nb', 'de' ] ];
		( $this->hooks['admin_init'] )();
		$this->assertSame( 2, WP_Loupe_Index_Notice::$flushes );
	}
}
