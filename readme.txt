=== Loupe Search Multilingual ===
Contributors: PerS
Tags: search, multilingual, polylang, wpml, stemming
Requires at least: 7.0
Tested up to: 7.1
Requires PHP: 8.3
Requires Plugins: loupe-search
Stable tag: 0.1.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

One Loupe Search index per language for sites translated with Polylang or WPML.

== Description ==

Loupe Search builds one index per post type, stemmed in the site language. On a multilingual site, Loupe Search Multilingual gives each language its own index instead:

* **Stemming and stop words per language.** A German search for "garten" finds "Gärten"; a Norwegian search for "hage" finds "hagene".
* **Results in the current language.** Front-end searches, and REST searches with a `lang` parameter, cover the current language only.
* **All languages without a current language**: the admin with "All languages" selected, WP-CLI and cron.
* **A Dashboard row per language** under each post type.

Requires Loupe Search 1.6.0 or later and Polylang or WPML. Other multilingual plugins can be supported with the `loupe_search_multilingual_adapter` filter.

== Installation ==

1. Install and activate Loupe Search, Polylang or WPML, and this plugin.
2. Click **Reindex now** in the Loupe Search notice, or run `wp loupe-search reindex`.

Reindex again after adding or removing a language, or changing a language's locale. Loupe Search shows its notice when that is needed.

== Frequently Asked Questions ==

= Which post types get an index per language? =

The post types your multilingual plugin translates. Other post types keep one index.

= Where do posts without a language go? =

To the default language's index.

= How do I get language-scoped results from the Loupe Search Block or REST API? =

With Polylang, pass the `lang` parameter, for example `/wp-json/loupe-search/v1/search?q=garten&lang=de`.

= What happens when I deactivate the add-on? =

Loupe Search shows its notice; reindex to get back to one index per post type.

== Changelog ==

= 0.1.0 =
* Initial release: one Loupe Search index per language for Polylang and WPML.
