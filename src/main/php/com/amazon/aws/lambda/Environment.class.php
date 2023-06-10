<?php namespace com\amazon\aws\lambda;

use com\amazon\aws\{Credentials, ServiceEndpoint};
use io\Path;
use io\streams\StringWriter;
use lang\{ElementNotFoundException, Environment as System};
use util\cmd\Console;
use util\{FilesystemPropertySource, PropertyAccess};

/**
 * Runtime environment of a lambda
 *
 * @test  com.amazon.aws.lambda.unittest.EnvironmentTest
 * @see   https://docs.aws.amazon.com/lambda/latest/dg/configuration-envvars.html
 */
class Environment {
  public $root, $variables, $writer, $properties;

  /** Creates a new environment */
  public function __construct(string $root, StringWriter $writer= null, array $variables= null) {
    $this->root= $root;
    $this->variables= $variables ?? System::variables();
    $this->writer= $writer ?? Console::$out;
    $this->properties= new FilesystemPropertySource($root);
  }

  /** Returns this environment's root path */
  public function taskroot(): Path { return new Path($this->root); }

  /** Returns a path inside this environment's root path */
  public function path(string $path): Path { return new Path($this->root, $path); }

  /** Returns temporary directory */
  public function tempDir(): Path {
    foreach (['TEMP', 'TMP', 'TMPDIR', 'TEMPDIR'] as $variable) {
      if (isset($this->variables[$variable])) return new Path($this->variables[$variable]);
    }
    return new Path(sys_get_temp_dir());
  }

  /** Returns whether this is a local invocation */
  public function local(): bool {
    return isset($this->variables['AWS_LOCAL']);
  }

  /**
   * Returns a given environment variable
   *
   * @param  string $name
   * @return ?string
   */
  public function variable($name) {
    return $this->variables[$name] ?? null;
  }

  /**
   * Returns credentials from this environment
   *
   * @return com.amazon.aws.Credentials
   */
  public function credentials() {
    return new Credentials(
      $this->variables['AWS_ACCESS_KEY_ID'],
      $this->variables['AWS_SECRET_ACCESS_KEY'],
      $this->variables['AWS_SESSION_TOKEN'] ?? null
    );
  }

  /**
   * Returns an endpoint for a given service
   *
   * @param  string $service
   * @return com.amazon.aws.ServiceEndpoint
   */
  public function endpoint($service) {
    return new ServiceEndpoint($service, $this->credentials());
  }

  /**
   * Writes a trace message
   *
   * @param  var... $args
   * @return void
   */
  public function trace(... $args) {
    $this->writer->writeLine(...$args);
  }

  /**
   * Returns properties with a given name
   * 
   * @throws lang.ElementNotFoundException
   */
  public function properties(string $name): PropertyAccess {
    if ($this->properties->provides($name)) return $this->properties->fetch($name);

    throw new ElementNotFoundException('Cannot find properties "'.$name.'" in '.$this->properties->toString());
  }
}