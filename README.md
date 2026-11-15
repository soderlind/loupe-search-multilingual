# Loupe Search Multilingual

One [Loupe Search](https://github.com/soderlind/loupe-search) index per language, for sites
translated with [Polylang](https://wordpress.org/plugins/polylang/) or
[WPML](https://wpml.org/).

Loupe Search builds one index per post type, with the stemmer and stop words of the site
language. On a multilingual site, that means every post is stemmed as if it were in the site
language, and visitors find posts in all languages. With this add-on:

- **Each language has its own index**, stemmed with its own stemmer and stop words. A German
  search for "garten" finds "Gärten"; a Norwegian search for "hage" finds "hagene".
- **Visitors only find posts in the current language.** Front-end searches and REST searches
  with a `lang` parameter (Polylang) cover the current language's index only.
- **Searches without a language cover all languages**: the admin with "All languages" selected,
  WP-CLI and cron.
- **The Dashboard shows a row per language** under each post type, with the language name.

## Requirements

- WordPress 7.0 or later, PHP 8.3 or later
- [Loupe Search](https://github.com/soderlind/loupe-search) 1.6.0 or later
- Polylang or WPML

## Setup

1. Install and activate Loupe Search, your multilingual plugin and this plugin.
2. Loupe Search shows a notice that the indexes need a rebuild. Click **Reindex now** on
   **Settings → Loupe Search → Dashboard**, or run `wp loupe-search reindex`.

Reindex again when you add or remove a language, or change a language's locale. Loupe Search
shows its notice when that is needed. When you deactivate this add-on, reindex to get back to
one index per post type.

## How it works

Loupe Search 1.6.0 can split a post type's index into partitions, each a separate Loupe index
in its own directory (for example `wp-content/loupe-search-db/site-2/post/de/`). The add-on
hooks Loupe Search's partition filters:

| Filter | What the add-on returns |
| --- | --- |
| `loupe_search_partitions` | The language codes, default language first, for post types your multilingual plugin translates. Other post types keep one index. |
| `loupe_search_post_partition` | The post's language. Posts without a language go to the default language. |
| `loupe_search_partition_language` | The Loupe stemmer language for the language's locale (`nb_NO` → `no`). |
| `loupe_search_partition_label` | The language name, for the Dashboard. |
| `loupe_search_query_partitions` | The current language's partition only. Without a current language: all of them. |

When you change a post's language without saving the post (for example with Polylang's bulk
edit or `pll_set_post_language()`), the add-on reindexes it at the end of the request, so it
moves to the new language's index.

The `languages` key of the `loupe_search_advanced` option, if set, overrides the stemmer
language for every partition.

## Search Block and REST

Polylang sets the current language for REST requests from the `lang` parameter. Pass it to get
results in one language:

```
/wp-json/loupe-search/v1/search?q=garten&lang=de
```

Without `lang`, the REST search covers all languages.

## Other multilingual plugins

Implement `Soderlind\Plugin\LoupeSearchMultilingual\Language_Adapter` and return it from the
`loupe_search_multilingual_adapter` filter. It runs on `plugins_loaded` at priority 5, before
Loupe Search sets up its indexes.

```php
add_filter( 'loupe_search_multilingual_adapter', function ( $adapter ) {
	return $adapter ?? new My_Language_Adapter();
} );
```

## Development

```sh
composer install
composer test
```

## License

GPL-2.0-or-later. Copyright Per Soderlind.
