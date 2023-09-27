<?php

namespace CiviCleaner\Cmd;

use Civi\Api4\Contact;

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

    $this->buildGetContactIdsApi();
  }

  /**
   * Define the command.
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
  }

  /**
   * Starts the process.
   */
  public function start() {
    parent::start();

    $this->log('Getting contacts IDs...');
    $contact_ids = $this->getContactsIds();
    $this->log(print_r($contact_ids, TRUE));
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
