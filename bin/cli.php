#!/bin/php
<?php
/**
 * @file
 * CLI tool to clean CiriCRM contact database.
 *
 * @author John Livingston <git@john-livingston.fr>
 * @license AGPL-v3 https://www.gnu.org/licenses/agpl-3.0.html
 */

if (PHP_SAPI !== 'cli') {
  die("Can only be called in CLI mode");
}

require __DIR__ . '/../vendor/autoload.php';
use CiviCleaner\Cmd\ContactTrash;

// To initialise the CiviCRM env, we have to eval `cv php:boot`.
// This is the official way to do, see:
// https://docs.civicrm.org/dev/en/latest/framework/bootstrap/#independent-scripts
// phpcs:ignore Drupal.Functions.DiscouragedFunctions.Discouraged
eval(`cv php:boot`);

echo "Testing if CiviCRM env is set...\n";
// phpcs:ignore Drupal.Classes.UseGlobalClass.RedundantUseStatement
use CRM_CampagnodonCivicrm_ExtensionUtil as E;

try {
  if (empty(E::path())) {
    die('Missing CRM_CampagnodonCivicrm_ExtensionUtil::path(), it seems that the CiviCRM env is not correctly set.');
  }
}
catch (Throwable $e) {
  echo "ERROR: " . $e . "\n";
  die('It seems that the CiviCRM env is not correctly set.');
}

require_once 'Console/CommandLine.php';
$parser = new Console_CommandLine([
  'description' => 'Civicrm-cleaner CLI tool: allows to easily clean trashed contact on huge databases.',
]);
ContactTrash::defineCommand($parser);

try {
  $result = $parser->parse();
  switch ($result->command_name) {
    case 'contact_trash':
      $ct = new ContactTrash($result);
      $ct->start();
      $ct->stop();
      exit(0);
  }
}
catch (Exception $exc) {
  $parser->displayError($exc->getMessage());
  die();
}
