<?php namespace xp\lambda;

use io\Path;
use lang\{Process, CommandLine};

trait Docker {
  private $command= null;

  /** Returns docker runtime */
  private function command() {
    return $this->command ?? $this->command= CommandLine::forName(PHP_OS)->compose(Process::resolve('docker'), []);
  }

  /** Returns a given docker image, building it if necessary */
  private function image(string $name, string $version, array $dependencies= [], bool $rebuild= false): string {
    $image= "lambda-xp-{$name}:{$version}";

    $rebuild ? $out= [] : exec("{$this->command()} image ls -q {$image}", $out, $result);
    if (empty($out)) {

      // Ensure dependencies exist
      foreach ($dependencies as $dependency => $transitive) {
        $this->image($dependency, $version, $transitive);
      }

      // Build this
      $file= new Path(__DIR__, 'Dockerfile.'.$name);
      passthru("{$this->command()} build -t {$image} --build-arg php_version={$version} -f {$file} .", $result);
    }

    return $image;
  }
}