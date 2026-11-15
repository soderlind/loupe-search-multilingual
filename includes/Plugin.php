<?php
/**
 * Plugin bootstrap.
 *
 * @package Soderlind\Plugin\LoupeSearchMultilingual
 */

namespace Soderlind\Plugin\LoupeSearchMultilingual;

use Soderlind\Plugin\LoupeSearch\WP_Loupe_Index_Notice;
use Soderlind\Plugin\LoupeSearch\WP_Loupe_Indexer;
use Soderlind\Plugin\LoupeSearch\WP_Loupe_Partitions;
use Soderlind\Plugin\LoupeSearch\WP_Loupe_Utils;

defined( 'ABSPATH' ) || exit;

/**
 * Wires the language adapter into Loupe Search.
 */
final class Plugin {

	/** Hash of the partition layout the indexes were last checked against. */
	public const LAYOUT_OPTION = 'loupe_search_multilingual_layout';

	private static ?self $instance = null;

	/**
	 * Post IDs whose language changed outside a regular save.
	 *
	 * @var array<int,true>
	 */
	private array $language_changed = [];

	private function __construct( private Language_Adapter $adapter, private Partitioner $partitioner ) {}

	/**
	 * Runs on plugins_loaded, after the multilingual plugin and before Loupe Search.
	 */
	public static function boot(): void {
		add_action( 'plugins_loaded', [ self::class, 'check_requirements' ], 20 );

		$adapter = self::adapter();
		if ( null === $adapter ) {
			return;
		}
		self::$instance = new self( $adapter, new Partitioner( $adapter ) );
		self::$instance->register();
	}

	/**
	 * The language adapter for the active multilingual plugin, if any.
	 */
	public static function adapter(): ?Language_Adapter {
		$adapter = null;
		if ( Polylang_Adapter::is_available() ) {
			$adapter = new Polylang_Adapter();
		} elseif ( Wpml_Adapter::is_available() ) {
			$adapter = new Wpml_Adapter();
		}

		/**
		 * Filters the language adapter, e.g. to support another multilingual plugin.
		 *
		 * @since 0.1.0
		 * @param Language_Adapter|null $adapter Adapter for Polylang or WPML, or null.
		 */
		$adapter = apply_filters( 'loupe_search_multilingual_adapter', $adapter );

		return $adapter instanceof Language_Adapter ? $adapter : null;
	}

	public function register(): void {
		$this->partitioner->register();

		$this->adapter->on_post_language_change(
			function ( int $post_id ): void {
				$this->language_changed[ $post_id ] = true;
			}
		);
		// Loupe Search indexes the post itself on wp_after_insert_post (priority 10).
		add_action(
			'wp_after_insert_post',
			function ( $post_id ): void {
				unset( $this->language_changed[ (int) $post_id ] );
			},
			20
		);
		add_action( 'shutdown', [ $this, 'reindex_language_changes' ] );
		add_action( 'admin_init', [ $this, 'check_layout' ] );
	}

	/**
	 * Reindex posts whose language changed without a post save, so they move partition.
	 */
	public function reindex_language_changes(): void {
		$post_ids               = array_keys( $this->language_changed );
		$this->language_changed = [];
		if ( empty( $post_ids ) || ! class_exists( WP_Loupe_Indexer::class ) ) {
			return;
		}

		$post_types = WP_Loupe_Utils::get_indexed_post_types();
		$indexer    = null;
		foreach ( $post_ids as $post_id ) {
			$post = get_post( $post_id );
			if ( ! $post instanceof \WP_Post || ! in_array( $post->post_type, $post_types, true ) ) {
				continue;
			}
			try {
				$indexer ??= new WP_Loupe_Indexer( null, false );
				$indexer->add( $post_id, $post, true );
			} catch ( \Throwable $e ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( sprintf( 'Loupe Search Multilingual: reindexing post %d failed: %s', $post_id, $e->getMessage() ) );
			}
		}
	}

	/**
	 * When languages are added, removed or change locale, refresh Loupe Search's
	 * index status so its notice asks for a reindex.
	 */
	public function check_layout(): void {
		if ( ! class_exists( WP_Loupe_Partitions::class ) ) {
			return;
		}

		$layout = [];
		foreach ( WP_Loupe_Utils::get_indexed_post_types() as $post_type ) {
			foreach ( WP_Loupe_Partitions::all( $post_type ) as $partition ) {
				$layout[ $post_type ][ $partition ] = WP_Loupe_Partitions::language( $partition, $post_type );
			}
		}
		$hash = md5( (string) wp_json_encode( $layout ) );

		if ( get_option( self::LAYOUT_OPTION ) !== $hash ) {
			update_option( self::LAYOUT_OPTION, $hash, false );
			WP_Loupe_Index_Notice::flush();
		}
	}

	/**
	 * Admin notices for missing requirements.
	 */
	public static function check_requirements(): void {
		$message = null;
		if ( ! class_exists( WP_Loupe_Partitions::class ) ) {
			$message = __( 'Loupe Search Multilingual requires Loupe Search 1.6.0 or later.', 'loupe-search-multilingual' );
		} elseif ( null === self::$instance ) {
			$message = __( 'Loupe Search Multilingual requires Polylang or WPML.', 'loupe-search-multilingual' );
		}
		if ( null === $message ) {
			return;
		}

		add_action(
			'admin_notices',
			static function () use ( $message ): void {
				if ( current_user_can( 'activate_plugins' ) ) {
					printf( '<div class="notice notice-warning"><p>%s</p></div>', esc_html( $message ) );
				}
			}
		);
	}

	/**
	 * Indexes built per language no longer match an unpartitioned layout: show the reindex notice.
	 */
	public static function deactivate(): void {
		delete_option( self::LAYOUT_OPTION );
		if ( class_exists( WP_Loupe_Index_Notice::class ) ) {
			WP_Loupe_Index_Notice::flush();
		}
	}
}
