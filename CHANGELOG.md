# Changelog

## [1.4.1](https://github.com/tomshaw/electricgrid/compare/v1.4.0...v1.4.1) (2026-01-01)


### Bug Fixes

* enhance README with detailed pagination and per-page controls documentation ([3345684](https://github.com/tomshaw/electricgrid/commit/3345684cdf1c557ef99ddcb1dad3d81ee1ba34ab))

## [1.4.0](https://github.com/tomshaw/electricgrid/compare/v1.3.0...v1.4.0) (2026-01-01)


### Features

* add per-page settings and improve pagination logic in Component ([1147a5d](https://github.com/tomshaw/electricgrid/commit/1147a5dba9330bc9da70d853b05b363b1e93d4a4))
* add session state management for filter persistence in Component ([eaee0eb](https://github.com/tomshaw/electricgrid/commit/eaee0eb27fdc5c4653e6b391caca0e412d7d1608))
* update PHP version requirements and improve session key formatting ([718693e](https://github.com/tomshaw/electricgrid/commit/718693efd12797a18aed309bddf48a5ba3d6cc7b))

## [1.3.0](https://github.com/tomshaw/electricgrid/compare/v1.2.0...v1.3.0) (2025-12-01)


### Features

* enhance collection transformation to render View objects as strings ([d5830f7](https://github.com/tomshaw/electricgrid/commit/d5830f73998e8794a8b5256643c644a32c70a29c))

## [1.2.0](https://github.com/tomshaw/electricgrid/compare/v1.1.1...v1.2.0) (2025-09-20)


### Features

* added database collection support. ([bcbcee7](https://github.com/tomshaw/electricgrid/commit/bcbcee7c709a3f82653fef31e670044a85ba2bbf))
* updated per page values ([9934a71](https://github.com/tomshaw/electricgrid/commit/9934a713497c305992a205421340379ed779baa9))


### Bug Fixes

* addressed issue with column sorting ([05c142d](https://github.com/tomshaw/electricgrid/commit/05c142dc6b8434c84a17df66b8c39622bce38e96))


### Miscellaneous Chores

* **master:** release 1.1.1 ([a66866d](https://github.com/tomshaw/electricgrid/commit/a66866d64f4b138d30b50b42edd18b3644f0dd93))

## [1.1.1](https://github.com/tomshaw/electricgrid/compare/v1.1.0...v1.1.1) (2025-09-20)


### Bug Fixes

* addressed issue with column sorting ([05c142d](https://github.com/tomshaw/electricgrid/commit/05c142dc6b8434c84a17df66b8c39622bce38e96))

## [1.1.0](https://github.com/tomshaw/electricgrid/compare/v1.0.3...v1.1.0) (2025-09-20)


### Features

* add new issue templates for bug reports, documentation issues, feature requests, general issues, improvements, and questions ([d9d6262](https://github.com/tomshaw/electricgrid/commit/d9d626234efec798246e1f65c1c09d5615d45239))
* added column aggregate summable and averagable features. ([3dd6c1a](https://github.com/tomshaw/electricgrid/commit/3dd6c1a0d776203ab1a5d8ceed88f5002e0adbb6))


### Bug Fixes

* component formatting ([e75bedd](https://github.com/tomshaw/electricgrid/commit/e75beddfdd33d21d65e794577e61d6dde40c7ba9))

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
