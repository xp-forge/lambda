<?php namespace xp\lambda;

use util\cmd\Console;

class DisplayError {
  private $message;

  public function __construct($message) {
    $this->message= $message;
  }

  public function run(): int {
    Console::$err->writeLine($this->message);
    return 2;
  }
}