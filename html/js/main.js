
/**
 * Interface interaction
 *
 * @author     Jerzy Lekowski <jerzy@lekowski.pl>
 * @version    0.1b
 * @link       http://dev.lekowski.pl
 * @since      File available since Release 0.1b
 *
 * @todo       1. Move variables and function into Battleships object
 *
 */

// log events, updates managements
var debug = true;

// battle boards axix Y legend
var axis_y = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J'];
// battle boards axix X legend
var axis_x = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10];
// keys after pushing which we type in chatbox (A-Z, 0-9, :,._+)
var chatbox_keys = "48-57, 59, 65-90, 96-105, 110, 188-191, 219-222";
// whether the game is started
var game_started = false;
// prevents shooting (when game not started or other player's turn)
var shot_prevent = true;
// true - updates are requested (updating ON); false - you can start requesting updates (updating OFF);
var update_execute = !debug;
// inteval between update calls
var update_interval = 3000;
// prevents focusing on chatbox (usually when pressing ctrl/alt + chatbox_key)
var focus_prevent = false;
// updates AJAX object
var updateXHR;
// battle boards
var $battleground;
// chatbox
var $chatbox;
// setTimeout() return value when waiting update_interval for a new update call
var last_timeout;


// default settings for AJAX calls
$.ajaxSetup({
    url:        'webservice.php',
    dataType:   'json',
    cache:      false,
    beforeSend: function(jqXHR, settings) {
        custom_log( settings.url.split("?")[1].split("&") );
    },
    error:      function(jqXHR, textStatus, errorThrown) {
        custom_log(jqXHR.responseText);
    }
});

$(document).ready( function() {
    $battleground = $("div:gt(0) div:not(:first-child)", "div.board");
    $chatbox      = $("#chatbox :text");

    if( debug === true ) {
        $("#update, div.log").show();
    }

    // parse key range to array
    parse_chatbox_keys();

    // when start typing and not focused on text field, focus to type on chatbox
    $(this).keydown( function(event) {
        if( (event.which == 17) || (event.which == 18) ) {
            focus_prevent = true;
            return true;
        }

        if( focus_prevent || $(event.target).is(":text") || ($.inArray(event.which, chatbox_keys) == -1) ) {
            return true;
        }

        $chatbox.focus();
    }).keyup( function(event) {
        focus_prevent = false;
    });

    // first call to load ships, battle and chats
    get_battle();

    // start AJAX calls for updates
    $("#update").triggerHandler('click');

    // board handling
    $battleground.click( function() {
        // marking opponents ships
        if( $battleground.index(this) >= 100 ) {
            shot(this);
        }
        // if game not started set the ship
        else if( !game_started ) {
            $(this).toggleClass("ship");
        }
    });


    // starting the game
    $("#start").click( function() {
        if( game_started ) {
            alert("Game is already started");
            return false;
        }

        var $ships = $battleground.slice(0, 100).filter(".ship");

        if( ships_check($ships) == false ) {
            alert("There is either not enough ships or they're set incorrectly");
            return false;
        }


        var ships_array = $ships.map( function() {
                              return get_coords(this).join('');
                          }).toArray();

        $.ajax({
            data:      {'action': "start_game", 'ships': ships_array},
            success:   function(result) {
                custom_log( result );
                if( result.error !== false ) {
                    return false;
                }

                game_start();
            }
        });
    });


    // updating player's name
    $("#name_update").click( function() {
        $(this).hide().siblings(":text").show().select();
    }).siblings(":text").keyup( function(event) {
        // if pressed ESC - leave the input, if ENTER - process, if other - do nothing
        if( event.which != 13 ) {
            if( event.which == 27 ) {
                $(this).blur();
                $chatbox.focus();
            }

            return true;
        }


        var $input      = $(this);
        var player_name = $input.val();

        $.ajax({
            data:      {'action': "name_update", 'player_name':  player_name},
            success:   function(result) {
                custom_log( result );
                if( result.error !== false ) {
                    return false;
                }

                $input.hide().siblings("span").text(player_name).show();
            }
        });
    }).blur( function() {
        if( $(this).has(":visible") ) {
            var player_name  = $(this).siblings("span").text();

            $(this).hide().val(player_name).siblings("span").show();
        }
    });


    // updates management
    $("#update").click( function() {
        update_execute = !update_execute;

        if( update_execute ) {
            $(this).text("Updates [ON]");
            get_updates();
        }
        else {
            $(this).text("Updates [OFF]");
            stop_update();
        }
    });


    // starts new game
    $("#new_game").click( function() {
        if( confirm("Are you sure you want to quit the current game?") ) {
            window.location = window.location.protocol + "//" + window.location.hostname + window.location.pathname;
        }
    });


    // send chat text
    $chatbox.keyup( function(event) {
        if( event.which != 13 ) {
            return true;
        }

        var text = $.trim($chatbox.val());

        if( text == "" ) {
            return true;
        }

        $chatbox.prop('disabled', true);

        $.ajax({
            data:      {'action': "chat", 'text':  text},
            success:   function(result) {
                custom_log( result );
                if( result.error !== false ) {
                    return false;
                }

                chat_append(text, $("#name_update").text(), result.success);
                $chatbox.val("");
            },
            complete:  function(jqXHR, textStatus) {
                $chatbox.prop('disabled', false);
            }
        });
    });


    // shoot randomly
    $("#random_shot").click( function() {
       var $empty_fields = $battleground.slice(100).not(".miss, .hit");
       // random from 0 to the amount of empty fields - 1 (because first's index is 0)
       var index = Math.floor(Math.random() * $empty_fields.length);
       $empty_fields.eq(index).click();
    });
});


function shot(element) {
    if( !game_started ) {
        alert("You can't shoot at the moment - game is not started")
        return false;
    }

    if( shot_prevent ) {
        alert("You can't shoot at the moment - othe player has not started or it's not your turn!")
        return false;
    }

    var coords = get_coords(element);

    shot_prevent = true;

    $.ajax({
        data:      {'action': "shot", 'coords': coords.join('')},
        success:   function(result) {
            custom_log( result );
            if( result.error !== false ) {
                return false;
            }

            if( result.success != "miss" ) {
                shot_prevent = false;
            }

            mark_shot(1, coords, result.success);
        },
        complete:  function(jqXHR, textStatus) {
            if( textStatus != 'success' ) {
                shot_prevent = false;
            }
        }
    });
}

function get_coords( element ) {
    var index = $battleground.index(element);

    // to convert: 1 -> [0,1], 12 -> [1,2], 167 -> [6,7]
    var temp    = (index >= 100) ? (index - 100) : index;
        temp    = (temp < 10 ? "0" : "") + temp;
    var indexes = temp.split("");

    var cord_y = axis_y[ indexes[1] ];
    var cord_x = axis_x[ indexes[0] ];

    return [cord_y, cord_x];
}

function get_field_by_coords( coords, board_number ) {
    var coords     = $.type(coords) == "array" ? coords : [coords.substr(0, 1), parseInt(coords.substr(1))];
    var position_y = $.inArray(coords[0], axis_y);
    var position_x = $.inArray(coords[1], axis_x);

    // parseInt("08") -> 0
    var position = parseInt([position_x, position_y].join(''), 10) + (board_number * 100);

    return $battleground.eq(position);
}

function mark_shot( board_number, coords, type, tendency ) {
    var coords        = $.type(coords) == "array" ? coords : [coords.substr(0, 1), parseInt(coords.substr(1))];
    var field         = get_field_by_coords(coords, board_number);
    var position_y    = $.inArray(coords[0], axis_y);
    var position_x    = $.inArray(coords[1], axis_x);
    var mark_class    = '';
    var missed_coords = [];

    if( board_number == 0 && type == null ) {
        if( field.hasClass("ship") ) {
            type = is_sunk(coords) ? "sunk" : "hit";
        }
        else {
            type = "miss";
        }
    }


    if( type == "miss" ) {
        mark_class = "miss";
        missed_coords.push( coords );
    }

    if( type == "hit" || type == "sunk" ) {
        mark_class = "hit";
        missed_coords.push( (position_y > 0 && position_x > 0) ? [axis_y[ position_y - 1 ], axis_x[ position_x - 1 ]] : [] );
        missed_coords.push( (position_y > 0 && position_x < 9) ? [axis_y[ position_y - 1 ], axis_x[ position_x + 1 ]] : [] );
        missed_coords.push( (position_y < 9 && position_x < 9) ? [axis_y[ position_y + 1 ], axis_x[ position_x + 1 ]] : [] );
        missed_coords.push( (position_y < 9 && position_x > 0) ? [axis_y[ position_y + 1 ], axis_x[ position_x - 1 ]] : [] );
    }

    if( type == "sunk" ) {
        missed_coords.push( (position_y > 0)                   ? [axis_y[ position_y - 1 ], coords[1]]                : [] );
        missed_coords.push( (position_y < 9)                   ? [axis_y[ position_y + 1 ], coords[1]]                : [] );
        missed_coords.push( (position_x < 9)                   ? [coords[0], axis_x[ position_x + 1 ]]                : [] );
        missed_coords.push( (position_x > 0)                   ? [coords[0], axis_x[ position_x - 1 ]]                : [] );
    }


    for( key in missed_coords ) {
        if( missed_coords[key].length == 0 || (tendency != null && tendency != key) ) {
            continue;
        }

        var temp_field = get_field_by_coords(missed_coords[key], board_number);

        if( temp_field.hasClass("hit") && type == "sunk" ) {
            mark_shot(board_number, missed_coords[key], type, key);
        }
        else {
            temp_field.addClass("miss");
        }
    }


    if( tendency == null ) {
        field.addClass(mark_class);
    }

    return type;
}

function is_sunk( coords, tendency ) {
    var coords        = $.type(coords) == "array" ? coords : [coords.substr(0, 1), parseInt(coords.substr(1))];
    var position_y    = $.inArray(coords[0], axis_y);
    var position_x    = $.inArray(coords[1], axis_x);
    var check_coords  = [];

    check_coords.push( (position_y > 0) ? [axis_y[ position_y - 1 ], coords[1]] : [] );
    check_coords.push( (position_y < 9) ? [axis_y[ position_y + 1 ], coords[1]] : [] );
    check_coords.push( (position_x < 9) ? [coords[0], axis_x[ position_x + 1 ]] : [] );
    check_coords.push( (position_x > 0) ? [coords[0], axis_x[ position_x - 1 ]] : [] );


    for( key in check_coords ) {
        if( check_coords[key].length == 0 || (tendency != null && tendency != key) ) {
            continue;
        }

        var temp_field = get_field_by_coords(check_coords[key], 0);

        if( temp_field.hasClass("hit") ) {
            if( is_sunk(check_coords[key], key) == false ) {
                return false;
            }
        }
        else if( temp_field.hasClass("ship") ) {
            return false;
        }
    }


    return true;
}

function get_updates() {
    if( update_execute !== true ) {
        return false;
    }

    updateXHR = $.ajax({
        data:      {'action': "get_updates"},
        cache:     false,
        success:   function(result) {
            custom_log( result );
            if( result === null || result.error !== false || result.success.length == 0 ) {
                return false;
            }


            for( key in result.success ) {
                switch( result.success[key].action ) {
                    case 'name_update':
                        $("div.board_menu:eq(1) span").text(result.success[key].value);
                        break;

                    case 'start_game':
                        shot_prevent = false;
                        $("div.board:eq(1) div div:not(:first-child)").css('border-right-color', "black");
                        $("div.board:eq(1) div:not(:first-child) div").css('border-bottom-color', "black");
                        break;

                    case 'join_game':
                        $("div.board_menu:eq(1) span").css({'font-weight': "bold"});
                        $("#game_link").text("");
                        break;

                    case 'shot':
                        if( mark_shot(0, result.success[key].value) == "miss" ) {
                            shot_prevent = false;
                        }
                        break;

                    case 'chat':
                        var chat = result.success[key].value;
                        chat_append(chat.text, $("div.board_menu:eq(1) span").text(), chat.time);
                        break;
                }
            }
        },
        complete:  function(jqXHR, textStatus) {
            if( update_execute === false ) {
                return false;
            }

            last_timeout = setTimeout("get_updates(true)", update_interval);

            return true;
        }
    });
}

function stop_update() {
    clearTimeout(last_timeout);
    update_execute = false;
    updateXHR.abort();
}

function get_battle() {
    var date = new Date();
    var timezone_offset = -date.getTimezoneOffset() / 60;

    $.ajax({
        data:      {'action': "get_battle", 'timezone_offset': timezone_offset},
        success:   function(result) {
            custom_log( result );
            if( result.error !== false ) {
                return false;
            }

            var battle = result.success.battle;
            var chats  = result.success.chats;

            if( battle.player_ships.length > 0 ) {
                game_start();

                if( (battle.other_shots !== null) || (battle.player_shots !== null) ) {
                    shot_prevent = false;
                }
            }

            for( key in battle.player_ships ) {
                var field = get_field_by_coords(battle.player_ships[key], 0);
                field.addClass("ship");
            }

            for( key in battle.other_shots ) {
                mark_shot(0, key, battle.other_shots[key]);
            }

            for( key in battle.player_shots ) {
                mark_shot(1, key, battle.player_shots[key]);
            }

            for( key in chats ) {
                chat_append(chats[key].text, chats[key].name, chats[key].time);
            }
        }
    });
}

function chat_append(text, name, time) {
    var $time = $("<span>").addClass("time").text("[" + time + "] ");
    var $name = $("<span>").addClass("name").text(name + ": ");
    var $text = $("<span>").text(text);
    var $chat_row = $("<p>").append($time).append($name).append($text);

    var $chats = $("#chatbox div.chats");

    var t = $chats.find(".time");
    var l = t.length;
    var i = l - 1;

    // finding a place to put a new row into (in case if new updated chat is older than an existing one)
    for( i; i >= 0; i-- ) {
        if( (t.eq(i).text().replace(/\[|\]/g, "") <= time) ) {
            break;
        }
    }

    if( (l == 0) || (i == l - 1) ) {
        $chats.append($chat_row);
    }
    else {
        $chats.children("p").eq(i+1).before($chat_row);
    }

    $chats.clearQueue().animate({
        scrollTop: $chats.children("p").height() * i
    }, 'slow');
}

function game_start() {
    game_started = true;
    $("#start").prop('disabled', true);
}

function ships_check($ships) {
    var ships_array = $ships.map( function() {
                          return $battleground.index(this);
                      }).toArray();

    var ships_length = 20;
    var ships_types  = {1:0, 2:0, 3:0, 4:0};
    var direction_multipliers = [1, 10];
    var idx;

    if( ships_array.length != ships_length ) {
        return false;
    }


    // check if no edge connection
    for( i in ships_array ) {
        idx = ((ships_array[i] < 10 ? "0" : "") + ships_array[i]).split("");

        if( idx[0] == 9 ) {
            continue;
        }

        var upper_right_corner = (idx[1] > 0) && ($.inArray(ships_array[i] + 9, ships_array) != -1);
        var lower_right_corner = (idx[1] < 9) && ($.inArray(ships_array[i] + 11, ships_array) != -1);

        if( upper_right_corner || lower_right_corner ) {
            return false;
        }
    }

    // check if there are the right types of ships
    for( i in ships_array ) {
        // we ignore masts which have already been marked as a part of a ship
        if( ships_array[i] === null ) {
            continue;
        }

        idx = ((ships_array[i] < 10 ? "0" : "") + ships_array[i]).split("");

        for( j in direction_multipliers ) {
            var border_index    = parseInt(j) == 1 ? 0 : 1;
            var border_distance = parseInt(idx[border_index]);

            var k = 1;
            // battleground border
            while( border_distance + k <= 9 ) {
                var index = ships_array[i] + (k * direction_multipliers[j]);
                var key   = $.inArray(index, ships_array);

                // no more masts
                if( key == -1 ) {
                    break;
                }

                ships_array[key] = null;

                // ship is too long
                if( ++k > 4 ) {
                    return false;
                }
            }

            // if not last direction check and only one (otherwise in both direction at least 1 mast would be found)
            if( (k == 1) && (j + 1 != direction_multipliers.length) ) {
                continue;
            }

            break; // either k > 1 (so ship found) or last loop
        }

        ships_types[k]++;
    }

    // strange way to check if ships_types == {1:4, 2:3, 3:2, 4:1}
    for( i in ships_types ) {
        if( parseInt(i) + ships_types[i] != 5 ) {
            return false;
        }
    }

    return true;
}

function parse_chatbox_keys() {
    var temp      = chatbox_keys.split(",");
    chatbox_keys = [];

    for( i in temp ) {
        var range = temp[i].split("-");

        if( range.length == 1 ) {
            chatbox_keys.push( parseInt(range[0]) );
        }
        else {
            for( j = range[0]; j <= range[1]; j++ ) {
                chatbox_keys.push( parseInt(j) );
            }
        }
    }
}

function custom_log( log ) {
    if (debug !== true) {
        return true;
    }

    var log_me = ($.type(log) != "object") ? log : ($.browser.mozilla ? log.toSource() : log.error + ' | ' + log.success + ' | ' + log.time);

    $("div.log").clearQueue().append( $("<p>").text(log_me) ).animate({
        scrollTop: $("div.log").prop("scrollHeight")
    }, 'slow');

    console.log(log);
}

function temp() {
    var a = [0, 19, 45, 55, 58, 59, 60, 61, 62, 65, 75, 88, 89, 94, 95, 96];
    for( i in a ) {
        $battleground.eq(a[i]).addClass("ship");
    }
}