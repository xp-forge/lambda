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
    $docker= $this->command();
    $test= $this->image('test', $this->version, ['runtime' => []]);
    if (null === $test) return 1;

    $payload= '"'.str_replace('"', '\\"', $this->payload).'"';
    passthru("{$docker} run --rm -v {$this->path}:/var/task:ro {$test} {$this->handler} {$payload}", $result);
    return $result;
  }
}