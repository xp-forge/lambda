<?php namespace xp\lambda;

use io\Path;
use util\cmd\Console;

class TestLambda {
  use Docker;

  private $version, $path;
  private $environment= [];
  private $handler= 'Handler';
  private $payload= '{}';

  public function __construct(string $version, Path $path, array $args) {
    $this->version= $version;
    $this->path= $path->asRealpath();

    // Separate `-e NAME=VALUE` from handler and payload
    for ($i= 0, $s= sizeof($args); $i < $s; $i++) {
      if ('-e' === $args[$i]) {
        $this->environment[]= $args[++$i];
      } else {
        $this->handler= $args[$i];
        $this->payload= $args[++$i] ?? '{}';
        break;
      }
    }
  }

  /** Passes multiple arguments via command line */
  private function pass($flag, $list) {
    $r= [];
    foreach ($list as $element) {
      $r[]= $flag;
      $r[]= $element;
    }
    return $r;
  }

  /** Runs this command */
  public function run(): int {
    $image= $this->image('test', $this->version, ['runtime' => []])['test'];
    if (null === $image) return 1;

    return $this->passthru([
      'run',
      '--rm',
      '-v', "{$this->path}:/var/task:ro",
      ...$this->pass('-e', $this->environment),
      $image,
      $this->handler,
      $this->payload
    ]);
  }
}