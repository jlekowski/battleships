# Battleships

## Battleships (sea battle) game for web

This is a playable beta version of Battleships.
This is also beta github commit.

App is by default set in dev/debug mode for long calls (which might not work well on some of shared hosting).

### DEMO
http://dev.lekowski.pl/


## === Installation ===
1. Download from https://github.com/jlekowski/battleships/
2. Copy to your web server directory (advised to point domain to html folder)
 * You might need to add writing permission to db\ directory (chmod 777)
 * You might need change to short calls (see setup paragraph)
3. Enter the URL and enjoy the game

## === Setup ===

#### config/config.php
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

#### html/js/main.js
* debug
 * set to true for debugging mode