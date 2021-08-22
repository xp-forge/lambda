AWS Lambda change log
=====================

## ?.?.? / ????-??-??

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