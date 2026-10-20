# Changelog - Version 7.0

> **Status: in development.** This document tracks changes landing on the `7.0` branch.
> Nothing here is released yet, and the contents may still change.

## Breaking Changes

### Enum cases renamed to PascalCase

Enum cases follow the ByJG standard and are written in PascalCase. The backed values did not
change, so `->value`, `from()`, `tryFrom()` and stored data are not affected; only code that
names a case (or reads `->name`) has to be updated.

| Enum | Before (6.x) | After (7.0) |
|---|---|---|
| `ModelFileParameters` | `mirostat`, `mirostatEta`, `mirostatTau`, `numCtx`, `repeatLastN`, `repeatPenalty`, `temperature`, `seed`, `stop`, `tfsZ`, `numPredict`, `topK`, `topP`, `minP` | `Mirostat`, `MirostatEta`, `MirostatTau`, `NumCtx`, `RepeatLastN`, `RepeatPenalty`, `Temperature`, `Seed`, `Stop`, `TfsZ`, `NumPredict`, `TopK`, `TopP`, `MinP` |
| `Role` | `user`, `assistant`, `system`, `developer`, `tool` | `User`, `Assistant`, `System`, `Developer`, `Tool` |
| `ToolChoice` | `auto`, `none`, `required` | `Auto`, `None`, `Required` |

### `Api\Ollama` removed

`ByJG\LlmApiObjects\Api\Ollama` was an empty class with no behaviour and was not used by the
library. It is removed; Ollama is still supported through its OpenAI-compatible `/v1` endpoint
(see [Getting Started](docs/getting-started.md)). Code that referenced the class only has to
drop the reference.

## Requirements

- PHP 8.3, 8.4, 8.5 and 8.6 are now supported: `"php": ">=8.3 <8.7"`.
  The previous `<8.6` upper bound excluded PHP 8.6, since `<8.6` is exclusive.

### ByJG dependencies

- `byjg/anydataset-xml` is now `^7.0`.

While 7.0 is unreleased these resolve to `7.0.x-dev` from each component's
`7.0` branch, via `minimum-stability: dev` with `prefer-stable: true`.

## Toolchain

- PHPUnit updated to `^12.5`.
- Psalm is installed as `psalm/phar` instead of `vimeo/psalm`.

  `vimeo/psalm` lists the PHP versions it supports and no published release includes
  8.6, so as a dev dependency it made `composer install` fail on the 8.6 build job
  before any test ran. `psalm/phar` requires only `php ^8.2` and bundles its own
  dependencies, so it installs on every PHP version in the matrix and cannot conflict
  with the project's. Psalm itself still refuses to *run* on 8.6, which is why the
  Psalm job uses 8.5. `composer psalm` runs it.

- PHPUnit 13 is deliberately **not** used. It requires PHP `>=8.4.1`, breaking the
  8.3 floor.

## Continuous Integration

- The build matrix now includes PHP 8.6.
- The Psalm job runs on PHP 8.5.

## Housekeeping

- `phpunit.xml.dist` renamed to `phpunit.xml`.
- Added the `LICENSE` file (MIT); the license was already declared in `composer.json`.
