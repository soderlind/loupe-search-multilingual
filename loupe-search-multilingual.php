<?php
/**
 * Loupe Search Multilingual
 *
 * @package   Soderlind\Plugin\LoupeSearchMultilingual
 * @author    Per Soderlind
 * @license   GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       Loupe Search Multilingual
 * Plugin URI:        https://github.com/soderlind/loupe-search-multilingual
 * Description:       One Loupe Search index per language for sites translated with Polylang or WPML, so each language gets its own stemming and stop words and visitors only find posts in their language.
 * Version:           0.2.0
 * Author:            Per Soderlind
 * Author URI:        https://soderlind.no
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       loupe-search-multilingual
 * Domain Path:       /languages
 * Requires at least: 7.0
 * Requires PHP:      8.3
 * Requires Plugins:  loupe-search
 */

namespace Soderlind\Plugin\LoupeSearchMultilingual;

defined( 'ABSPATH' ) || exit;

const VERSION = '0.2.0';
const FILE    = __FILE__;

spl_autoload_register(
	static function ( string $class ): void {
		$prefix = __NAMESPACE__ . '\\';
		if ( ! str_starts_with( $class, $prefix ) ) {
			return;
		}
		$file = __DIR__ . '/includes/' . str_replace( '\\', '/', substr( $class, strlen( $prefix ) ) ) . '.php';
		if ( is_readable( $file ) ) {
			require_once $file;
		}
	}
);

// After Polylang initializes (plugins_loaded, 1) and before Loupe Search builds its indexer (plugins_loaded, 10).
add_action( 'plugins_loaded', [ Plugin::class, 'boot' ], 5 );
register_deactivation_hook( __FILE__, [ Plugin::class, 'deactivate' ] );
