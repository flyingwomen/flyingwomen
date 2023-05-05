<?php

/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * BI implementation : © <Your name here> <Your email address here>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * bi.view.php
 *
 * This is your "view" file.
 *
 * The method "build_page" below is called each time the game interface is displayed to a player, ie:
 * _ when the game starts
 * _ when a player refreshes the game page (F5)
 *
 * "build_page" method allows you to dynamically modify the HTML generated for the game interface. In
 * particular, you can set here the values of variables elements defined in bi_bi.tpl (elements
 * like {MY_VARIABLE_ELEMENT}), and insert HTML block elements (also defined in your HTML template file)
 *
 * Note: if the HTML of your game interface is always the same, you don't have to place anything here.
 *
 */

require_once(APP_BASE_PATH . "view/common/game.view.php");

class view_bi_bi extends game_view
{
    function getGameName()
    {
        return "bi";
    }
    function build_page($viewArgs)
    {
        // Get players & players number
        $players = $this->game->loadPlayersBasicInfos();
        $players_nbr = count($players);

        /*********** Place your code below:  ************/
        $template = self::getGameName() . "_" . self::getGameName();
        global $g_user;
        $current_player_id = $g_user->get_id();
        $is_spectator = (!array_key_exists($current_player_id, $players));

        // create squares for main board showing player francs
        $this->page->begin_block("bi_bi", "square");

        $hor_scale = 10;
        $ver_scale = 10;
        for ($x = 1; $x <= 54; $x++) {
            for ($y = 1; $y <= 20; $y++) {
                $this->page->insert_block("square", array(
                    'X' => $x,
                    'Y' => $y,
                    'LEFT' => round(($x - 1) * $hor_scale),
                    'TOP' => round(($y - 1) * $ver_scale)
                ));
            }
        }

        // this will inflate our player block with actual players data

        $this->page->begin_block($template, "cur_player");
        if (!$is_spectator) {
            $this->page->insert_block("cur_player", array(
                "PLAYER_ID" => $current_player_id,
                "PLAYER_NAME" => $players[$current_player_id]['player_name'],
                "PLAYER_COLOR" => $players[$current_player_id]['player_color']
            ));
        }
        foreach ($players as $player_id => $info) {
            if ($players[$player_id]['player_id']  != $current_player_id)
                continue;
        }

        $this->page->begin_block($template, "player");

        foreach ($players as $player_id => $info) {
            if ($players[$player_id]['player_id']  == $current_player_id)
                continue;
            $this->page->insert_block("player", array(
                "PLAYER_ID" => $player_id,
                "PLAYER_NAME" => $players[$player_id]['player_name'],
                "PLAYER_COLOR" => $players[$player_id]['player_color']
            ));
        }
        // this will make our My Hand text translatable
        $this->tpl['MY_HAND'] = self::_("My hand");
        /*
        
        // Examples: set the value of some element defined in your tpl file like this: {MY_VARIABLE_ELEMENT}

        // Display a specific number / string
        $this->tpl['MY_VARIABLE_ELEMENT'] = $number_to_display;

        // Display a string to be translated in all languages: 
        $this->tpl['MY_VARIABLE_ELEMENT'] = self::_("A string to be translated");

        // Display some HTML content of your own:
        $this->tpl['MY_VARIABLE_ELEMENT'] = self::raw( $some_html_code );
        
        */

        /*
        
        // Example: display a specific HTML block for each player in this game.
        // (note: the block is defined in your .tpl file like this:
        //      <!-- BEGIN myblock --> 
        //          ... my HTML code ...
        //      <!-- END myblock --> 
        

        $this->page->begin_block( "bi_bi", "myblock" );
        foreach( $players as $player )
        {
            $this->page->insert_block( "myblock", array( 
                                                    "PLAYER_NAME" => $player['player_name'],
                                                    "SOME_VARIABLE" => $some_value
                                                    ...
                                                     ) );
        }
        
        */



        /*********** Do not change anything below this line  ************/
    }
}
