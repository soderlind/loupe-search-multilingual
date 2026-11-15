<?php
namespace Soderlind\Plugin\LoupeSearchMultilingual\Tests;

use Soderlind\Plugin\LoupeSearchMultilingual\Partitioner;

final class PartitionerTest extends TestCase {

	public function test_translated_post_types_get_one_partition_per_language_default_first(): void {
		$partitioner = new Partitioner( $this->adapter() );

		$this->assertSame( [ 'en', 'nb', 'pt-br' ], $partitioner->filter_partitions( [ '' ], 'post' ) );
		$this->assertSame( [ '' ], $partitioner->filter_partitions( [ '' ], 'product' ) );
	}

	public function test_languages_that_load_late_are_picked_up(): void {
		$adapter     = $this->adapter();
		$languages   = $adapter->languages;
		$adapter->languages = [];
		$partitioner = new Partitioner( $adapter );

		$this->assertSame( [ '' ], $partitioner->filter_partitions( [ '' ], 'post' ) );

		$adapter->languages = $languages;
		$this->assertSame( [ 'en', 'nb', 'pt-br' ], $partitioner->filter_partitions( [ '' ], 'post' ) );
	}

	public function test_post_goes_to_its_language_or_the_default(): void {
		$partitioner = new Partitioner( $this->adapter( [ 1 => 'nb', 2 => 'pt-BR' ] ) );

		$this->assertSame( 'nb', $partitioner->filter_post_partition( 'en', new \WP_Post( 1 ) ) );
		$this->assertSame( 'pt-br', $partitioner->filter_post_partition( 'en', new \WP_Post( 2 ) ) );
		$this->assertSame( 'en', $partitioner->filter_post_partition( 'nb', new \WP_Post( 3 ) ) );
		$this->assertSame( 'x', $partitioner->filter_post_partition( 'x', new \WP_Post( 1, 'product' ) ) );
	}

	public function test_partition_language_and_label_come_from_the_language(): void {
		$partitioner = new Partitioner( $this->adapter() );

		$this->assertSame( 'pt', $partitioner->filter_partition_language( 'en', 'pt-br', 'post' ) );
		$this->assertSame( 'Norsk bokmål', $partitioner->filter_partition_label( 'nb', 'nb', 'post' ) );
		$this->assertSame( 'en', $partitioner->filter_partition_language( 'en', 'unknown', 'post' ) );
		$this->assertSame( 'unknown', $partitioner->filter_partition_label( 'unknown', 'unknown', 'post' ) );
	}

	public function test_search_covers_the_current_language_only(): void {
		$partitioner = new Partitioner( $this->adapter( [], 'nb' ) );

		$this->assertSame( [ 'nb' ], $partitioner->filter_query_partitions( [ 'en', 'nb', 'pt-br' ], 'post' ) );
		// Unpartitioned post types keep their single index.
		$this->assertSame( [ '' ], $partitioner->filter_query_partitions( [ '' ], 'product' ) );
	}

	public function test_search_covers_all_languages_without_a_current_language(): void {
		$partitioner = new Partitioner( $this->adapter() );

		$this->assertSame( [ 'en', 'nb' ], $partitioner->filter_query_partitions( [ 'en', 'nb' ], 'post' ) );
	}
}
