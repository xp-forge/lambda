<?php namespace xp\lambda;

use io\Path;
use util\cmd\Console;

class TestLambda {
  use Docker;

  private $version, $path, $handler, $payload;

  public function __construct(string $version, Path $path, string $handler, string $payload) {
    $this->version= $version;
    $this->path= $path->asRealpath();
    $this->handler= $handler;
    $this->payload= $payload;
  }

  public function run(): int {
    $test= $this->image('test', $this->version, ['runtime' => []])['test'];
    if (null === $test) return 1;

    return $this->passthru(['run', '--rm', '-v', "{$this->path}:/var/task:ro", $test, $this->handler, $this->payload]);
  }
}