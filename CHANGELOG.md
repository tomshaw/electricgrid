# Changelog

## [1.0.3](https://github.com/tomshaw/electricgrid/compare/v1.0.2...v1.0.3) (2025-04-19)


### Bug Fixes

* update package description for clarity and accuracy ([562b3d2](https://github.com/tomshaw/electricgrid/commit/562b3d204c88a8f964f91bfdf8de2250da491d16))

## [1.0.2](https://github.com/tomshaw/electricgrid/compare/v1.0.1...v1.0.2) (2025-04-19)


### Bug Fixes

* improve checkbox handling and sorting labels in grid component ([212f8e0](https://github.com/tomshaw/electricgrid/commit/212f8e0ebe8dcf74253e4332cb5138f682679295))

## [1.0.1](https://github.com/tomshaw/electricgrid/compare/v1.0.0...v1.0.1) (2025-03-24)


### Performance Improvements

* deprecate checkboxAll memory blowup by removing large-scale checkboxValues tracking\n\n- Avoided populating checkboxValues[] with massive row counts when checkboxAll is true\n- Improved scalability by toggling visual checkbox state only for current page\n- Bulk actions now operate efficiently on all rows using logical checkboxAll flag with chunked processing ([11cd5a8](https://github.com/tomshaw/electricgrid/commit/11cd5a83f9f0cddbcb20e9f925d352a2f8ab4e4a))

## [1.0.0](https://github.com/tomshaw/electricgrid/compare/v0.5.2...v1.0.0) (2025-03-12)


### ⚠ BREAKING CHANGES

* This update requires Laravel v12 and may introduce breaking changes.

### Features

* update package to Laravel v12 ([af9e8f0](https://github.com/tomshaw/electricgrid/commit/af9e8f08b689ade9eaeb24c5d5f7e1ed395239d8))

## [0.5.2](https://github.com/tomshaw/electricgrid/compare/v0.5.1...v0.5.2) (2025-02-03)


### Miscellaneous Chores

* **deps:** add support for PHP 8.3 and 8.4 in composer.json ([56c6d20](https://github.com/tomshaw/electricgrid/commit/56c6d201e73cba6050e14146447bccb815943262))

## [0.5.1](https://github.com/tomshaw/electricgrid/compare/v0.5.0...v0.5.1) (2025-02-02)


### Miscellaneous Chores

* **deps:** add support for PHP 8.3 and 8.4 in composer.json ([bb9afb2](https://github.com/tomshaw/electricgrid/commit/bb9afb2696761a4d1cffe8350979523bb1ececfc))

## [0.5.0](https://github.com/tomshaw/electricgrid/compare/v0.4.0...v0.5.0) (2024-08-03)


### Features

* updated readme. ([41cc623](https://github.com/tomshaw/electricgrid/commit/41cc623033cafb1a1b53e48bc47c5afe03559200))

## [0.4.0](https://github.com/tomshaw/electricgrid/compare/v0.3.0...v0.4.0) (2024-04-19)


### Features

* added release please github workflow. ([a0153eb](https://github.com/tomshaw/electricgrid/commit/a0153eb031915ab82b0ed24a0025ca79838bfb24))
