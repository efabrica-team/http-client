# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]
### Added
- Rewrite of RetryStrategy - lots of code removed, made much simpler and cleaner. (BC Break)
- Some classes were made final. (BC Break)
- Content responses are now buffered by default if Tracy is enabled.
### Removed
- Deprecated RetryStrategy class is now removed. (BC Break)
- SSLContext cannot be false in withOptions anymore. It must be null or an instance of SSLContext. (BC Break)

## [0.2.4] - 2024-04-09
### Added
- Tracy HttpPanel improvements

## [0.2.3] - 2024-04-04
### Fixed
- RetryStrategy PHPDoc

## [0.2.2] - 2024-04-04
### Added
- RetryStrategy::multi()

## [0.2.1] - 2024-04-03
### Fixed
- PHP8.1 require

## [0.2.0] - 2024-04-03
### Added
- Support both AMPHP2 and AMPHP3
- remove AMPHP2 from dependencies, both are optional

## [0.1.0] - 2024-03-27
- Initial release

[Unreleased]: https://github.com/efabrica-team/http-client/compare/0.2.4...master
[0.2.4]: https://github.com/efabrica-team/http-client/compare/0.2.3...0.2.4
[0.2.3]: https://github.com/efabrica-team/http-client/compare/0.2.2...0.2.3
[0.2.2]: https://github.com/efabrica-team/http-client/compare/0.2.1...0.2.2
[0.2.1]: https://github.com/efabrica-team/http-client/compare/0.2.0...0.2.1
[0.2.0]: https://github.com/efabrica-team/http-client/compare/0.1.0...0.2.0
[0.1.0]: https://github.com/efabrica-team/http-client/compare/...0.1.0
