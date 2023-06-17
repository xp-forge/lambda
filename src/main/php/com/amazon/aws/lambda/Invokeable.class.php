<?php namespace com\amazon\aws\lambda;

class Invokeable {
  public $callable, $invokeMode;

  public function __construct($callable, InvokeMode $invokeMode) {
    $this->callable= $callable;
    $this->invokeMode= $invokeMode;
  }

  public function invoke($event, Context $context) {
    return $this->invokeMode->invoke($this->callable, $event, $context);
  }
}