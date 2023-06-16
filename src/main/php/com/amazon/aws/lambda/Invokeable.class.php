<?php namespace com\amazon\aws\lambda;

class Invokeable {
  public $callable, $invokeMode;

  public function __construct($callable, $invokeMode) {
    $this->callable= $callable;
    $this->invokeMode= $invokeMode;
  }
}