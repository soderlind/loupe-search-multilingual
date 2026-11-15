<?php
namespace Soderlind\Plugin\LoupeSearchMultilingual\Tests;

use Brain\Monkey;
use Brain\Monkey\Functions;
use Soderlind\Plugin\LoupeSearch\WP_Loupe_Index_Notice;
use Soderlind\Plugin\LoupeSearch\WP_Loupe_Indexer;
use Soderlind\Plugin\LoupeSearch\WP_Loupe_Partitions;
use Soderlind\Plugin\LoupeSearch\WP_Loupe_Utils;

/**
 * Brain Monkey with in-memory options and a fake language adapter.
 */
abstract class TestCase extends \PHPUnit\Framework\TestCase {

	/** @var array<string,mixed> */
	protected array $options = [];

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
		$this->options = [];

		WP_Loupe_Partitions::$partitions = [];
		WP_Loupe_Index_Notice::$flushes  = 0;
		WP_Loupe_Indexer::$added         = [];
		WP_Loupe_Utils::$post_types      = [ 'post' ];

		Functions\stubTranslationFunctions();
		Functions\when( 'get_option' )->alias( fn ( $name, $default = false ) => $this->options[ $name ] ?? $default );
		Functions\when( 'update_option' )->alias(
			function ( $name, $value ) {
				$this->options[ $name ] = $value;
				return true;
			}
		);
		Functions\when( 'wp_json_encode' )->alias( static fn ( $data ) => json_encode( $data ) );
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	/**
	 * A language adapter with fixed answers.
	 *
	 * @param array<int,string> $post_languages Post ID => language code.
	 */
	protected function adapter( array $post_languages = [], ?string $current = null ): Fake_Adapter {
		return new Fake_Adapter(
			[
				'nb'    => [ 'locale' => 'nb_NO', 'name' => 'Norsk bokmål' ],
				'en'    => [ 'locale' => 'en_US', 'name' => 'English' ],
				'pt-BR' => [ 'locale' => 'pt_BR', 'name' => 'Português' ],
			],
			'en',
			$post_languages,
			$current
		);
	}
}
