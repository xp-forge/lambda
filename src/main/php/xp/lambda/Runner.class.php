<?php namespace xp\lambda;

use io\Path;
use peer\http\HttpConnection;
use text\json\{Json, StreamInput};

/**
 * XP AWS Lambda
 * =============
 *
 * - Store runtime layer as `runtime.zip`, building if necessary:
 *   ```sh
 *   $ xp lambda runtime
 *   ```
 * - Rebuild runtime:
 *   ```sh
 *   $ xp lambda runtime -b
 *   ```
 * - Test lambda:
 *   ```sh
 *   $ xp lambda test Greet '{"name":"Test"}'
 *   ```
 * - Package `function.zip` file for deployment, including `src` and `vendor`:
 *   ```sh
 *   $ xp lambda package Greet.class.php
 *   ```
 * This command requires Docker to be installed!
 */
class Runner {
  const PHP_RELEASES = 'https://www.php.net/releases/';

  /** Fetches JSON for a given URL */
  private static function fetch(string $query) {
    return Json::read(new StreamInput((new HttpConnection(self::PHP_RELEASES.'?'.$query))->get()->in()));
  }

  /** Resolves the PHP version */
  private static function resolve(string $version= null): string {
    if (null === $version) {
      $select= ['version' => PHP_VERSION];
    } else if ('latest' === $version) {
      $r= self::fetch('json');
      $select= $r[key($r)];
    } else {
      $select= self::fetch('json&version='.$version);
    }

    return $select['version'];
  }

  /** Returns the command instance for the given name and arguments */
  private static function command(string $name, array $args): object {
    sscanf($name, "%[^:]:%[^\r]", $command, $version);
    switch ($command) {
      case 'package': return new PackageLambda(new Path('function.zip'), new Sources(new Path('.'), [...$args, 'src', 'vendor']));
      case 'runtime': return new CreateRuntime(self::resolve($version), new Path('runtime-%s.zip'), in_array('-b', $args));
      case 'test': return new TestLambda(self::resolve($version), new Path('.'), $args[0] ?? 'Handler', $args[1] ?? '{}');
      default: return new DisplayError('Unknown command "'.$args[0].'"');
    }
  }

  /** Entry point */
  public static function main(array $args): int {
    if (empty($args)) {
      $c= new DisplayError('Missing command, expecting `xp lambda runtime`, `xp lambda test` or `xp lambda package`');
    } else {
      $c= self::command($args[0], array_slice($args, 1));
    }
    return $c->run();
  }
}