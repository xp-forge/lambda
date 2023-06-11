AWS Lambda change log
=====================

## ?.?.? / ????-??-??

## 4.5.0 / 2023-06-11

* Check whether configured / passed handler extends the `Handler` base
  class and fail with a dedicated error message otherwise
  (@thekid)

## 4.4.0 / 2023-06-10

* Merged PR #21: Implement `xp lambda run ...` to run lambdas locally
  (@thekid)

## 4.3.0 / 2023-03-15

* Merged PR #20: Add `Environment::endpoint()` to return AWS service
  endpoints
  (@thekid)
* Merged PR #19: Extract AWS core library - containing credentials
  and AWS service endpoints, see https://github.com/xp-forge/aws
* Merged PR #18: Make credentials accessible via `credentials()` in
  `Environment` (instead of having to use *AWS_ACCESS_KEY_ID/...*
  environment variables).
  (@thekid)
* Merged PR #17: Migrate to new testing library - @thekid

## 4.2.0 / 2022-07-14

* Merged PR #16: Include exception cause in error output - @thekid

## 4.1.0 / 2022-07-14

* Added [zlib extension](https://www.php.net/zlib), see #8 - @thekid

## 4.0.1 / 2022-02-26

* Fixed "Creation of dynamic property" warnings in PHP 8.2 - @thekid

## 4.0.0 / 2021-11-23

This major release upgrades the base image used to compile the runtime
from from Amazon Linux 1 to Amazon Linux 2. It also changes the `test`
image to use the image https://gallery.ecr.aws/lambda/provided

* Merged PR #15: Upgrade to Amazon Linux 2 - @thekid

## 3.2.0 / 2021-11-15

* Merged PR #14: Add ability to pass environment variables to tested lambda
  (@thekid)

## 3.1.0 / 2021-11-11

* Enabled SimpleXML extension in order to be able to support AWS SDK
  see https://github.com/xp-forge/lambda/issues/8#issuecomment-966308720
  (@thekid)

## 3.0.2 / 2021-10-21

* Made library compatible with new major release of `xp-forge/json`
  (@thekid)

## 3.0.1 / 2021-10-21

* Made library compatible with XP 11, newer `xp-framework/zip` library
  (@thekid)

## 3.0.0 / 2021-09-26

This major release drops backwards compatibility with older XP Framework
releases. XP 10 was released roughly two years ago at the time of writing.

* Merged PR #13: Refactor container execution to Process API provided by
  XP 10.14. Drops BC with XP 9 and lower, see xp-framework/rfc#341.
  (@thekid)

## 2.3.0 / 2021-09-24

* Merged PR #11: Enable XML extensions. The PHP extensions dom, libxml,
  xml, xmlreader and xmlwriter are now available. See discussion in #8
  (@thekid)

## 2.2.0 / 2021-09-15

* Implemented feature request #9: Add podman support - @thekid

## 2.1.1 / 2021-09-15

* Fixed issue #10: lang.IndexOutOfBoundsException (Undefined index: XP_VERSION)
  on systems where the PHP *variables_order* setting does not include `E`.
  (@thekid)

## 2.1.0 / 2021-08-30

* Sped up build by using `-j $(nproc)` flag for `make`, see #7 - @thekid
* Enabled *bcmath* extension - @thekid

## 2.0.0 / 2021-08-29

This major release changes the packaging defaults. This way, files to be
incuded can be specified more flexibly via command line arguments.

* Increased code coverage for classes in public API to 100%, see issue #6
  (@thekid)
* Fixed `Context::remainingTime()`'s return type, which can include NULL.
  (@thekid)
* Changed packaging to no longer include `src` directory automatically,
  it might contain static resources like e.g. a web application's images.
  Typically, `xp lambda package src/main/php` will be what you want.
  (@thekid)

## 1.1.0 / 2021-08-28

* Added accessor for environment varibales, `Environment::variable()`
  (@thekid)
* Added accessor for temporary directory, `Environment::tempDir()`
  (@thekid)
* Added accessor for environment root path, `Environment::taskroot()`
  (@thekid)

## 1.0.0 / 2021-08-28

The first major release includes final touches to the packaged ZIP file.

* Merged PR #5: Unique given sources, preventing them being added to the
  ZIP file twice
  (@thekid)
* Also support bzip2 compression if PHP extension is loaded, but keep
  preference on gzip, which has performed better in my tests.
  (@thekid)

## 0.7.0 / 2021-08-27

* Fixed issue #3: Context object compatibility. Context now also declares
  the properties *logGroupName* and *logStreamName* like in NodeJS, and
  uses *memoryLimitInMB* instead of *memorySize*.
  (@thekid)
* Merged PR #4: Pass version along to docker images. Use `xp lambda runtime`
  to use the current PHP version, `xp lambda runtime:8.0` to use the newest
  PHP 8.0 release, and `xp lambda runtime:8.0.10` for version pinning.
  (@thekid)
* Ignore *any* directories inside `src/test` and `src/it` as well any hidden
  directories when packaging.
  (@thekid)

## 0.6.0 / 2021-08-23

* Decreased size of runtime layer from ~8.4 MB to ~2.4 MB by using `strip`
  and `zip -9`, reducing layer publishing time as well as initialization duration
  https://stackoverflow.com/questions/4179010/how-to-strip-executables-thoroughly
  (@thekid)

## 0.5.0 / 2021-08-22

* Renamed `task.zip` to `function.zip` in order not to introduce more
  vocabulary than necessary
  (@thekid)

## 0.4.0 / 2021-08-22

* Renamed `xp lambda invoke` to `xp lambda test` to make clear we're
  not invoking the deployed lambda
  (@thekid)

## 0.3.0 / 2021-08-22

* Implemented `xp lambda package` subcommand for packaging lambda code
  including the *src* and *vendor* directories as requested in issue #2
  (@thekid)
* Renamed context member payload to payloadLength - @thekid

## 0.2.0 / 2021-08-21

* Fixed environment passed to execution context - @thekid
* Made it possible to trigger a runtime rebuild via `-b` - @thekid

## 0.1.0 / 2021-08-20

* Hello World! First release - @thekid