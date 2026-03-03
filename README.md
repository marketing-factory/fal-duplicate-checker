# FAL Duplicate Checker

A TYPO3 backend extension that detects duplicate files in FAL (`sys_file`) by comparing SHA1 hashes and displays a warning in the backend file info view.

## Requirements

- TYPO3 13.4 or 14.x
- PHP 8.2+

## Installation

```bash
composer require mfd/fal-duplicate-checker
```

No additional configuration is required. The extension registers its page TSconfig and backend route override automatically on activation.

## How it works

When a backend user opens the file information view (e.g. via the File List module → right-click → Info), the extension queries `sys_file` for other records that share the same SHA1 hash as the currently viewed file. If any are found, a warning infobox is shown listing the paths of all duplicate files.

Duplicate detection relies on the `sha1` column already maintained by the TYPO3 FAL indexer — no additional indexing or scheduler tasks are needed.

## License

GPL-2.0-or-later — see [LICENSE](LICENSE).
