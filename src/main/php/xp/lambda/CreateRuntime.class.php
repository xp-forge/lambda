<?php namespace xp\lambda;

use io\Path;
use util\cmd\Console;

class CreateRuntime {
  use Docker;

  private $version, $target, $rebuild;

  public function __construct(string $version, Path $target, bool $rebuild= false) {
    $this->version= $version;
    $this->target= new Path(sprintf($target, $version));
    $this->rebuild= $rebuild;
  }

  public function run(): int {
    $docker= $this->command();
    $runtime= $this->image('runtime', $this->version, [], $this->rebuild);

    $code= "echo ' => PHP ', PHP_VERSION, ' & Zend ', zend_version(), ' @ ', php_uname(), PHP_EOL, ' => ', implode(' ', get_loaded_extensions()), PHP_EOL;";
    Console::writeLine();
    Console::writeLine("[+] Running {$runtime}\e[34m");
    passthru("{$docker} run {$runtime} /opt/php/bin/php -r \"{$code}\"", $result);
    Console::writeLine("\e[0m");

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