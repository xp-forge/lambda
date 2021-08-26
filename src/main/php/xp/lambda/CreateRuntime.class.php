<?php namespace xp\lambda;

use io\Path;
use util\cmd\Console;

class CreateRuntime {
  use Docker;

  private $target, $rebuild;

  public function __construct(Path $target, bool $rebuild= false) {
    $this->target= $target;
    $this->rebuild= $rebuild;
  }

  public function run(): int {
    $docker= $this->command();
    $runtime= $this->image('runtime', [], $this->rebuild);
    $container= uniqid();

    $commands= [
      "{$docker} create --name {$container} {$runtime}",
      "{$docker} cp {$container}:/opt/php/runtime.zip {$this->target}",
      "{$docker} rm -f {$container}",
    ];

    Console::writeLine('[+] Creating ', $this->target);
    foreach ($commands as $command) {
      Console::writeLinef("\e[34m => %s\e[0m", $command);
      exec($command, $out, $result);
    }

    Console::writeLine();
    Console::writeLine('Wrote ', number_format(filesize($this->target)), ' bytes');
    return $result;
  }
}