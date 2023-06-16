<?php namespace com\amazon\aws\lambda;

interface Streaming {

  /**
   * Transmits a given source to the output asynchronously.
   *
   * @param  io.Channel|io.streams.InputStream $source
   * @param  string $mimeType
   * @return void
   * @throws lang.IllegalArgumentException
   * @throws lang.IllegalStateException
   */
  public function transmit($source, $mimeType= null);

  /**
   * Uses given mime type
   *
   * @param  string $mimeType
   * @return void
   * @throws lang.IllegalStateException
   */
  public function use($mimeType);

  /**
   * Writes to and flushes the stream
   *
   * @param  string $bytes
   * @return void
   * @throws lang.IllegalStateException
   */
  public function write($bytes);

  /**
   * Ends this response stream
   *
   * @return void
   */
  public function end();
}