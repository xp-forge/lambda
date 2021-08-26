<?php namespace xp\lambda;

use io\Path;
use util\cmd\Console;

class TestLambda {
  use Docker;

  private $path, $handler, $payload;

  public function __construct(Path $path, string $handler, string $payload) {
    $this->path= $path->asRealpath();
    $this->handler= $handler;
    $this->payload= $payload;
  }

  public function run(): int {
    $docker= $this->command();
    $test= $this->image('test', ['runtime' => []]);
    $payload= '"'.str_replace('"', '\\"', $this->payload).'"';

    passthru("{$docker} run --rm -v {$this->path}:/var/task:ro {$test} {$this->handler} {$payload}", $result);
    return $result;
  }
}