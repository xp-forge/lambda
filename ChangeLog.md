AWS Lambda change log
=====================

## ?.?.? / ????-??-??

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