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
  private function image(string $name, string $version, array $dependencies= [], bool $rebuild= false): array {
    $image= "lambda-xp-{$name}:{$version}";
    $images= [$name => $image];

    $rebuild ? $out= [] : exec("{$this->command()} image ls -q {$image}", $out, $result);
    if (empty($out)) {

      // Support 3-digit `6.1.0` as well as 4-digit `6.1.0.1234` formats
      $runners= preg_replace('/^(\d+\.\d+\.\d+)(.*)/', '$1', $_ENV['XP_VERSION']);

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