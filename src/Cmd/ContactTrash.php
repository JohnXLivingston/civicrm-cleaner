<?php

namespace CiviCleaner\Cmd;

use Civi\Api4\Contact;
use CiviCleaner\ProgressBar;

/**
 * Handle deletion of trashed contacts.
 */
class ContactTrash extends Base {
  /**
   * The Api4 instance to load contact IDs.
   *
   * @var string
   */
  private $getContactIdsAPI;

  /**
   * The run mode.
   *
   * @var string test | run | rollback
   */
  private $runMode;

  /**
   * If stop_at options is set, the timestamp at which we must stop.
   *
   * @var timestamp|false
   */
  private $stopAtTimestamp = FALSE;

  /**
   * ContactTrash contructor.
   */
  public function __construct($parser_result) {
    parent::__construct($parser_result);

    if ($this->verbose) {
      $this->log("Command arguments:\n");
      $this->log(print_r($this->commandArgs, TRUE));
      $this->log("Command options:\n");
      $this->log(print_r($this->commandOptions, TRUE));
    }

    $this->runMode = $this->commandOptions['run_mode'];
    $this->log('Run mode is set to: ' . $this->runMode . "\n");

    if ($this->commandOptions['stop_at']) {
      $this->stopAtTimestamp = strtotime($this->commandOptions['stop_at']);
      if ($this->stopAtTimestamp === FALSE) {
        throw new \Exception('Invalid stop_at option.');
      }
      $this->log(
        "The script will stop at: "
        . date('Y-m-d\TH:i:s.Z\Z', $this->stopAtTimestamp)
        . " \n"
      );
    }

    $this->buildGetContactIdsApi();
  }

  /**
   * Define the command (name, options and arguments).
   *
   * @param Console_CommandLine $parser
   *   The Console_CommandLine parse.
   */
  public static function defineCommand($parser) {
    $command = $parser->addCommand(
      'contact_trash',
      [
        'description' => 'Delete contacts in trash',
      ]
    );
    Base::defineCommand($parser, $command);

    $command->addOption('verbose', [
      'short_name' => '-v',
      'long_name' => '--verbose',
      'description' => 'Prints more verbose informations',
      'action' => 'StoreTrue',
    ]);

    $command->addOption('usleep', [
      'description' => 'The script can wait X milliseconds for each loop, if you want to preserve server resources',
      'long_name' => '--usleep',
      'action' => 'StoreInt',
    ]);

    $command->addOption('run_mode', [
      'description' => 'Run mode: "test", "rollback" or "run". Test: just test data, rollback: try to delete, but then rollback, run: delete for real.',
      'long_name' => '--run',
      'default' => 'test',
      'choices' => [
        'run',
        'test',
        'rollback',
      ],
      'action' => 'StoreString',
    ]);

    $command->addOption('max', [
      'description' => 'Max contact to loop on.',
      'action' => 'StoreInt',
      'optional' => TRUE,
      'long_name' => '--max',
    ]);

    $command->addOption('stop_at', [
      'description' => 'A datetime at which the script must stop.'
      . ' Format: any format accepted by the php strtotime function.'
      . ' Example: 2023-09-28T10:30:30. You can also use: \'+1 hour\'.',
      'action' => 'StoreString',
      'optional' => TRUE,
      'long_name' => '--stop-at',
    ]);
  }

  /**
   * Starts the process.
   */
  public function start() {
    parent::start();

    $this->log('Getting contacts IDs...');
    $contact_ids = $this->getContactsIds();
    $total = count($contact_ids);
    $this->log("\n");
    $this->log('Number of found contact IDs: ' . $total . "\n");

    if ($this->commandOptions['max']) {
      $total = min($total, $this->commandOptions['max']);
      $this->log("\n");
      $this->log("Max param provided, we will stop after " . $total . "\n");
      $this->log("\n");
    }

    if (!$this->askConfirmation("Do you want to process?")) {
      $this->log("\n");
      $this->log("Aborting.\n");
      return;
    }

    $progress = new ProgressBar($total, $this);

    foreach ($contact_ids as $id) {
      if ($progress->currentStep() >= $total) {
        // Force print the progress:
        $progress->progress();
        $this->log("\n");
        $this->log("Stopped because we reached the max (or the total number of contacts)\n");
        break;
      }
      if ($this->stopAtTimestamp !== FALSE && time() >= $this->stopAtTimestamp) {
        // Force print the progress:
        $progress->progress();
        $this->log("\n");
        $this->log("Stopped because we reached the stop at time\n");
        break;
      }
      $progress->step();

      if ($this->commandOptions['usleep']) {
        usleep($this->commandOptions['usleep'] * 1000);
      }

      $line = $this->newResultLine($id);

      $contact = Contact::get()
        ->setCheckPermissions(FALSE)
        ->addSelect('*')
        ->addWhere('id', '=', $id)
        ->execute()
        ->first();

      if (!$contact) {
        $line['error'] = 'Contact not found';
        $this->output($line);
        continue;
      }

      // Check it is in trash!
      if (!$contact['is_deleted']) {
        $line['error'] = 'Contact not deleted';
        $this->output($line);
        continue;
      }

      $line['name'] = $contact['first_name'] . ' ' . $contact['last_name'];

      // TODO: test something like:
      // CRM_Financial_BAO_FinancialItem::checkContactPresent([$contactID], $error).
      // And check others tests made in CiviCRM when deleting.

      if ($this->runMode !== 'run' && $this->runMode !== 'rollback') {
        // Test mode, nothing more to do.
        $this->output($line);
        continue;
      }

      $tx = new \CRM_Core_Transaction();
      try {
        $r = Contact::delete(FALSE)
          ->setCheckPermissions(FALSE)
          ->addWhere('id', '=', $id)
          ->setUseTrash(FALSE)
          ->execute()
          ->single();

        if ($this->runMode !== 'run') {
          $tx->rollback();
          $line['deleted'] = 'Rollbacked';
        }
        else {
          $tx->commit();
          $line['deleted'] = 'Y';
        }
      }
      catch (\Throwable $e) {
        $tx->rollback();
        $line['error'] = $e;
      }
      $this->output($line);
    }
  }

  /**
   * Builds the API4 object to get contact IDs.
   */
  private function buildGetContactIdsApi() {
    $this->getContactIdsAPI = Contact::get()
      ->setCheckPermissions(FALSE)
      ->addSelect('contact.id')
      ->addWhere('is_deleted', '=', TRUE)
      ->addOrderBy('id', 'ASC');

    if ($this->verbose) {
      $this->getContactIdsAPI->setDebug(TRUE);
    }
  }

  /**
   * Returns the contact IDs to process.
   */
  private function getContactsIds() {
    $contacts = $this->getContactIdsAPI->execute();
    if ($this->verbose) {
      $this->log("API debug infos:\n");
      $this->log(print_r($contacts->debug, TRUE));
    }
    $result = [];
    foreach ($contacts as $contact) {
      array_push($result, $contact['id']);
    }
    return $result;
  }

  /**
   * Returns a new result line, that will be usable in $this->output.
   */
  private function newResultLine($id) {
    $line = [];
    $line['id'] = '' . $id;
    $line['name'] = '';
    $line['error'] = '';
    $line['deleted'] = 'N';
    return $line;
  }

}
