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

	public function test_loupe_rest_searches_follow_the_lang_parameter(): void {
		$partitioner = new Partitioner( $this->adapter( [], 'en' ) );
		$all         = [ 'en', 'nb', 'pt-br' ];

		$partitioner->capture_rest_language( null, null, new \WP_REST_Request( '/loupe-search/v1/search', [ 'lang' => 'pt-BR' ] ) );
		$this->assertSame( [ 'pt-br' ], $partitioner->filter_query_partitions( $all, 'post' ) );
		$partitioner->restore_rest_language( null, null, null );

		// Unknown languages and other routes fall back to the current language.
		$partitioner->capture_rest_language( null, null, new \WP_REST_Request( '/loupe-search/v1/search', [ 'lang' => 'xx' ] ) );
		$this->assertSame( [ 'en' ], $partitioner->filter_query_partitions( $all, 'post' ) );
		$partitioner->restore_rest_language( null, null, null );
		$partitioner->capture_rest_language( null, null, new \WP_REST_Request( '/wp/v2/posts', [ 'lang' => 'nb' ] ) );
		$this->assertSame( [ 'en' ], $partitioner->filter_query_partitions( $all, 'post' ) );
		$partitioner->restore_rest_language( null, null, null );
	}

	public function test_rest_language_ends_with_its_request(): void {
		$partitioner = new Partitioner( $this->adapter( [], 'en' ) );
		$all         = [ 'en', 'nb', 'pt-br' ];

		$partitioner->capture_rest_language( null, null, new \WP_REST_Request( '/loupe-search/v1/search', [ 'lang' => 'nb' ] ) );
		// A nested request with another language.
		$partitioner->capture_rest_language( null, null, new \WP_REST_Request( '/loupe-search/v1/search', [ 'lang' => 'pt-br' ] ) );
		$this->assertSame( [ 'pt-br' ], $partitioner->filter_query_partitions( $all, 'post' ) );
		$partitioner->restore_rest_language( null, null, null );
		$this->assertSame( [ 'nb' ], $partitioner->filter_query_partitions( $all, 'post' ) );
		$partitioner->restore_rest_language( null, null, null );

		$this->assertSame( [ 'en' ], $partitioner->filter_query_partitions( $all, 'post' ) );
	}

	public function test_search_block_url_gets_the_current_language(): void {
		\Brain\Monkey\Functions\when( 'add_query_arg' )->alias( static fn ( $key, $value, $url ) => "$url?$key=$value" );

		$this->assertSame( '/search?lang=nb', ( new Partitioner( $this->adapter( [], 'nb' ) ) )->filter_block_rest_url( '/search' ) );
		$this->assertSame( '/search', ( new Partitioner( $this->adapter() ) )->filter_block_rest_url( '/search' ) );
	}
}
