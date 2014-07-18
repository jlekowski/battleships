
/**
 * Interface interaction
 *
 * @author     Jerzy Lekowski <jerzy@lekowski.pl>
 * @version    0.5.1
 * @link       http://dev.lekowski.pl
 * @since      File available since Release 0.1b
 *
 * @todo       0. game_started and set_turn take into consideration other player started
 * @todo       1. Move variables and function into Battleships object
 *
 */

$().ready(function() {
    var Battleships = new BattleshipsClass();
    Battleships.run();
});
