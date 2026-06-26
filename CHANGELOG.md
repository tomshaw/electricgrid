# Changelog

## [2.12.1](https://github.com/tomshaw/electricgrid/compare/v2.12.0...v2.12.1) (2026-06-26)


### Bug Fixes

* preserve persisted filters on nested relation columns ([467f5ad](https://github.com/tomshaw/electricgrid/commit/467f5ad157bddef7f73bb6c64f9b6d9c2089d95d))

## [2.12.0](https://github.com/tomshaw/electricgrid/compare/v2.11.0...v2.12.0) (2026-06-26)


### Features

* Add tests for pruning persisted filters and orderBy properties on component mount ([f2681b1](https://github.com/tomshaw/electricgrid/commit/f2681b1c46e1cdc5fe8ee1943b53d6c9525cb768))
* Implement pruneStaleState method to clean up filter and orderBy properties ([4a5425a](https://github.com/tomshaw/electricgrid/commit/4a5425ac1cc8666ead0ac222d1fcff5ccfab02e1))

## [2.11.0](https://github.com/tomshaw/electricgrid/compare/v2.10.0...v2.11.0) (2026-06-25)


### Features

* Add support for nested relation sorting and implement Company model ([d23b007](https://github.com/tomshaw/electricgrid/commit/d23b007174ba8af2b910340165fde2ef6cf6ff02))


### Miscellaneous Chores

* **deps:** bump actions/checkout from 6 to 7 ([bb0990f](https://github.com/tomshaw/electricgrid/commit/bb0990f3c28d11e42aaee3b7c646d918e94c0c0f))
* **deps:** bump actions/checkout from 6 to 7 ([ec15c5a](https://github.com/tomshaw/electricgrid/commit/ec15c5a7d9088360b95ef3df9117336b33625a11))

## [2.10.0](https://github.com/tomshaw/electricgrid/compare/v2.9.3...v2.10.0) (2026-06-12)


### Features

* Add strict types and type hints across multiple files for improved type safety ([41aa6bf](https://github.com/tomshaw/electricgrid/commit/41aa6bf6142b60a03fa8607a19f1aa18a4eb775e))
* Enable strict types across multiple files for improved type safety ([3cb223d](https://github.com/tomshaw/electricgrid/commit/3cb223de28bcf99a05090e6410681c55646fe5ce))
* Implement filtering and sorting capabilities for data sources ([4d3a419](https://github.com/tomshaw/electricgrid/commit/4d3a419ccf64118116c86caa21c6cac5411e5039))


### Miscellaneous Chores

* Remove unused phpstan baseline file and related configurations ([8dab988](https://github.com/tomshaw/electricgrid/commit/8dab988cae820ad86d83934221f8ab2ea1c621e4))
* Update README badges for improved visibility and accuracy ([62d34c8](https://github.com/tomshaw/electricgrid/commit/62d34c86ab04d738809976f07402353b5ec5b865))

## [2.9.3](https://github.com/tomshaw/electricgrid/compare/v2.9.2...v2.9.3) (2026-06-09)


### Bug Fixes

* persist session state after toggling order direction ([e19e4ff](https://github.com/tomshaw/electricgrid/commit/e19e4fff09e7073c29e00fdee5b011c2ad88eac3))

## [2.9.2](https://github.com/tomshaw/electricgrid/compare/v2.9.1...v2.9.2) (2026-06-04)


### Bug Fixes

* update badge links in README for improved visibility and accuracy ([275a50b](https://github.com/tomshaw/electricgrid/commit/275a50b20f421185f1847694e0ebec08c7a485c4))

## [2.9.1](https://github.com/tomshaw/electricgrid/compare/v2.9.0...v2.9.1) (2026-06-04)


### Bug Fixes

* improve exportable column visibility logic to match grid display ([5ce963f](https://github.com/tomshaw/electricgrid/commit/5ce963f1290642b18076ab3f9e41d669c1db3d50))

## [2.9.0](https://github.com/tomshaw/electricgrid/compare/v2.8.1...v2.9.0) (2026-05-25)


### Features

* add caption support for tables with customizable positioning and tests ([9ac2e3e](https://github.com/tomshaw/electricgrid/commit/9ac2e3e2073f1d3dd0f39eb6442b3bd38d8aa330))
* add row stripe customization and corresponding tests ([4063c71](https://github.com/tomshaw/electricgrid/commit/4063c717921f1a3490623f385d5abc8656a09685))
* enhance data export functionality with customizable styles and tests ([4f3a602](https://github.com/tomshaw/electricgrid/commit/4f3a6020fdc0e42fe38656a0f82e07a66c09c3fe))
* implement customizable row hover colors and add tests ([985b067](https://github.com/tomshaw/electricgrid/commit/985b067bd83e21f8c6c3abf77644a0cf00662f84))

## [2.8.1](https://github.com/tomshaw/electricgrid/compare/v2.8.0...v2.8.1) (2026-05-21)


### Bug Fixes

* add type hint for updated method parameter and save session state on letter search ([aab5691](https://github.com/tomshaw/electricgrid/commit/aab5691e990524d13a054216ba6ea87abd0ef39a))

## [2.8.0](https://github.com/tomshaw/electricgrid/compare/v2.7.1...v2.8.0) (2026-05-12)


### Features

* implement infinite scroll functionality with IntersectionObserver and releated tests ([95ace4c](https://github.com/tomshaw/electricgrid/commit/95ace4ca09fd783c1cf765a01a3cb1981b36bc8b))

## [2.7.1](https://github.com/tomshaw/electricgrid/compare/v2.7.0...v2.7.1) (2026-05-08)


### Bug Fixes

* improve checkbox value handling in GridActions trait; add ExportActionTest for export functionality ([acc0088](https://github.com/tomshaw/electricgrid/commit/acc0088b800c9bf6d9cb980e7002b911b3e7dde1))

## [2.7.0](https://github.com/tomshaw/electricgrid/compare/v2.6.2...v2.7.0) (2026-05-06)


### Features

* add row click functionality to table rows and update data source transformations ([806568c](https://github.com/tomshaw/electricgrid/commit/806568c4b31646cd1df2e7f91c41b1d1cede4630))


### Bug Fixes

* update README to include clickable rows functionality and examples ([f1798d9](https://github.com/tomshaw/electricgrid/commit/f1798d9f4b1ec918e5fc1eae872584775b1ebfe1))

## [2.6.2](https://github.com/tomshaw/electricgrid/compare/v2.6.1...v2.6.2) (2026-05-02)


### Bug Fixes

* update version constraints for maatwebsite/excel and phpoffice/phpspreadsheet ([debdd21](https://github.com/tomshaw/electricgrid/commit/debdd214190d734a44ff38237a4e9bf50ed22a25))


### Miscellaneous Chores

* **deps:** bump googleapis/release-please-action from 4 to 5 ([cd2b611](https://github.com/tomshaw/electricgrid/commit/cd2b611a5cf4b8b1430d758b97260eb3a7e0b326))
* **deps:** bump googleapis/release-please-action from 4 to 5 ([9fcac97](https://github.com/tomshaw/electricgrid/commit/9fcac97482dd5774eacabfafc29648559bd92919))

## [2.6.1](https://github.com/tomshaw/electricgrid/compare/v2.6.0...v2.6.1) (2026-04-08)


### Bug Fixes

* improve toggle columns button and tooltip styling ([cb7e831](https://github.com/tomshaw/electricgrid/commit/cb7e83190d8cc1fab5df55089212cd69f1d92a62))

## [2.6.0](https://github.com/tomshaw/electricgrid/compare/v2.5.0...v2.6.0) (2026-04-08)


### Features

* enhance getColspanProperty to exclude hidden columns ([374e777](https://github.com/tomshaw/electricgrid/commit/374e777bb01ddeb0b49787729334f20954b072c6))
* update handleToggleColumns to save session state after toggling columns ([a5ef774](https://github.com/tomshaw/electricgrid/commit/a5ef774cc9b40b8b3cef1561e8b2425c5bfcff53))

## [2.5.0](https://github.com/tomshaw/electricgrid/compare/v2.4.0...v2.5.0) (2026-04-04)


### Features

* apply pint code style fixes ([73d3880](https://github.com/tomshaw/electricgrid/commit/73d388022be022268d400ebbe5d73f4bccf435d1))

## [2.4.0](https://github.com/tomshaw/electricgrid/compare/v2.3.1...v2.4.0) (2026-04-04)


### Features

* apply pint code style fixes and update linting workflow ([f84ef9d](https://github.com/tomshaw/electricgrid/commit/f84ef9d385184d10758ace32d799d2a0faa7f4ea))
* fix pint workflow to use project config and revert incorrect style changes ([fccd0eb](https://github.com/tomshaw/electricgrid/commit/fccd0eb8299da882d205fc95cd0f7fc3fffdf1fc))
* standardize CI workflows and update dependencies for Laravel 13 ([4d01025](https://github.com/tomshaw/electricgrid/commit/4d01025e5074d5ef936d255d23fdb5da6d1ff7f1))
* use project pint version in CI instead of external action ([a23f45a](https://github.com/tomshaw/electricgrid/commit/a23f45a52e26f310dcb3e3597b65d7ec40461a18))

## [2.3.1](https://github.com/tomshaw/electricgrid/compare/v2.3.0...v2.3.1) (2026-03-23)


### Bug Fixes

* upgrade actions/checkout and actions/cache to v5, and update git-auto-commit-action to v7 in workflow files ([8f1acb2](https://github.com/tomshaw/electricgrid/commit/8f1acb2ad1514e9a69a041bc8cb49e20a66df15c))

## [2.3.0](https://github.com/tomshaw/electricgrid/compare/v2.2.0...v2.3.0) (2026-03-23)


### Features

* update composer.json to support Laravel 13 and Orchestra Testbench 12 ([3e4317b](https://github.com/tomshaw/electricgrid/commit/3e4317bd5f2e78a4c443a7151bdb69566e779e48))


### Miscellaneous Chores

* **deps:** bump actions/cache from 4 to 5 ([f698909](https://github.com/tomshaw/electricgrid/commit/f698909d50df5cb5097606d301e3e8304d9b7c50))
* **deps:** bump actions/cache from 4 to 5 ([561bb16](https://github.com/tomshaw/electricgrid/commit/561bb16759c3fcc3f6ac8aed1a5384e1f69f261d))

## [2.2.0](https://github.com/tomshaw/electricgrid/compare/v2.1.2...v2.2.0) (2026-02-20)


### Features

* enhance builder method to support in-memory data sources and update related components ([40a1634](https://github.com/tomshaw/electricgrid/commit/40a163448086006fec00f7f1fc3a30238ce7cfc8))


### Bug Fixes

* update badge logos in README to use GitHub branding ([eeccc4d](https://github.com/tomshaw/electricgrid/commit/eeccc4db4253aba6781ad2ae6589f641dd078da4))
* update badge styles in README for consistency with GitHub branding ([702d795](https://github.com/tomshaw/electricgrid/commit/702d795523e8ef1c84f0024223ca9003da47e8d8))

## [2.1.2](https://github.com/tomshaw/electricgrid/compare/v2.1.1...v2.1.2) (2026-02-20)


### Bug Fixes

* update issue templates and remove unused files; enhance table styling in components ([38847bd](https://github.com/tomshaw/electricgrid/commit/38847bd2130b1a859602a7791fa72aa9ac1a9999))

## [2.1.1](https://github.com/tomshaw/electricgrid/compare/v2.1.0...v2.1.1) (2026-02-02)


### Miscellaneous Chores

* update .gitignore to include .claude and .cursor files ([debf4ee](https://github.com/tomshaw/electricgrid/commit/debf4eec34810812429ab54f95ffdf8bbd34fa2a))

## [2.1.0](https://github.com/tomshaw/electricgrid/compare/v2.0.1...v2.1.0) (2026-01-30)


### Features

* add exportCallback method and transformColumnsForExport for custom export formatting ([5c908ba](https://github.com/tomshaw/electricgrid/commit/5c908ba49f19035748cc144b8a505fe64caf3e03))


### Bug Fixes

* rename style property to styles in Column class and update references ([92da8d3](https://github.com/tomshaw/electricgrid/commit/92da8d33e6795f08522250020d75226f942de16a))

## [2.0.1](https://github.com/tomshaw/electricgrid/compare/v2.0.0...v2.0.1) (2026-01-29)


### Bug Fixes

* use Paginator to resolve current page in pagination method ([1e08de4](https://github.com/tomshaw/electricgrid/commit/1e08de48bf522f44541d2c36f787bd1b53ca5790))

## [2.0.0](https://github.com/tomshaw/electricgrid/compare/v1.5.1...v2.0.0) (2026-01-22)


### ⚠ BREAKING CHANGES

* upgrade livewire to v4

### Features

* upgrade livewire to v4 ([a43317c](https://github.com/tomshaw/electricgrid/commit/a43317c69647cf0eb16e43c11e5521bdaa8a96e4))

## [1.5.1](https://github.com/tomshaw/electricgrid/compare/v1.5.0...v1.5.1) (2026-01-09)


### Bug Fixes

* handle empty values in date and time filtering for CollectionDataSource ([52dd389](https://github.com/tomshaw/electricgrid/commit/52dd3890814d9411853dab9600ab00322a763782))

## [1.5.0](https://github.com/tomshaw/electricgrid/compare/v1.4.1...v1.5.0) (2026-01-09)


### Features

* add date and time filtering capabilities to CollectionDataSource ([75dae8d](https://github.com/tomshaw/electricgrid/commit/75dae8d7acea50bd79952587675b9e25670bd6f4))

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
