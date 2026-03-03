# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Overview

`fal_duplicate_checker` is a TYPO3 v13.4 backend extension (GPL-2.0-or-later) that detects duplicate files in FAL (`sys_file`) by comparing SHA1 hashes and displays a warning in the backend file info view.

Extension key: `fal_duplicate_checker`
Namespace: `Mfd\Fal\DuplicateChecker`

## Installation

This extension is installed as part of a TYPO3 project via Composer. There are no standalone build steps — no test suite, no assets to compile.

```bash
composer install
```

## Architecture

### How the duplicate detection works

The extension overrides the TYPO3 core backend route `show_item` (`/record/info`) by registering its own controller in `Configuration/Backend/Routes.php`. This takes precedence over the core controller of the same route name.

`ElementInformationController` extends `TYPO3\CMS\Backend\Controller\ContentElement\ElementInformationController` and overrides `mainAction()`. After performing all standard element info rendering (inherited from the base class), it adds duplicate detection: when the viewed record is a `sys_file`, it queries all other `sys_file` records sharing the same `sha1` hash and passes them to the template as `{duplicates}`.

### Template override mechanism

`Configuration/page.tsconfig` registers an additional template path for the `typo3/cms-backend` package:

```
templates.typo3/cms-backend.1743610000 = mfd/fal-duplicate-checker:Resources/Private/Templates
```

This causes Fluid to find `Resources/Private/Templates/ContentElement/ElementInformation.html` before the core template, effectively overriding it. The template is a full copy of the core template with the duplicate warning infobox added at line 82–91.

### DI

`Configuration/Services.yaml` uses standard autowire/autoconfigure. The controller is tagged automatically via the `#[AsController]` PHP attribute.

## Key files

| File | Purpose |
|------|---------|
| `Classes/Controller/Backend/ElementInformationController.php` | Core logic — extends base controller, adds SHA1-based duplicate query |
| `Configuration/Backend/Routes.php` | Overrides the `show_item` route to use this extension's controller |
| `Configuration/page.tsconfig` | Registers template override path so the Fluid template is found first |
| `Resources/Private/Templates/ContentElement/ElementInformation.html` | Full template copy from core with duplicate warning block added |

## Important constraints

- Targets **TYPO3 13.4.x only** (`^13.4`). The base controller API and template structure are tightly coupled to this version.
- The template (`ElementInformation.html`) is a copy of the core template. When updating the TYPO3 core version, this template must be re-synced with the upstream version to avoid regressions.
- The `page.tsconfig` key `1743610000` must remain unique across all installed extensions in the project.
