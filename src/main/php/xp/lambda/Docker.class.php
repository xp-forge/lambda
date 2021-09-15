<?php namespace xp\lambda;

use io\Path;
use lang\CommandLine;

trait Docker {
  private $command= null;

  /**
   * Resolves a list of commands. Copy of `lang.Process::resolve()` from
   * XP 10.14 in order to be compatible with older XP versions.
   *
   * @see    https://github.com/xp-framework/core/pull/279
   * @param  string[] $commands
   * @return string
   * @throws lang.IllegalStateException
   */
  private function resolve($commands) {
    clearstatcache();

    // PATHEXT is in form ".{EXT}[;.{EXT}[;...]]"
    $extensions= array_merge([''], explode(PATH_SEPARATOR, getenv('PATHEXT')));
    $paths= $paths= explode(PATH_SEPARATOR, getenv('PATH'));
    foreach ($commands as $command) {

      // If the command is in fully qualified form and refers to a file
      // that does not exist (e.g. "C:\DoesNotExist.exe", "\DoesNotExist.com"
      // or /usr/bin/doesnotexist), do not attempt to search for it.
      if ((DIRECTORY_SEPARATOR === $command[0]) || ((strncasecmp(PHP_OS, 'Win', 3) === 0) &&
        strlen($command) > 1 && (':' === $command[1] || '/' === $command[0])
      )) {
        foreach ($extensions as $ext) {
          $q= $command.$ext;
          if (file_exists($q) && !is_dir($q)) return realpath($q);
        }
        continue;
      }

      // Check the PATH environment setting for possible locations of the
      // executable if its name is not a fully qualified path name.
      foreach ($paths as $path) {
        foreach ($extensions as $ext) {
          $q= $path.DIRECTORY_SEPARATOR.$command.$ext;
          if (file_exists($q) && !is_dir($q)) return realpath($q);
        }
      }
    }

    throw new IllegalStateException('Cannot find any of '.implode(', ', $commands));
  }

  /** Returns docker runtime */
  private function command() {
    return $this->command ?? $this->command= CommandLine::forName(PHP_OS)->compose($this->resolve(['docker', 'podman']));
  }

  /** Returns a given docker image, building it if necessary */
  private function image(string $name, string $version, array $dependencies= [], bool $rebuild= false): array {
    $image= "lambda-xp-{$name}:{$version}";
    $images= [$name => $image];

    $rebuild ? $out= [] : exec("{$this->command()} image ls -q {$image}", $out, $result);
    if (empty($out)) {

      // Support 3-digit `6.1.0` as well as 4-digit `6.1.0.1234` formats
      $runners= preg_replace('/^(\d+\.\d+\.\d+)(.*)/', '$1', getenv('XP_VERSION'));

      // Ensure dependencies exist
      foreach ($dependencies as $dependency => $transitive) {
        $images+= $this->image($dependency, $version, $transitive, $rebuild);
      }

      // Build this
      $file= new Path(__DIR__, 'Dockerfile.'.$name);
      passthru("{$this->command()} build -t {$image} --build-arg php_version={$version} --build-arg xp_version={$runners} -f {$file} .", $result);
      0 === $result || $images[$name]= null;
    }

    return $images;
  }
}