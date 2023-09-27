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
  }

  /**
   * Starts the process.
   */
  public function start() {
    parent::start();

    $this->log('Getting contacts IDs...');
    $contact_ids = $this->getContactsIds();
    $total = count($contact_ids);
    $this->log('Number of found contact IDs: ' . $total);

    $progress = new ProgressBar($total, $this);

    foreach ($contact_ids as $id) {
      if ($this->commandOptions['usleep']) {
        usleep($this->commandOptions['usleep'] * 1000);
      }
      $progress->step();
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

}
