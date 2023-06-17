<?php namespace com\amazon\aws\lambda\unittest;

use com\amazon\aws\lambda\Stream;

class TestStream implements Stream {
  public $written= '';

  public function transmit($source, $mime= null) { /* NOOP */ }

  public function use($mime) { /** NOOP */ }

  public function write($bytes) { $this->written.= $bytes; }

  public function end() { /** NOOP */ }
}