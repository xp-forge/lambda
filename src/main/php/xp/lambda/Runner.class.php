<?php namespace xp\lambda;

use io\{Path, File};
use lang\Process;
use util\cmd\Console;

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
 * - Invoke lambda
 *   ```sh
 *   $ xp lambda invoke Greet '{"name":"Test"}'
 *   ```
 * This command requires Docker to be installed!
 */
class Runner {

  /** Returns a given docker image, building it if necessary */
  private static function image(string $docker, string $name, array $dependencies= [], bool $rebuild= false): string {
    $image= "lambda-xp-{$name}";

    $rebuild ? $out= [] : exec("{$docker} image ls -q {$image}", $out, $result);
    if (empty($out)) {

      // Ensure dependencies exist
      foreach ($dependencies as $dependency => $transitive) {
        self::image($docker, $dependency, $transitive);
      }

      // Build this
      $file= new File(__DIR__, 'Dockerfile.'.$name);
      passthru("{$docker} build -t {$image} -f {$file} .", $result);
    }

    return $image;
  }

  /** Entry point */
  public static function main(array $args): int {
    $docker= '"'.Process::resolve('docker').'"';

    switch ($args[0] ?? null) {
      case 'runtime':
        $rebuild= '-b' === ($args[1] ?? null);
        $runtime= self::image($docker, 'runtime', [], $rebuild);
        $target= new Path('runtime.zip');
        $container= uniqid();

        $commands= [
          "{$docker} create --name {$container} {$runtime}",
          "{$docker} cp {$container}:/opt/php/runtime.zip {$target}",
          "{$docker} rm -f {$container}",
        ];

        Console::writeLine('[+] Creating ', $target);
        foreach ($commands as $command) {
          Console::writeLinef("\e[34m => %s\e[0m", $command);
          exec($command, $out, $result);
        }

        Console::writeLine();
        Console::writeLine('Wrote ', filesize($target), ' bytes');
        return $result;

      case 'invoke':
        $invoke= self::image($docker, 'invoke', ['runtime' => []]);
        $cwd= getcwd();
        $handler= $args[1] ?? 'Handler';
        $payload= '"'.str_replace('"', '\\"', $args[2] ?? '{}').'';

        passthru("{$docker} run --rm -v {$cwd}:/var/task:ro {$invoke} {$handler} {$payload}", $result);
        return $result;

      case null:
        Console::writeLine('Missing command, expecting `xp lambda invoke` or `xp lambda runtime`');
        return 2;

      default:
        Console::writeLine('Unknown command "', $args[0], '"');
        return 2;
    }
  }
}