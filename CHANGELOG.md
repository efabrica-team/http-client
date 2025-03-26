# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [0.4.0] - 2025-03-26
### Added
- PassHeaders option to pass headers from the original request. E.g. to track users across multiple services.

## [0.3.7] - 2025-02-10
### Fixed
- Fixed composer.json

## [0.3.6] - 2025-02-10
### Fixed
- Don't reuse connections (leaves PHP-FPM connections hanging)
- force enabled buffer in Shared Traceable client response

## [0.3.5] - 2024-11-12
### Added
- revolt parameter

## [0.3.3] - 2024-10-29
### Fixed
- fix when amphp/amp was not required

## [0.3.2] - 2024-10-21
### Fixed
- fiber execution context fix
### Added
- tracy bluescreen support

## [0.3.1] - 2024-08-21

## [0.3.0] - 2024-05-23
### Added
- Rewrite of RetryStrategy - lots of code removed, made much simpler and cleaner. (BC Break)
- Some classes were made final. (BC Break)
- Content responses are now buffered by default if Tracy is enabled.
- JSON parameter supports non-array values.
- PHPStan level 8

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

[Unreleased]: https://github.com/efabrica-team/http-client/compare/0.4.0...master
[0.4.0]: https://github.com/efabrica-team/http-client/compare/0.3.7...0.4.0
[0.3.7]: https://github.com/efabrica-team/http-client/compare/0.3.6...0.3.7
[0.3.6]: https://github.com/efabrica-team/http-client/compare/0.3.5...0.3.6
[0.3.5]: https://github.com/efabrica-team/http-client/compare/0.3.3...0.3.5
[0.3.3]: https://github.com/efabrica-team/http-client/compare/0.3.2...0.3.3
[0.3.2]: https://github.com/efabrica-team/http-client/compare/0.3.1...0.3.2
[0.3.1]: https://github.com/efabrica-team/http-client/compare/0.3.0...0.3.1
[0.3.0]: https://github.com/efabrica-team/http-client/compare/0.2.4...0.3.0
[0.2.4]: https://github.com/efabrica-team/http-client/compare/0.2.3...0.2.4
[0.2.3]: https://github.com/efabrica-team/http-client/compare/0.2.2...0.2.3
[0.2.2]: https://github.com/efabrica-team/http-client/compare/0.2.1...0.2.2
[0.2.1]: https://github.com/efabrica-team/http-client/compare/0.2.0...0.2.1
[0.2.0]: https://github.com/efabrica-team/http-client/compare/0.1.0...0.2.0
[0.1.0]: https://github.com/efabrica-team/http-client/compare/...0.1.0
