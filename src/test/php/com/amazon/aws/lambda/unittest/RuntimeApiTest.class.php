<?php namespace com\amazon\aws\lambda\unittest;

use com\amazon\aws\lambda\RuntimeApi;
use test\{Assert, Test};

class RuntimeApiTest {

  #[Test]
  public function can_create() {
    new RuntimeApi('test');
  }

  #[Test]
  public function default_version() {
    Assert::equals('2018-06-01', (new RuntimeApi('test'))->version);
  }

  #[Test]
  public function latest_version() {
    Assert::equals('latest', (new RuntimeApi('test', 'latest'))->version);
  }

  #[Test]
  public function endpoint_url() {
    $runtime= new RuntimeApi('localhost:9000');
    Assert::equals('http://localhost:9000/', $runtime->conn->getUrl()->getCanonicalURL());
  }

  #[Test]
  public function endpoint_timeout_is_15_minutes() {
    $runtime= new RuntimeApi('localhost:9000');
    Assert::equals(15 * 60, $runtime->conn->getTimeout());
  }
}