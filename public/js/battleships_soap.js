
/**
 * Battleships class for SOAP API
 *
 * @author     Jerzy Lekowski <jerzy@lekowski.pl>
 * @version    0.6
 * @link       http://dev.lekowski.pl
 * @since      File available since Release 0.6
 *
 */

var BattleshipsClass = function() {
    // log events, updates managements
    var debug = true;
    // battle boards axis Y legend
    var axis_y = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J'];
    // battle boards axis X legend
    var axis_x = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10];
    // keys after pushing which we type in chatbox (A-Z, 0-9, :,._+)
    var chatbox_keys = "48-57, 59, 65-90, 96-105, 110, 188-191, 219-222";
    // whether the game has started
    var game_started = false;
    // whether the game has ended
    var game_ended = false;
    // whether player started the game
    var player_started = false;
    // whether opponent started the game
    var other_started = false;
    // prevents shooting (when game not started or other player's turn)
    var shot_prevent = true;
    // true - updates are requested (updating ON), false - you can start requesting updates (updating OFF);
    var update_execute = false;
    // interval between update calls
    var update_interval = 3000;
    // prevents focusing on chatbox (usually when pressing ctrl/alt + chatbox_key)
    var focus_prevent = false;
    // id of the last event retrieved from API
    var lastIdEvents = 0;
    // updates AJAX object
    var updateXHR = null;
    // battle boards
    var $battleground = null;
    // chatbox
    var $chatbox = null;
    // setTimeout() return value when waiting update_interval for a new update call
    var last_timeout = null;

    this.run = function () {
        // default settings for SOAP calls
        $.soap({
            url: 'SOAP/server.php',
            appendMethodToURL: false,
            request: function(soapRequest) {
                custom_log(soapRequest.toString());
            },
            error: function(soapResponse) {
                custom_log(soapResponse.toString());
            }
        });

        $battleground = $("div:gt(0) div:not(:first-child)", "div.board");
        $battleground.board = function(index) {
            return index === 0 ? $battleground.slice(0, 100) : $battleground.slice(100);
        };

        $chatbox = $(":text", "#chatbox");

        // parse key range to array
        parse_chatbox_keys();
        // when start typing and not focused on text field, focus to type on chatbox
        $(document).on({keydown: documentKeydownCallback, keyup: documentKeyupCallback});

        // board handling
        $battleground.on('click', battlegroundClickCallback);

        // starting the game
        $("#start").on('click', startClickCallback);

        // updating player's name
        $("#name_update").on('click', nameUpdateClickCallback)
            .siblings(":text").on({keyup: nameUpdateTextKeyupCallback, blur: nameUpdateTextBlurCallback});

        // updates management
        $("#update").on('click', updateClickCallback);

        // starts new game
        $("#new_game").on('click', newGameClickCallback);

        // send chat text
        $chatbox.on('keyup', chatboxKeyupCallback);

        // shoot randomly
        $("#random_shot").on('click', random_shot);

        // set ships randomly
        $("#random_ships").on('click', {retry: 2}, random_ships);

        if (debug === true) {
            $("#update, div.log").show();
        }

        // first call to load ships, battle and chats
        get_battle();
    };

    function documentKeydownCallback(event) {
        // if ctr or alt pressed
        if ((event.which == 17) || (event.which == 18)) {
            focus_prevent = true;
            return true;
        }

        if (focus_prevent || $(event.target).is(":text") || ($.inArray(event.which, chatbox_keys) == -1)) {
            return true;
        }

        $chatbox.focus();
    }

    function documentKeyupCallback() {
        focus_prevent = false;
    }

    function battlegroundClickCallback() {
        // marking opponents ships
        if ($battleground.index(this) >= 100) {
            shot(this);
        } else if (!player_started) {
            // if game not started set the ship
            $(this).toggleClass("ship");
        }
    }

    function startClickCallback() {
        if (player_started) {
            alert("You have already started the game");
            return false;
        }

        var $ships = $battleground.board(0).filter(".ship");

        if (check_ships($ships) == false) {
            alert("There is either not enough ships or they're set incorrectly");
            return false;
        }

        var shipsString = $ships.map(function() {
            return get_coords(this);
        }).toArray().join(",");

        $.soap({
            method: 'startGame',
            params: {
                hash: $("#hash").val(),
                ships: shipsString
            },
            success: function(soapResponse) {
                custom_log(soapResponse.toString());

                start_game($("#playerNumber").val() == 1);
            }
        });
    }

    function nameUpdateClickCallback() {
        $(this).hide().siblings(":text").show().select();
    }

    function nameUpdateTextKeyupCallback(event) {
        // if pressed ESC - leave the input, if ENTER - process, if other - do nothing
        if (event.which != 13) {
            if (event.which == 27) {
                $(this).blur();
                $chatbox.focus();
            }

            return true;
        }

        var $input      = $(this);
        var player_name = $input.val();

        $.soap({
            method: 'updateName',
            params: {
                hash: $("#hash").val(),
                playerName: $('<span>').text(player_name).html()
            },
            success: function(soapResponse) {
                custom_log(soapResponse.toString());

                $input.hide().siblings("span").show();
                $(".player_name").text(player_name);
            }
        });
    }

    function nameUpdateTextBlurCallback() {
        if ($(this).has(":visible")) {
            var player_name = $(this).siblings("span").text();

            $(this).hide().val(player_name).siblings("span").show();
        }
    }

    function updateClickCallback() {
        update_execute = !update_execute;

        if (update_execute) {
            $(this).text("Updates [ON]");
            get_updates();
        } else {
            $(this).text("Updates [OFF]");
            stop_update();
        }
    }

    function newGameClickCallback() {
        if (confirm("Are you sure you want to quit the current game?")) {
            window.location = window.location.protocol + "//" + window.location.hostname + window.location.pathname;
        }
    }

    function chatboxKeyupCallback(event) {
        if (event.which != 13) {
            return true;
        }

        var text = $.trim($chatbox.val());

        if (text == "") {
            return true;
        }

        $chatbox.prop('disabled', true);
        $.soap({
            method: 'addChat',
            params: {
                hash: $("#hash").val(),
                text: $('<span>').text(text).html()
            },
            success: function(soapResponse) {
                var result = soap_to_object(soapResponse, "result");
                custom_log(result);
                $chatbox.prop('disabled', false);

                chat_append(text, true, result);
                $chatbox.val("");
            }
        });
    }


    function shot(element) {
        if (!game_started) {
            alert("You can't shoot at the moment - game is not started");
            return false;
        }

        if (shot_prevent) {
            alert("You can't shoot at the moment - other player has not started or it's not your turn!");
            return false;
        }

        if ($(element).is(".miss, .hit")) {
            custom_log("You either already shot this field, or no ship could be there");
            return;
        }

        var coords = get_coords(element);

        set_turn();

        $.soap({
            method: 'addShot',
            params: {
                hash: $("#hash").val(),
                coords: coords
            },
            success: function(soapResponse) {
                var result = soap_to_object(soapResponse, "result");
                custom_log(result);

                set_turn(result != "miss");
                mark_shot(1, coords, result);

                if (result == "sunk") {
                    check_game_end();
                }
            },
            error: function(soapResponse) {
                custom_log(soapResponse.toString());
                set_turn(true);
            }
        });
    }

    function get_coords(element) {
        var index = $battleground.index(element);

        // to convert: 1 -> [0,1], 12 -> [1,2], 167 -> [6,7]
        var temp    = (index >= 100) ? (index - 100) : index;
            temp    = (temp < 10 ? "0" : "") + temp;
        var indexes = temp.split("");

        var cord_y = axis_y[ indexes[1] ];
        var cord_x = axis_x[ indexes[0] ];

        return cord_y + cord_x;
    }

    function get_field_by_coords(coords, board_number) {
        if ($.type(coords) != "array") {
            coords = [coords.substr(0, 1), parseInt(coords.substr(1))];
        }
        var position_y = $.inArray(coords[0], axis_y);
        var position_x = $.inArray(coords[1], axis_x);

        // parseInt("08") -> 0
        var position = parseInt([position_x, position_y].join(''), 10) + (board_number * 100);

        return $battleground.eq(position);
    }

    function mark_shot(board_number, coords, type, tendency) {
        if ($.type(coords) != "array") {
            coords = [coords.substr(0, 1), parseInt(coords.substr(1))];
        }
        var field         = get_field_by_coords(coords, board_number);
        var position_y    = $.inArray(coords[0], axis_y);
        var position_x    = $.inArray(coords[1], axis_x);
        var mark_class    = '';
        var missed_coords = [];

        if (board_number == 0 && type == null) {
            if (field.hasClass("ship")) {
                type = is_sunk(coords) ? "sunk" : "hit";
            } else {
                type = "miss";
            }
        }


        if (type == "miss") {
            mark_class = "miss";
            missed_coords.push(coords);
        }

        if (type == "hit" || type == "sunk") {
            mark_class = "hit";
            missed_coords.push( (position_y > 0 && position_x > 0) ? [axis_y[ position_y - 1 ], axis_x[ position_x - 1 ]] : [] );
            missed_coords.push( (position_y > 0 && position_x < 9) ? [axis_y[ position_y - 1 ], axis_x[ position_x + 1 ]] : [] );
            missed_coords.push( (position_y < 9 && position_x < 9) ? [axis_y[ position_y + 1 ], axis_x[ position_x + 1 ]] : [] );
            missed_coords.push( (position_y < 9 && position_x > 0) ? [axis_y[ position_y + 1 ], axis_x[ position_x - 1 ]] : [] );
        }

        if (type == "sunk") {
            missed_coords.push( (position_y > 0)                   ? [axis_y[ position_y - 1 ], coords[1]]                : [] );
            missed_coords.push( (position_y < 9)                   ? [axis_y[ position_y + 1 ], coords[1]]                : [] );
            missed_coords.push( (position_x < 9)                   ? [coords[0], axis_x[ position_x + 1 ]]                : [] );
            missed_coords.push( (position_x > 0)                   ? [coords[0], axis_x[ position_x - 1 ]]                : [] );
        }


        for (var i = 0; i < missed_coords.length; i++) {
            if (missed_coords[i].length == 0 || (tendency != null && tendency != i)) {
                continue;
            }

            var temp_field = get_field_by_coords(missed_coords[i], board_number);

            if (temp_field.hasClass("hit") && type == "sunk") {
                mark_shot(board_number, missed_coords[i], type, i);
            } else {
                temp_field.addClass("miss");
            }
        }


        if (tendency == null) {
            field.addClass(mark_class);
        }

        return type;
    }

    function is_sunk(coords, tendency) {
        if ($.type(coords) != "array") {
            coords = [coords.substr(0, 1), parseInt(coords.substr(1))];
        }
        var position_y    = $.inArray(coords[0], axis_y);
        var position_x    = $.inArray(coords[1], axis_x);
        var check_coords  = [];

        check_coords.push( (position_y > 0) ? [axis_y[ position_y - 1 ], coords[1]] : [] );
        check_coords.push( (position_y < 9) ? [axis_y[ position_y + 1 ], coords[1]] : [] );
        check_coords.push( (position_x < 9) ? [coords[0], axis_x[ position_x + 1 ]] : [] );
        check_coords.push( (position_x > 0) ? [coords[0], axis_x[ position_x - 1 ]] : [] );


        for (var i = 0; i < check_coords.length; i++) {
            if (check_coords[i].length == 0 || (tendency != null && tendency != i)) {
                continue;
            }

            var temp_field = get_field_by_coords(check_coords[i], 0);

            if (temp_field.hasClass("hit")) {
                if (is_sunk(check_coords[i], i) == false) {
                    return false;
                }
            } else if (temp_field.hasClass("ship")) {
                return false;
            }
        }


        return true;
    }

    function get_updates() {
        if (update_execute !== true) {
            return false;
        }

        updateXHR = $.soap({
            method: 'getUpdates',
            params: {
                hash: $("#hash").val(),
                lastIdEvents: lastIdEvents
            },
            success: function(soapResponse) {
                var result = soap_to_object(soapResponse, "updates");
                custom_log(result);

                if (update_execute !== false) {
                    last_timeout = setTimeout(get_updates, update_interval);
                }

                if (result === null || result === false || result.length == 0) {
                    return false;
                }

                for (var key in result) {
                    var updates = result[key];
                    for (var i = 0; i < updates.length; i++) {
                        var update = updates[i];

                        switch (key) {
                            case 'name_update':
                                $(".other_name").text(update);
                                break;

                            case 'start_game':
                                set_turn($("#playerNumber").val() == 1);
                                other_started = true;
                                game_started = player_started && other_started;
                                break;

                            case 'join_game':
                                $(".board_menu:eq(1) span").css({fontWeight: "bold"});
                                $("#game_link").text("");
                                break;

                            case 'shot':
                                var shotResult = mark_shot(0, update);
                                if (shotResult == "miss") {
                                    set_turn(true);
                                } else if (shotResult == "sunk") {
                                    check_game_end();
                                }
                                break;

                            case 'chat':
                                chat_append(update.text, false, update.timestamp);
                                break;

                            case 'lastIdEvents':
                                lastIdEvents = update;
                                break;
                        }
                    }
                }
            }
        });
    }

    function stop_update() {
        clearTimeout(last_timeout);
        update_execute = false;
        updateXHR.abort();
    }

    function get_battle() {
        $.soap({
            method: 'getGame',
            params: {
                hash: $("#hash").val()
            },
            success: function(soapResponse) {
                var key;
                var gameData = soap_to_object(soapResponse, "gameData");
                custom_log(gameData);

                var battle = gameData.battle;
                var chats  = gameData.chats;
                lastIdEvents = gameData.lastIdEvents;

                other_started = gameData.otherStarted;
                if (gameData.playerStarted) {
                    start_game(gameData.whoseTurn == gameData.playerNumber);
                }

                for (key in gameData.playerShips) {
                    var field = get_field_by_coords(gameData.playerShips[key], 0);
                    field.addClass("ship");
                }

                for (key in battle.playerGround) {
                    mark_shot(0, key, battle.playerGround[key]);
                }

                for (key in battle.otherGround) {
                    mark_shot(1, key, battle.otherGround[key]);
                }

                for (key in chats) {
                    chat_append(chats[key].text, chats[key].player == gameData.playerNumber, chats[key].timestamp);
                }

                check_game_end();
                // start AJAX calls for updates
                $("#update").triggerHandler('click');
            }
        });
    }

    // TODO: would be better if node_to_object() handled this (double children().length check)
    function soap_to_object(soap, childName) {
        var response;
        var $resultNode = $(soap.toXML()).find(childName);

        if ($resultNode.children().length > 0) {
            response = {};
            $resultNode.children().each(function() {
                var key;
                var value;
                if (childName == "updates") {
                    key = node_to_object($(this).find('key').get(0));
                    if (key == "chat") {
                        value = [];
                        $(this).find('value').children().each(function() {
                            value.push(node_to_object(this));
                        });
                    } else {
                        value = node_to_object($(this).find('value').get(0));
                    }
                } else {
                    key = $(this).prop('tagName');
                    value = node_to_object(this);
                }
                response[key] = value;
            });
        } else {
            response = node_to_object($resultNode.get(0));
        }

        return response;
    }

    function node_to_object(node) {
        if ($(node).children().length === 0) {
            return get_node_value(node);
        }

        var object = is_xsi_type_node(node, "array") ? [] : {};
        if ($(node).children('key, value').length == 2) {
            var key   = node_to_object($(node).find('key').get(0));
            var value = node_to_object($(node).find('value').get(0));
            object[key] = value;
        } else {
            $(node).children().each(function() {
                if ($.isArray(object)) {
                    object.push(node_to_object(this));
                } else {
                    $.extend(object, node_to_object(this));
                }
            });
        }

        return object;
    }

    function get_node_value(node) {
        var nodeValue;
        var nodeText = $(node).text();
        var xsiAttributes = get_node_xsi_attributes(node);

        if ($.inArray(nodeText, ["true", "false", "null"]) != -1
            || (xsiAttributes.type && xsiAttributes.type[1] == "boolean")
        ) {
            nodeValue = eval(nodeText);
        } else if (xsiAttributes.nil) {
            nodeValue = null;
        } else {
            nodeValue = nodeText;
        }

        return nodeValue;
    }

    function get_node_xsi_attributes(node) {
        var attributes = {};
        $.each(node.attributes, function() {
            if (!this.specified || this.prefix != "xsi") {
                return;
            }

            attributes[this.localName] = this.value.split(":");
        });

        return attributes;
    }

    function is_xsi_type_node(node, type) {
        var xsiAttributes = get_node_xsi_attributes(node);

        return xsiAttributes.type && xsiAttributes.type[1].toLowerCase() == type.toLowerCase();
    }

    function chat_append(text, isMe, timestamp) {
        var name = $(".board_menu:eq(" + Number(!isMe) + ") span").text();
        var date = new Date(timestamp * 1000);
        var formattedDate = date.getFullYear()
            + "-" + (date.getMonth() < 10 ? "0" : "") + date.getMonth()
            + "-" + (date.getDate() < 10 ? "0" : "") + date.getDate()
            + " " + (date.getHours() < 10 ? "0" : "") + date.getHours()
            + ":" + (date.getMinutes() < 10 ? "0" : "") + date.getMinutes()
            + ":" + (date.getSeconds() < 10 ? "0" : "") + date.getSeconds();
        var $time = $("<span>").addClass("time").text("[" + formattedDate + "] ");
        var $chatterName = $("<span>").addClass((isMe ? "player_name" : "other_name")).text(name);
        var $name = $("<span>").addClass("name").append($chatterName).append(": ");
        var $text = $("<span>").text(text);
        var $chat_row = $("<p>").append($time).append($name).append($text);

        var $chats = $("#chatbox div.chats");

        var t = $chats.find(".time");
        var l = t.length;
        var i = l - 1;

        // finding a place to put a new row into (in case if new updated chat is older than an existing one)
        for (i; i >= 0; i--) {
            if ((t.eq(i).text().replace(/\[|\]/g, "") <= formattedDate)) {
                break;
            }
        }

        if ((l == 0) || (i == l - 1)) {
            $chats.append($chat_row);
        } else {
            $chats.children("p").eq(i + 1).before($chat_row);
        }

        $chats.clearQueue().animate({
            scrollTop: $chats.children("p").height() * i
        }, 'slow');
    }

    function start_game(isMyTurn) {
        player_started = true;
        game_started = player_started && other_started;
        $("#start").prop('disabled', true);
        $("#random_shot, #random_ships").toggle();
        set_turn(isMyTurn);
    }

    function check_game_end() {
        if (game_ended) {
            return game_ended;
        }

        if ($battleground.board(0).filter(".hit").length >= 20) {
            alert("You lost");
            game_ended = true;
        } else if ($battleground.board(1).filter(".hit").length >= 20) {
            alert("You won");
            game_ended = true;
        }

        return game_ended;
    }

    function set_turn(isMyTurn) {
        if ($.type(isMyTurn) == "undefined") {
            $(".board_menu span").removeClass("turn");
        } else {
            $(".board_menu:eq(" + Number(!isMyTurn) + ") span").addClass("turn");
            $(".board_menu:eq(" + Number(isMyTurn) + ") span").removeClass("turn");
        }
        shot_prevent = !isMyTurn;
        $("#random_shot").prop('disabled', !isMyTurn);
    }

    function check_ships($ships) {
        var ships_array = $ships.map(function() {
            return $battleground.index(this);
        }).toArray();

        var ships_length = 20;
        var ships_types  = {1:0, 2:0, 3:0, 4:0};
        var direction_multipliers = [1, 10];
        var idx;
        var i, j, k;

        if (ships_array.length != ships_length) {
            return false;
        }

        // check if no edge connection
        for (i = 0; i < ships_array.length; i++) {
            idx = ((ships_array[i] < 10 ? "0" : "") + ships_array[i]).split("");

            if (idx[0] == 9) {
                continue;
            }

            var upper_right_corner = (idx[1] > 0) && ($.inArray(ships_array[i] + 9, ships_array) != -1);
            var lower_right_corner = (idx[1] < 9) && ($.inArray(ships_array[i] + 11, ships_array) != -1);

            if (upper_right_corner || lower_right_corner) {
                return false;
            }
        }

        // check if there are the right types of ships
        for (i = 0; i < ships_array.length; i++) {
            // we ignore masts which have already been marked as a part of a ship
            if (ships_array[i] === null) {
                continue;
            }

            idx = ((ships_array[i] < 10 ? "0" : "") + ships_array[i]).split("");

            for (j = 0; j < direction_multipliers.length; j++) {
                var border_index = parseInt(j) == 1 ? 0 : 1;
                var border_distance = parseInt(idx[border_index]);

                k = 1;
                // battleground border
                while (border_distance + k <= 9) {
                    var index = ships_array[i] + (k * direction_multipliers[j]);
                    var key = $.inArray(index, ships_array);

                    // no more masts
                    if (key == -1) {
                        break;
                    }

                    ships_array[key] = null;

                    // ship is too long
                    if (++k > 4) {
                        return false;
                    }
                }

                // if not last direction check and only one (otherwise in both direction at least 1 mast would be found)
                if ((k == 1) && (j + 1 != direction_multipliers.length)) {
                    continue;
                }

                break; // either k > 1 (so ship found) or last loop
            }

            ships_types[k]++;
        }

        // strange way to check if ships_types == {1:4, 2:3, 3:2, 4:1}
        for (i = 0; i < ships_types.length; i++) {
            if (parseInt(i) + ships_types[i] != 5) {
                return false;
            }
        }

        return true;
    }

    function parse_chatbox_keys() {
        var temp = chatbox_keys.split(",");
        chatbox_keys = [];

        for (var i = 0; i < temp.length; i++) {
            var range = temp[i].split("-");

            if (range.length == 1) {
                chatbox_keys.push(parseInt(range[0]));
            } else {
                for (var j = range[0]; j <= range[1]; j++) {
                    chatbox_keys.push(parseInt(j));
                }
            }
        }
    }

    function random_shot() {
        var $empty_fields = $battleground.board(1).not(".miss, .hit");
        // random from 0 to the amount of empty fields - 1 (because first's index is 0)
        var index = Math.floor(Math.random() * $empty_fields.length);
        $empty_fields.eq(index).trigger('click');
    }

    function random_ships(event) {
        var orientations = [0, 1]; // 0 - vertical, 1 - horizontal
        var direction_multipliers = [1, 10];
        var ships_types = {1:4, 2:3, 3:2, 4:1};

        $battleground.board(0).filter(".ship").click();
        for (var number_of_ships in ships_types) {
            var masts = ships_types[number_of_ships];

            for (var j = 0; j < number_of_ships; j++) {
                var orientation = orientations[ Math.floor(Math.random() * orientations.length) ];
                mark_restricted_starts(masts, orientation);
                var $startFields = $battleground.board(0).not(".restricted");

                var index = Math.floor(Math.random() * $startFields.length);
                var idx = $battleground.index( $startFields.eq(index) );
                for (var k = 0; k < masts; k++) {
                    $battleground.eq(idx + k * direction_multipliers[orientation]).click();
                }
            }
        }

        if (check_ships($battleground.board(0).filter(".ship")) == false) {
            if (event.data && event.data.retry > 0) {
                $battleground.board(0).removeClass("ship");
                event.data.retry--;
                return random_ships(event);
            }

            return false;
        }

        $battleground.board(0).removeClass("restricted");

        return true;
    }

    function mark_restricted_starts(masts, orientation) {
        var direction_multipliers = [1, 10];

        var marks = $battleground.board(0).filter(".ship").map(function() {
            var index = $battleground.index(this);
            var idx = ((index < 10 ? "0" : "") + index).split("");
            var border_distance = parseInt(idx[Number(!orientation)]);

            var mark = [index];

            if (idx[0] < 9) {
                mark.push(index + 10);
                if (idx[1] < 9) {
                    mark.push(index + 11);
                }
                if (idx[1] > 0) {
                    mark.push(index + 9);
                }
            }

            if (idx[0] > 0) {
                mark.push(index - 10);
                if (idx[1] < 9) {
                    mark.push(index - 9);
                }
                if (idx[1] > 0) {
                    mark.push(index - 11);
                }
            }

            if (idx[1] < 9) {
                mark.push(index + 1);
            }

            if (idx[1] > 0) {
                mark.push(index - 1);
            }

            for (var k = 2; (border_distance - k >= 0) && (k <= masts); k++) {
                var safe_index = index - (k * direction_multipliers[orientation]);
                var safe_idx = ((safe_index < 10 ? "0" : "") + safe_index).split("");
                mark.push(safe_index);

                if (safe_idx[orientation] > 0) {
                    mark.push(safe_index - direction_multipliers[Number(!orientation)]);
                }
                if (safe_idx[orientation] < 9) {
                    mark.push(safe_index + direction_multipliers[Number(!orientation)]);
                }
            }

            return mark;
        }).toArray();

        $battleground.board(0).removeClass("restricted");

        for (var i = 0; i < marks.length; i++) {
            $battleground.board(0).eq(marks[i]).addClass("restricted");
        }

        if (orientation == 0) {
            $battleground.board(0).filter('div:nth-child(n+' + (13 - masts) + ')').addClass("restricted");
        } else {
            $battleground.board(0).slice((11 - masts) * 10).addClass("restricted");
        }
    }

    function custom_log(log) {
        if (debug !== true) {
            return true;
        }

        var log_me = ($.type(log) !== "object") ? log : JSON.stringify(log);

        $("div.log").clearQueue().append($("<p>").text(log_me)).animate({
            scrollTop: $("div.log").prop('scrollHeight')
        }, 'slow');

        console.log(log);
    }
};
