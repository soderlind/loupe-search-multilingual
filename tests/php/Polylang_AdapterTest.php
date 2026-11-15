<?php
namespace Soderlind\Plugin\LoupeSearchMultilingual\Tests;

use Brain\Monkey\Functions;
use Soderlind\Plugin\LoupeSearchMultilingual\Polylang_Adapter;

final class Polylang_AdapterTest extends TestCase {

	public function test_reads_languages_and_treats_false_as_no_language(): void {
		$lists = [
			'slug'   => [ 'en', 'nb' ],
			'locale' => [ 'en_US', 'nb_NO' ],
			'name'   => [ 'English', 'Norsk bokmål' ],
		];
		Functions\when( 'pll_languages_list' )->alias( static fn ( $args ) => $lists[ $args['fields'] ] );
		Functions\when( 'pll_current_language' )->justReturn( false );
		Functions\when( 'pll_get_post_language' )->justReturn( false );

		$adapter = new Polylang_Adapter();

		$this->assertSame(
			[
				'en' => [ 'locale' => 'en_US', 'name' => 'English' ],
				'nb' => [ 'locale' => 'nb_NO', 'name' => 'Norsk bokmål' ],
			],
			$adapter->languages()
		);
		$this->assertNull( $adapter->current_language() );
		$this->assertNull( $adapter->post_language( new \WP_Post( 1 ) ) );
	}
}
