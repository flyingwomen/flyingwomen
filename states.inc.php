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
 * states.inc.php
 *
 * BI game states description
 *
 */

/*
   Game state machine is a tool used to facilitate game developpement by doing common stuff that can be set up
   in a very easy way from this configuration file.

   Please check the BGA Studio presentation about game state to understand this, and associated documentation.

   Summary:

   States types:
   _ activeplayer: in this type of state, we expect some action from the active player.
   _ multipleactiveplayer: in this type of state, we expect some action from multiple players (the active players)
   _ game: this is an intermediary state where we don't expect any actions from players. Your game logic must decide what is the next game state.
   _ manager: special type for initial and final state

   Arguments of game states:
   _ name: the name of the GameState, in order you can recognize it on your own code.
   _ description: the description of the current game state is always displayed in the action status bar on
                  the top of the game. Most of the time this is useless for game state with "game" type.
   _ descriptionmyturn: the description of the current game state when it's your turn.
   _ type: defines the type of game states (activeplayer / multipleactiveplayer / game / manager)
   _ action: name of the method to call when this game state become the current game state. Usually, the
             action method is prefixed by "st" (ex: "stMyGameStateName").
   _ possibleactions: array that specify possible player actions on this step. It allows you to use "checkAction"
                      method on both client side (Javacript: this.checkAction) and server side (PHP: self::checkAction).
   _ transitions: the transitions are the possible paths to go from a game state to another. You must name
                  transitions in order to use transition names in "nextState" PHP method, and use IDs to
                  specify the next game state for each transition.
   _ args: name of the method to call to retrieve arguments for this gamestate. Arguments are sent to the
           client side to be used on "onEnteringState" or to set arguments in the gamestate description.
   _ updateGameProgression: when specified, the game progression is updated (=> call to your getGameProgression
                            method).
*/

//    !! It is not a good idea to modify this file when a game is running !!

 
$machinestates = array(

    // The initial state. Please do not modify.
    1 => array(
        "name" => "gameSetup",
        "description" => "",
        "type" => "manager",
        "action" => "stGameSetup",
        "transitions" => array( "" => 2 )
    ),
    
    // Note: ID=2 => your first state
    2 => array(
        "name" => "admitGuest",
        "description" => '',
        "type" => "game",
        "action" => "stAdmitGuest",
        "updateGameProgression" => true,   
        "transitions" => array( "assignGuestRoom" => 3, "playerTurn" => 4 )
    ), 
    3 => array(
    	"name" => "assignGuestRoom",
    	"description" => clienttranslate('${actplayer} must place guest in room'),
    	"descriptionmyturn" => clienttranslate('${you} must place guest in room'),
    	"type" => "activeplayer",
    	"possibleactions" => array( "admitGuest" ),
    	"transitions" => array( "admitGuest" => 2 )
    ),

    4 => array(
    		"name" => "playerTurn",
    		"description" => clienttranslate('${actplayer} must choose victim, then select action '),
    		"descriptionmyturn" => clienttranslate('${you} must choose victim, then select action '),
    		"type" => "activeplayer",
    		"possibleactions" => array( "bribed", "killed", "built","burried", "pass"  ),
    		"transitions" => array( "selectAccomplice" => 14, "nextPlayer" => 5 , "selectRoom" => 8, "freeBribed" => 9,"freeBurried" => 10,"freeKilled" => 11, "freeBistro" => 19 )
    ),
    14 => array(
    		"name" => "selectAccomplice",
    		"description" => clienttranslate('${actplayer} must select accomplices '),
    		"descriptionmyturn" => clienttranslate('${you} must select accomplices '),
    		"type" => "activeplayer",
    		"possibleactions" => array( "startOver", "SO", "accomplice" ,"bribed", "killed", "built","burried", "pass"  ),
    		"transitions" => array("playerTurn" => 4, "nextPlayer" => 5 , "selectRoom" => 8, "freeBribed" => 9,"freeBurried" => 10,"freeKilled" => 11, "freeBistro" => 19 )
    ),
    5 => array(
        "name" => "nextPlayer",
        "description" => '',
        "type" => "game",
        "action" => "stNextPlayer",
        "updateGameProgression" => true,   
        "transitions" => array( "playerTurn" => 4,"endRound" => 12 , "gameEnd" => 99 )
    ),  
	8 => array(
        "name" => "selectRoom",
    	"description" => clienttranslate('${actplayer}must select room'),
    	"descriptionmyturn" => clienttranslate('${you} must select room'),
        "type" => "activeplayer",
		"possibleactions" => array( "confirmRoom" ),
        "transitions" => array( "nextPlayer" => 5 )
    ), 
	9 => array(
        "name" => "freeBribed",
    	"description" => clienttranslate('${actplayer} choose action'),
    	"descriptionmyturn" => clienttranslate('${you} choose action'),
        "type" => "activeplayer",
		"possibleactions" => array(  "SO", "accomplice" , "startOver", "bribed", "done"  ),
        "transitions" => array("playerTurn" => 4, "selectAccomplice" => 14,   "nextPlayer" => 5 )
    ), 
	19 => array(
        "name" => "freeBistro",
    	"description" => clienttranslate('${actplayer} choose action'),
    	"descriptionmyturn" => clienttranslate('${you} choose action'),
        "type" => "activeplayer",
		"possibleactions" => array( "startOver", "bribed", "done"  ),
        "transitions" => array(  "nextPlayer" => 5 )
    ), 
	10 => array(
        "name" => "freeBurried",
    	"description" => clienttranslate('${actplayer} choose action'),
    	"descriptionmyturn" => clienttranslate('${you} choose action'),
        "type" => "activeplayer",
		"possibleactions" => array(  "SO", "accomplice" ,"startOver", "burried", "done"  ),
        "transitions" => array( "playerTurn" => 4, "selectAccomplice" => 14,   "nextPlayer" => 5 )
    ), 
	11 => array(
        "name" => "freeKilled",
    	"description" => clienttranslate('${actplayer} choose action'),
    	"descriptionmyturn" => clienttranslate('${you} choose action'),
        "type" => "activeplayer",
		"possibleactions" => array(  "SO", "accomplice" ,"startOver",  "killed", "done" ),
        "transitions" => array("playerTurn" => 4, "selectAccomplice"  => 14,  "nextPlayer" => 5 )
    ), 
	
	12 => array(
        "name" => "endRound",
        "description" => '',
        "type" => "game",
        "action" => "stEndRound",
        "updateGameProgression" => true,   
        "transitions" => array( "admitGuest" => 2, "endRound" => 12 ,  "payAccomplice" => 13, "gameEnd" => 99 )
    ), 
	13 => array(
    		"name" => "payAccomplice",
    		"description" => clienttranslate('${actplayer} must select accomplice to remove'),
    		"descriptionmyturn" => clienttranslate('${you}  must select accomplice to remove then click confirm'),
    		"type" => "activeplayer",
    		"possibleactions" => array( "remove"  ),
    		"transitions" => array( "endRound" => 12  )
    ),

    // Please do not modify (and do not overload action/args methods).
    99 => array(
        "name" => "gameEnd",
        "description" => clienttranslate("End of game"),
        "type" => "manager",
        "action" => "stGameEnd",
        "args" => "argGameEnd"
    )

);



