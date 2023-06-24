<?php namespace com\amazon\aws\lambda\unittest;

use com\amazon\aws\lambda\RuntimeApi;
use test\Before;

abstract class RuntimeTest {
  protected $runtime;

  #[Before]
  public function runtime() {
    $this->runtime= new RuntimeApi(new TestConnection());
  }
}