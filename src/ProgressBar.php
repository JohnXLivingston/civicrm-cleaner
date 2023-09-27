<?php

namespace CiviCleaner;

/**
 * Simple CLI progress bar class.
 */
class ProgressBar {
  /**
   * Current step.
   *
   * @var int
   */
  protected $current = 0;

  /**
   * Total steps.
   *
   * @var int
   */
  protected $total;

  /**
   * The calling Civicleaner/Cmd object, with a log method.
   *
   * @var Object
   */
  protected $cmd;

  /**
   * New ProgressBar.
   */
  public function __construct($total, $cmd) {
    $this->total = $total;
    $this->cmd = $cmd;
    $cmd->log("\n");
  }

  /**
   * Increment by 1 the current step.
   *
   * Prints the progess (every N steps, N depending of the total lines)
   */
  public function step() {
    $this->current++;
    $this->cmd->log($this->progress());
  }

  /**
   * Prints the progress line.
   */
  public function progress() {
    $width = 50;
    $info = $this->current . '/' . $this->total;
    $perc = round(($this->current * 100) / $this->total);
    $bar = round(($width * $perc) / 100);
    return sprintf("%s%%[%s>%s] %s\r", $perc, str_repeat("=", $bar), str_repeat(" ", $width - $bar), $info);
  }

}
