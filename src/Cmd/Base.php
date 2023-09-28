<?php

namespace CiviCleaner\Cmd;

/**
 * Base class for commands. Used to define and use some common options.
 */
class Base {

  /**
   * The parser result.
   *
   * @var Console_CommandLine_Result
   */
  protected $parserResult;

  /**
   * Command arguments.
   *
   * @var array
   */
  protected $commandArgs;

  /**
   * Command options.
   *
   * @var array
   */
  protected $commandOptions;

  /**
   * The output file path.
   *
   * @var string
   */
  protected $outputFilePath;

  /**
   * True if the verbose mode is on.
   *
   * @var bool
   */
  protected $verbose = FALSE;

  /**
   * Output file pointer.
   *
   * @var resource
   */
  private $fpOutput;

  /**
   * Contructor.
   */
  public function __construct($parser_result) {
    $this->parserResult = $parser_result;
    $this->outputFilePath = $this->parserResult->command->args['output_file'];
    if ($parser_result->command->options['verbose']) {
      $this->verbose = TRUE;
    }
    $this->commandArgs = $parser_result->command->args;
    $this->commandOptions = $parser_result->command->options;
  }

  /**
   * Define the command.
   *
   * @param Console_CommandLine $parser
   *   The Console_CommandLine parse.
   * @param Console_CommandLine_Command $command
   *   The command created by the parent class.
   */
  public static function defineCommand($parser, $command) {
    $command->addArgument('output_file', [
      'description' => 'The file where to output the result. Note: if the file exists, the output will be appended.',
      'multiple' => FALSE,
      'optional' => FALSE,
    ]);

    $command->addOption('yes', [
      'description' => 'Force Y response to every confirmation prompt.',
      'optional' => TRUE,
      'short_name' => '-y',
      'long_name' => '--yes',
      'action' => 'StoreTrue',
    ]);
  }

  /**
   * Starts the process.
   */
  public function start() {
    $this->log("Starting...\n");
    // Opening the output file:
    $this->fpOutput = fopen($this->outputFilePath, 'a');
    if (!$this->fpOutput) {
      die("Can't open output file for writing.\n");
    }
  }

  /**
   * Close all file handlers.
   */
  public function stop() {
    $this->log("Closing output file...\n");
    fclose($this->fpOutput);
  }

  /**
   * Prints data in output files.
   *
   * Prints all $data values separated with '|',
   * so it can be considered as CSV file.
   *
   * @var Array $data an array of data.
   */
  protected function output($data) {
    $line = '';
    foreach ($data as $value) {
      // Removing line break and co:
      $value = trim(preg_replace('/\s\s+/', ' ', $value));
      $line .= $value . '|';
    }
    fwrite($this->fpOutput, $line . "\n");
  }

  /**
   * Prints a message on the standard output.
   *
   * @var string $msg the message
   */
  public function log($msg) {
    print($msg);
  }

  /**
   * Ask for confirmation.
   *
   * If the --yes option is used, skips.
   *
   * @param string|undefined $msg
   *   The confirmation message, without trailing line feed.
   *
   * @return bool
   *   Returns TRUE if Yes, FALSE if No.
   */
  protected function askConfirmation($msg = "Are you sure?") {
    if ($this->commandOptions['yes']) {
      return TRUE;
    }
    while (TRUE) {
      print $msg . " [y/n] ";
      flush();
      ob_flush();
      $response = strtolower(trim(fgets(STDIN)));
      if ($response === 'y' || $response === 'yes') {
        return TRUE;
      }
      if ($response === 'n' || $response === 'no') {
        return FALSE;
      }
    }
  }

}
