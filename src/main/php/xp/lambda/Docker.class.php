<?php namespace xp\lambda;

use io\Path;
use lang\{CommandLine, Process, IllegalStateException};

trait Docker {
  private $command= null;

  /**
   * Resolves a list of commands. Raises an exception if none of the passed
   * commands is found.
   *
   * @param  string[] $commands
   * @return string
   * @throws lang.IllegalStateException
   */
  private function resolve($commands) {
    $c= CommandLine::forName(PHP_OS);
    foreach ($commands as $command) {
      foreach ($c->resolve($command) as $resolved) {
        return $resolved;
      }
    }

    throw new IllegalStateException('Cannot find any of '.implode(', ', $commands));
  }

  /** Returns docker runtime */
  private function command() {
    return $this->command ?? $this->command= $this->resolve(['docker', 'podman']);
  }

  /** Runs docker with arguments */
  private function passthru($arguments) {
    return (new Process($this->command(), $arguments, null, null, [STDIN, STDOUT, STDERR]))->close();
  }

  /** Returns a given docker image, building it if necessary */
  private function image(string $name, string $version, array $dependencies= [], bool $rebuild= false): array {
    $image= "lambda-xp-{$name}:{$version}";
    $images= [$name => $image];

    if ($rebuild) {
      $out= null;
    } else {
      $p= new Process($this->command(), ['image', 'ls', '-q', $image], null, null, [0 => STDIN, 2 => STDERR]);
      $out= $p->out->readLine();
      $p->close();
    }

    if (empty($out)) {

      // Support 3-digit `6.1.0` as well as 4-digit `6.1.0.1234` formats
      $runners= preg_replace('/^(\d+\.\d+\.\d+)(.*)/', '$1', getenv('XP_VERSION'));

      // Ensure dependencies exist
      foreach ($dependencies as $dependency => $transitive) {
        $images+= $this->image($dependency, $version, $transitive, $rebuild);
      }

      // Build this
      $result= $this->passthru([
        'build',
        '-t', $image,
        '--build-arg', "php_version={$version}",
        '--build-arg', "xp_version={$runners}",
        '-f', new Path(__DIR__, 'Dockerfile.'.$name),
        '.'
      ]);
      0 === $result || $images[$name]= null;
    }

    return $images;
  }
}