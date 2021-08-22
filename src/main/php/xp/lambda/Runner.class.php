<?php namespace xp\lambda;

use io\archive\zip\{ZipFile, ZipDirEntry, ZipFileEntry, Compression};
use io\streams\StreamTransfer;
use io\{Path, File, Files, Folder};
use lang\{Process, CommandLine};
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
 * - Test lambda:
 *   ```sh
 *   $ xp lambda test Greet '{"name":"Test"}'
 *   ```
 * - Package `task.zip` file for deployment, including `src` and `vendor`:
 *   ```sh
 *   $ xp lambda package Greet.class.php
 *   ```
 * This command requires Docker to be installed!
 */
class Runner {
  const COMPRESSION_THRESHOLD = 24;

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
      $file= new Path(__DIR__, 'Dockerfile.'.$name);
      passthru("{$docker} build -t {$image} -f {$file} .", $result);
    }

    return $image;
  }

  /** Returns ZIP file entries */
  private static function entries(Folder $base, Path $path, Compression $compression, $exclude) {
    $relative= rtrim(str_replace($base->getURI(), '', $path->toString()), DIRECTORY_SEPARATOR);
    if (preg_match($exclude, $relative)) {
      // NOOP
    } else if ($path->isFile()) {
      yield function($z) use($relative, $path, $compression) {
        $file= $z->add(new ZipFileEntry($relative));

        // See https://stackoverflow.com/questions/46716095/minimum-file-size-for-compression-algorithms
        if (filesize($path) > self::COMPRESSION_THRESHOLD) {
          $file->setCompression($compression, 9);
        }
        (new StreamTransfer($path->asFile()->in(), $file->out()))->transferAll();
        return $file;
      };
    } else {
      yield function($z) use($relative) { return $z->add(new ZipDirEntry($relative)); };
      foreach ($path->asFolder()->entries() as $entry) {
        yield from self::entries($base, $entry, $compression, $exclude);
      }
    }
  }

  /** Entry point */
  public static function main(array $args): int {
    $docker= CommandLine::forName(PHP_OS)->compose(Process::resolve('docker'), []);

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
        Console::writeLine('Wrote ', number_format(filesize($target)), ' bytes');
        return $result;

      case 'test':
        $test= self::image($docker, 'test', ['runtime' => []]);
        $cwd= getcwd();
        $handler= $args[1] ?? 'Handler';
        $payload= '"'.str_replace('"', '\\"', $args[2] ?? '{}').'';

        passthru("{$docker} run --rm -v {$cwd}:/var/task:ro {$test} {$handler} {$payload}", $result);
        return $result;

      case 'package':
        $target= new Path('task.zip');
        $base= new Folder('.');
        $sources= [...array_slice($args, 1), 'src', 'vendor'];
        $compression= extension_loaded('zlib') ? Compression::$GZ : Compression::$NONE;
        $exclude= '/src.test.php/';

        Console::writeLine('[+] Creating ', $target, ' (compression: ', $compression, ')');
        $z= ZipFile::create($target->asFile()->out());
        foreach ($sources as $i => $source) {
          Console::writef("\e[34m => [%d/%d] ", $i + 1, sizeof($sources) + 1);
          $entries= 0;
          foreach (self::entries($base, new Path($source), $compression, $exclude) as $action) {
            $entry= $action($z);
            $entries++;
            Console::writef('%-60s %4d%s', substr($entry->getName(), -60), $entries, str_repeat("\x08", 65));
          }
          Console::writeLine("\e[0m");
        }

        $file= $z->add(new ZipFileEntry('class.pth'));
        $file->out()->write(preg_replace($exclude, '?$0', file_get_contents('class.pth')));
        Console::writeLinef("\e[34m => [%1\$d/%1\$d] class.pth\e[0m", sizeof($sources) + 1);

        $z->close();
        Console::writeLine();
        Console::writeLine('Wrote ', number_format(filesize($target)), ' bytes');
        return 0;

      case null:
        Console::writeLine('Missing command, expecting `xp lambda invoke` or `xp lambda runtime`');
        return 2;

      default:
        Console::writeLine('Unknown command "', $args[0], '"');
        return 2;
    }
  }
}