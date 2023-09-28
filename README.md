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

Note: avoid running it as root.

## Usage

```bash
./bin/cli.php --help
./bin/cli.php contact_trash --run rollback -v --max 2  /tmp/result.csv
# Note: the script will append the current result to the output file.
# You can add current date to prevent mixing execution results:
./bin/cli.php contact_trash --run rollback -v --max 2 /tmp/result.$(date '+%Y-%m-%d.%H.%M.%S').csv
```

Note: avoid running it as root. Add something like `sudo -u www-data` on front of these commands if you are connected as root.

For a full list of commands, options and arguments, use `--help`.

Note: avoid using ctrl+c to stop the script. It could result in an incomplete output file.

## Developpement

Install dev dependencies:

```bash
composer install
```

To lint the code:

```bash
./vendor/bin/phpcs --standard=vendor/drupal/coder/coder_sniffer/Drupal bin/
```

## Campagnodon

This script is compatible with [Campagnodon](https://github.com/JohnXLivingston/campagnodon_civicrm/).
It will auto-detect if you are using it.
