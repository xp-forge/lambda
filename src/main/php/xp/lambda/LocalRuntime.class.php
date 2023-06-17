<?php namespace xp\lambda;

use com\amazon\aws\lambda\{InvokeMode, Stream, RuntimeApi};

class LocalRuntime extends RuntimeApi {
  public $out;

  /** @param io.streams.Writer $out */
  public function __construct($out) { $this->out= $out; }

  /** Returns the local buffered mode */
  public function buffered(): InvokeMode {
    return new class($this, 'BUFFERED') extends InvokeMode {
      public function invoke($lambda, $event, $context) {
        $result= $lambda($event, $context);
        $this->api->out->writeLine($result);
      }
    };
  }

  /** Returns the local streaming mode */
  public function streaming(): InvokeMode {
    return new class($this, 'RESPONSE_STREAM') extends InvokeMode {
      public function invoke($lambda, $event, $context) {
        $stream= new class($this->api) implements Stream {
          private $api;
          public function __construct($api) { $this->api= $api; }
          public function transmit($source, $mime= null) {
            $in= $source instanceof Channel ? $source->in() : $source;
            while ($in->available()) {
              $this->api->out->write($in->read());
            }
          }
          public function use($mime) { /** NOOP */ }
          public function write($bytes) { $this->api->out->write($bytes); }
          public function end() { /** NOOP */ }
        };
        $lambda($event, $stream, $context);
      }
    };
  }
}