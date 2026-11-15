# Changelog

All notable changes to this project are documented in this file.

## [0.1.0] - 2026-11-15

### Added

- One Loupe Search index partition per language for post types translated with Polylang or WPML,
  with the language's stemmer and stop words and the language name on the Dashboard.
- Searches cover the current language's partition; without a current language they cover all.
- Posts whose language changes without a post save are moved to the new language's index.
- Loupe Search's reindex notice is refreshed when languages are added, removed or change locale,
  and when the add-on is deactivated.
- `loupe_search_multilingual_adapter` filter for other multilingual plugins.
