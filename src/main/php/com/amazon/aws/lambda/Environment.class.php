<?php namespace com\amazon\aws\lambda;

use io\Path;
use io\streams\StringWriter;
use lang\{ElementNotFoundException, Environment as System};
use util\cmd\Console;
use util\{FilesystemPropertySource, PropertyAccess};

/**
 * Runtime environment of a lambda
 *
 * @test  com.amazon.aws.lambda.unittest.EnvironmentTest
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