<?php namespace xp\lambda;

use io\Path;

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

  /** Entry point */
  public static function main(array $args): int {
    switch ($args[0] ?? null) {
      case 'runtime': 
        $c= new CreateRuntime(new Path('runtime.zip'), in_array('-b', $args));
        break;

      case 'test':
        $c= new TestLambda(new Path('.'), $args[1] ?? 'Handler', $args[2] ?? '{}');
        break;

      case 'package':
        $c= new PackageLambda(new Path('function.zip'), new Path('.'), [...array_slice($args, 1), 'src', 'vendor']);
        break;

      case null:
        $c= new DisplayError('Missing command, expecting `xp lambda runtime`, `xp lambda test` or `xp lambda package`');
        break;

      default:
        $c= new DisplayError('Unknown command "'.$args[0].'"');
        break;
    }

    return $c->run();
  }
}