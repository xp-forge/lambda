<?php namespace com\amazon\aws\lambda\unittest;

use io\streams\MemoryInputStream;
use peer\http\{HttpConnection, HttpOutputStream, HttpRequest, HttpResponse};

class TestConnection extends HttpConnection {

  private function header($request) {
    $header= $request->method.' '.$request->target.' HTTP/'.$request->version."\r\n";
    foreach ($request->headers as $name => $values) {
      foreach ($values as $value) {
        $header.= $name.': '.$value."\r\n";
      }
    }
    return $header;
  }

  private function response($bytes) {
    return new HttpResponse(new MemoryInputStream(sprintf(
      "HTTP/1.1 200 OK\r\nContent-Type: text/plain\r\nContent-Length: %d\r\n\r\n%s",
      strlen($bytes),
      $bytes
    )));
  }

  public function send(HttpRequest $request) { return $this->response($request->getRequestString()); }

  public function open(HttpRequest $request) { return new TestOutputStream($this->header($request)); }

  public function finish(HttpOutputStream $stream) { return $this->response($stream->bytes); }
}