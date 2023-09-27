# civicrm-cleaner

## Description

civicrm-cleaner is a CLI tool to help clean CiviCRM contact databases.

## License

This project is under [AGPL-v3](./LICENSE) license.

Project maintainer: [John Livingston](https://www.john-livingston.fr).

## Installation

Clone or download the repository in a subfolder of your CiviCRM directory.
TODO: where exactly?

Install production dependencies:

```bash
composer install --no-dev
```

## Usage

```bash
./bin/cli.php --help
./bin/cli.php contact_trash --run rollback -v --max 2  /tmp/result.csv
```

## Developpement

Install dev dependencies:

```bash
composer install
```

To lint the code:

```bash
./vendor/bin/phpcs --standard=vendor/drupal/coder/coder_sniffer/Drupal bin/
```
