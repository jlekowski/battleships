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
