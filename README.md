[![Build Status](https://travis-ci.org/jlekowski/battleships.svg)](https://travis-ci.org/jlekowski/battleships)

# Battleships

## Battleships (sea battle) game for web and command line (PHP CLI)

App is by default set in dev/debug mode for long calls (see **Setup** section) which might not work well on some of shared hosting.

### DEMO
http://dev.lekowski.pl/
OR
from command line run:
php bin/client.php

## === Installation ===
1. Download from https://github.com/jlekowski/battleships/
2. Copy to your web server directory (advised to point domain to public folder)
 * You might need to add writing permission to db\ directory (chmod 777)
 * You might need to add writing permission to log\ directory (chmod 777)
 * You might need change to short calls (see **Setup** section)
3. Enter the URL and enjoy the game

## === Setup ===

#### init/config.php
* SQLITE_FILE
 * change to a random name if your db folder is accessible through the browser
* CHECK_UPDATES_TIMEOUT
 * 120 - suggested for long calls
 * 5   - suggested for short calls
* CHECK_UPDATES_INTERVAL
 * 2 - suggested for long calls
 * 0 - suggested for short calls
* CHECK_UPDATES_COUNT
 * 50 - suggested for long calls
 * 1  - suggested for short calls

#### public/js/main.js
* debug
 * set to true for debugging mode

## === Changelog ===

* version **0.6.1**
 * Changed REST response format
 * Added more unit tests
 * Updated End-to-End test
 * Refactored DB and ApiClient Class
* version **0.6**
 * Moved from SOAP to REST API (both supported, but SOAP will be removed in 0.7)
 * Upgraded client to jQuery v2
 * Added simple End-to-End test
 * Added first unit tests
 * Refactored a lot of code (Battleships object in JS, exceptions instead of errors and others)
* version **0.5.1**
 * Small fix when mod_rewrite turned off
* version **0.5**
 * Refactored code (namespaces, naming, file structure)
 * Added chat information for CLI
 * Fixed bugs (long call settings, coordinates validation)
* version **0.4**
 * Added marking whose turn it is
 * Added random ships setting
* version **0.3**
 * Moved webservice to SOAP
 * Added command line client
* version **0.2.2b**
 * Fixed auto start update and escape encoding
* version **0.2.1b**
 * Fixed check sunk ship bug
* version **0.2b**
 * Fixed bugs
 * Refactored code
 * Updated documentation
* version **0.1b**
 * Created first working battleships game
