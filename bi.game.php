<?php

/**
 *------
 * BGA  framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * BI implementation : © <Your name here> <Your email address here>
 * 
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 * 
 * bi.game.php
 *
 * This is the main file for your game logic.
 *
 * In this PHP file, you are going to defines the rules of the game.
 *
 */


require_once(APP_GAMEMODULE_PATH . 'module/table/table.game.php');
require('modules/bidatabase.php');
require('modules/notifies.php');
require('modules/traveler.php');
require('modules/myutilities.php');

class BI extends Table
{
	use BIDatabase;
	use Notifies;
	use Travelers;
	use MyUtilities;

	function __construct()
	{

		parent::__construct();
		$this->guest_decks = self::getNew("module.common.deck");
		$this->guest_decks->init("guest_deck");

		self::initGameStateLabels(array(
			"game length" => 100,
			"traveller_day_count" => 13,
			"action_round" => 10,
			"peasant_selected" => 21,
			"starting_player" => 12,
			"deck_emptied" => 15,
			"start_player_paid" => 16, // start player 
			"annex_room_select" => 17, //marks room selection happening now
			"last_guest_type_id" => 18, //holds that monk or concierge chosen built actions so can go between state
			"last_round" => 19,
			"cards_in_game" => 20,
			"grave_diggers_sent" => 21,
			"current_guest" => 22,
			"burial_location" => 23,	// for getAccomplices	
			"player_action" => 24
		));
	}

	protected function getGameName()
	{
		// Used for translations and stuff. Please do not modify.
		return "bi";
	}

	protected function setupNewGame($players, $options = array())
	{

		$gameinfos = self::getGameinfos();
		$default_colors = $gameinfos['player_colors'];

		// Create players
		$sql = "INSERT INTO player (player_id, player_color, player_canal, player_name, player_avatar) VALUES ";
		$values = array();
		foreach ($players as $player_id => $player) {
			$color = array_shift($default_colors);
			$values[] = "('" . $player_id . "','$color','" . $player['player_canal'] . "','" . addslashes($player['player_name']) . "','" . addslashes($player['player_avatar']) . "')";
		}
		$sql .= implode($values, ',');
		self::DbQuery($sql);
		self::reattributeColorsBasedOnPreferences($players, $gameinfos['player_colors']);
		self::reloadPlayersBasicInfos();

		self::setGameStateInitialValue('traveller_day_count', 0);
		self::setGameStateInitialValue('deck_emptied', 0);
		self::setGameStateInitialValue('action_round', 0);
		self::setGameStateInitialValue('peasant_selected', 0);
		self::setGameStateInitialValue('annex_room_select', 0);
		self::setGameStateInitialValue('last_guest_type_id', 0);
		self::setGameStateInitialValue('last_round', 0);
		self::setGameStateInitialValue('burial_location', 0);

		// setup the initial game situation 
		$this->createGuestDeck();
		$this->populatePlayerHand($players);
		$this->createTravelerDeck($players);
		$this->initStats();

		$starting_franc = 5;
		$starting_checks = 1;
		$num_players = 0;
		foreach ($players as $player_id => $player) {
			//set starting money
			$this->modifyFranc($player_id, $starting_franc);
			$this->modifychecks($player_id, $starting_checks);

			//create player room
			$room_id = "bottom_card" . strval(++$num_players);
			$color = $this->getPlayerColorById($player_id);
			$this->dbPopulateRoom($room_id, $player_id, $color, $guest_id = null);
			$this->setScore($player_id);
		}

		//create neutral rooms
		$num_room_white = ($num_players <= 3) ? 3 : 4;
		for ($i = 1; $i <= $num_room_white; $i++) {
			$room_id = "top_card" . strval($i);
			$this->dbPopulateRoom($room_id, "neutral", "white", $guest_id = null);
		}


		$this->activeNextPlayer();
		$starting_player_id = $this->getActivePlayerId();

		self::setGameStateInitialValue('starting_player', $starting_player_id);
	}


	protected function getAllDatas()
	{
		$result = array();
		$current_player_id = self::getCurrentPlayerId();    // !! We must only return informations visible by this player !!

		$sql = "SELECT player_id id, player_name, player_color, franc, checks,  player_score score FROM player ";
		$result['players'] = self::getCollectionFromDb($sql);
		$result['display'] = $this->dbGetAllGuests();
		$result['rooms'] = $this->dbRommsGetAll();
		$result['hand'] = $this->guest_decks->getCardsInLocation('hand', $current_player_id);
		$result['bistro_numbers'] = $this->dbGetNumInLocationArg("bistro");
		$result['travellers_in_deck'] = $this->guest_decks->countCardInLocation('traveler_deck');
		$result['cards_in_game'] = self::getGameStateValue('cards_in_game');
		$result['deck_num'] = self::getGameStateValue('deck_emptied');
		$result['game_state'] = $this->getStateName();
		$result['player_kills'] = $this->getAllPlayersNumKilled();
		$result['player_action'] = self::getGameStateValue('player_action');
		$free_guest = $this->dbGetFreeActionInfo($current_player_id);
		if ($free_guest) 	$result['free_action_first_victim'] = $free_guest;
		return $result;
	}

	function getGameProgression()
	{
		$cards_in_deck_to_play  = $this->guest_decks->countCardInLocation('traveler_deck');
		$decks_played = self::getGameStateValue('deck_emptied');
		$cards_in_deck  = self::getGameStateValue('cards_in_game');


		$percent_played = intval(100 *   ($cards_in_deck - $cards_in_deck_to_play)) / (2 * ($cards_in_deck));

		if ($decks_played > 1) {
			$percent_played =  100;
		} else if ($decks_played > 0) {
			$percent_played += 50;
		}
		return $percent_played;
	}


	///////////////////////////////////////////////////////////////////////////////
	//////////// Utility functions
	////////////    

	function createGuestDeck()
	{
		$cards = array();
		foreach ($this->guests as $guest => $guest_value) {
			switch ($guest) {
				case 'cultivator':
					$cards[] = array('type' => $guest, 'type_arg' =>  27, 'nbr' => 4);
					break;
				case 'mechanic':
					$cards[] = array('type' => $guest, 'type_arg' =>  26, 'nbr' => 4);
					break;
				case 'distiller':
					$cards[] = array('type' => $guest, 'type_arg' =>  25, 'nbr' => 3);
					break;
				case 'gardener':
					$cards[] = array('type' => $guest, 'type_arg' =>  24, 'nbr' => 1);
					break;
				case 'landscaper':
					$cards[] = array('type' => $guest, 'type_arg' =>  23, 'nbr' => 1);
					break;
				case 'butcher':
					$cards[] = array('type' => $guest, 'type_arg' =>  22, 'nbr' => 1);
					break;
				case 'newsboy':
					$cards[] = array('type' => $guest, 'type_arg' =>  21, 'nbr' => 4);
					break;
				case 'representative':
					$cards[] = array('type' => $guest, 'type_arg' =>  20, 'nbr' => 4);
					break;
				case 'concierge':
					$cards[] = array('type' => $guest, 'type_arg' =>  19, 'nbr' => 3);
					break;
				case 'grocer':
					$cards[] = array('type' => $guest, 'type_arg' =>  18, 'nbr' => 1);
					break;
				case 'shopkeeper':
					$cards[] = array('type' => $guest, 'type_arg' =>  17, 'nbr' => 1);
					break;
				case 'brewer':
					$cards[] = array('type' => $guest, 'type_arg' =>  16, 'nbr' => 5);
					break;
				case 'baron':
					$cards[] = array('type' => $guest, 'type_arg' =>  15, 'nbr' => 4);
					break;
				case 'viscount':
					$cards[] = array('type' => $guest, 'type_arg' =>  14, 'nbr' => 4);
					break;
				case 'count':
					$cards[] = array('type' => $guest, 'type_arg' =>  13, 'nbr' => 3);
					break;
				case 'duke':
					$cards[] = array('type' => $guest, 'type_arg' =>  12, 'nbr' => 1);
					break;
				case 'prince':
					$cards[] = array('type' => $guest, 'type_arg' =>  11, 'nbr' => 1);
					break;
				case 'marquis':
					$cards[] = array('type' => $guest, 'type_arg' =>  10, 'nbr' => 1);
					break;
				case 'peacekeeper':
					$cards[] = array('type' => $guest, 'type_arg' =>  9, 'nbr' => 4);
					break;
				case 'brigadier':
					$cards[] = array('type' => $guest, 'type_arg' =>  8, 'nbr' => 4);
					break;
				case 'brigadier_chief':
					$cards[] = array('type' => $guest, 'type_arg' =>  7, 'nbr' => 3);
					break;
				case 'major':
					$cards[] = array('type' => $guest, 'type_arg' =>  6, 'nbr' => 3);
					break;
				case 'novice':
					$cards[] = array('type' => $guest, 'type_arg' =>  5, 'nbr' => 4);
					break;
				case 'monk':
					$cards[] = array('type' => $guest, 'type_arg' =>  4, 'nbr' => 4);
					break;
				case 'abbot':
					$cards[] = array('type' => $guest, 'type_arg' =>  3, 'nbr' => 3);
					break;
				case 'priest':
					$cards[] = array('type' => $guest, 'type_arg' =>  2, 'nbr' => 1);
					break;
				case 'bishop':
					$cards[] = array('type' => $guest, 'type_arg' =>  1, 'nbr' => 1);
					break;
				case 'archbishop':
					$cards[] = array('type' => $guest, 'type_arg' =>  0, 'nbr' => 1);
					break;
				case 'peasant2':
					$cards[] = array('type' => $guest, 'type_arg' =>  28, 'nbr' => 4);
					break;
				case 'peasant1':
					$cards[] = array('type' => $guest, 'type_arg' => 29, 'nbr' => 4);
					break;
			}
		}
		$this->guest_decks->createCards($cards, 'draw_deck');
		$this->guest_decks->shuffle('draw_deck');
	}
	function populatePlayerHand($players)
	{
		$peasant1_deck =  $this->guest_decks->getCardsOfType('peasant1');
		$peasant2_deck =  $this->guest_decks->getCardsOfType('peasant2');
		$peasant_deck = array_merge($peasant1_deck, $peasant2_deck);
		$func = function ($c) {
			return $c['id'];
		};
		$card_ids = array_map($func, $peasant_deck);
		$this->guest_decks->moveCards($card_ids, 'peasant_deck');
		$this->guest_decks->shuffle('peasant_deck');
		foreach ($players as $player_id => $player) {
			$this->guest_decks->pickCards(2, 'peasant_deck', $player_id);
			$cards = $this->guest_decks->getCardsInLocation('hand', $player_id);
			foreach ($cards as $card_id => $card) {
				$this->dbPopulateGuest($card, "hand", $player_id);
			}
		}
		// for solitare game put 2 peasants in bistro
		$num_players = $this->getPlayersNumber();
		if ($num_players == 1) {
			$this->guest_decks->pickCardsForLocation(2, 'peasant_deck', 'discard', 'bistro');
			$bistro_cards = $this->guest_decks->getCardsInLocation('discard', 'bistro');
			foreach ($bistro_cards as $bistro_card_id => $bistro_card) {
				$this->dbPopulateGuest($bistro_card, 'discard', 'bistro');
			}
		}
	}

	function createTravelerDeck($players)
	{
		$num_players = count($players);
		$game_length = self::getGameStateValue('game length');
		switch ($num_players) {
			case 1:
				$num_cards = ($game_length == 2) ? 	34 : 26;
				break;
			case 2:
				$num_cards = ($game_length == 2) ? 	35 : 25;
				break;
			case 3:
				$num_cards = ($game_length == 2) ? 	28 : 16;
				break;
			case 4:
				$num_cards = ($game_length == 2) ? 	22 : 6;
				break;
		}
		$cards_in_deck = $this->guest_decks->countCardsByLocationArgs('draw_deck');
		$cards_in_traveler_deck = count($cards_in_deck) - $num_cards;
		$this->guest_decks->pickCardsForLocation($cards_in_traveler_deck, 'draw_deck', 'traveler_deck');
		$this->guest_decks->shuffle('traveler_deck');
		self::setGameStateInitialValue('cards_in_game', $cards_in_traveler_deck);
	}

	function getStateName()
	{
		$state = $this->gamestate->state();
		return $state['name'];
	}
	function getAllPlayersNumKilled()
	{
		$players_kills = [];
		$players = self::loadPlayersBasicInfos();
		foreach ($players as $player_id => $player) {
			$players_kills[$player_id] = $this->dbGetNumByLocaleAndLocationArg("killed", $player_id);
		}
		return $players_kills;
	}
	function checkoutGuests($guests_in_inn)
	{
		$guests_checking_out = $this->guest_decks->getCardsInLocation('room');

		$this->guest_decks->moveAllCardsInLocation('room', 'discard', null, 'exit_stack');
		$this->dbRoomSetAllRoomsGuest_id(); // set db room guest_id to null
		// db room okay not to clear as each room will be filled in admitGuest state
		foreach ($guests_checking_out as $guest) {
			$this->notifyGuestLocation($guest["id"]);
		}
	}
	//updates db guest and room with $to and $to_arg - 
	//parameters $traveler_info guest array from db guest, $to - new locale  $to_arg - new locale_arg - 
	function moveTraveller($action, $traveler_info, $to, $to_arg = 0)
	{
		$old_locale = array();
		array_push($old_locale, $traveler_info['locale'], $traveler_info['locale_arg']);
		$this->guest_decks->moveCard($traveler_info['guest_id'], $to, $to_arg);
		switch ($action) { // remove traveller from room  - all other db updated 
			case "bribed":
			case "killed":
				$this->dbRoomUpdateGuest_id($traveler_info['locale_arg']);
				break;
			case "built":
			case "burried":

				break;
			default:
				break;
		}

		return $old_locale;
	}
	function releaseAcccomplice($card, $action, $player)
	{
		$locale_arg = (($card["guest_type"] == "peasant1") || ($card["guest_type"] == "peasant2")) ? 'bistro' : 'exit_stack';
		$old_locale = $this->moveTraveller("", $card, "discard", $locale_arg);
		$this->notifyGuestAction($action, $card["guest_type"], $card["guest_id"], $old_locale, $player);
	}


	function performAction($action, $guest_info, $player, $locale_arg = null)
	{
		switch ($action) {
			case "bribed":
				$old_locale = $this->moveTraveller($action, $guest_info, "hand", $player);
				break;
			case "killed":
				$old_locale = $this->moveTraveller($action, $guest_info, "killed", $player);
				break;
			case "built":
				$old_locale = $this->moveTraveller($action, $guest_info, "annex", $player);
				$francs_earned = $this->builtAnnexBonuses($guest_info["guest_type"], $guest_info["type_id"],  $player);
				if ($francs_earned  > 0) {
					$worth = $this->modifyFranc($player, $francs_earned);
					$this->notifyPayment($player, $francs_earned, $worth, self::_(" paid "), $guest_info["guest_type"]);
					$this->incStat($francs_earned, 'game_annex_franc', $player);
				}
				break;
			case "burried":
				$old_locale = $this->moveTraveller($action, $guest_info, "burried", $locale_arg);
				$paid = $this->getPocketMoney($guest_info["guest_type"], $guest_info["pocket"],  $locale_arg, $player);
				break;
		}
		$this->notifyGuestAction($action, $guest_info["guest_type"],  $guest_info["guest_id"],  $old_locale, $player);
		$stas_key = "game_number_" . $action;
		$this->incStat(1, $stas_key, $player);
	}

	//check deck  update set 
	function manageTravellerDeck()
	{

		$cards_in_traveller_deck = $this->guest_decks->countCardInLocation('traveler_deck');
		$nun_rooms = $this->getNumRooms();
		$num_players = $this->getPlayersNumber();

		if ($cards_in_traveller_deck < $nun_rooms) {
			$deck_emptied = self::incGameStateValue('deck_emptied', 1);
			if ($deck_emptied  > 1) {
				if ($cards_in_traveller_deck < $num_players) {
					$this->performEndGame();
				} else {
					self::setGameStateValue('last_round', 1);
				}
			} else {
				$this->guest_decks->moveAllCardsInLocation('discard',  'temp_location', 'exit_stack');
				$this->guest_decks->shuffle('temp_location');
				$this->guest_decks->moveAllCardsInLocation('temp_location',  'traveler_deck');
			}
		}
		return self::getGameStateValue('deck_emptied');
	}

	function performEndGame()
	{
		$grave_diggers_used = $this->getGameStateValue("grave_diggers_sent");
		if (!$grave_diggers_used) $this->hireGraveDiggers();

		$players = self::loadPlayersBasicInfos();
		foreach ($players as $player_id => $player) {

			$landscaper_bonuses = $this->dbGetNumByLocaleAndLocationArgType("annex", $player_id,   'landscaper')  *  (4 *  $this->dbGetNumByColorAndLocationArg('red', 'exit_stack'));
			$francs = $this->modifyFranc($player_id, $landscaper_bonuses);
			$this->notifyPayment($player_id, $landscaper_bonuses, $francs, "paid", "landscaper");
			$this->incStat($landscaper_bonuses, 'game_annex_franc', $player_id);

			$grocer_bonuses = $this->dbGetNumByLocaleAndLocationArgType("annex", $player_id, 'grocer')  *  (4 *  $this->dbGetNumByColorAndLocationArg('blue', 'exit_stack'));
			$francs = $this->modifyFranc($player_id, $grocer_bonuses);
			$this->notifyPayment($player_id, $grocer_bonuses, $francs, "paid", "grocer");
			$this->incStat($grocer_bonuses, 'game_annex_franc', $player_id);


			$bishop_bonuses = $this->dbGetNumByLocaleAndLocationArgType("annex", $player_id,  'bishop')  *  (4 *  $this->dbGetNumByColorAndLocationArg('purple', 'exit_stack'));
			$francs = $this->modifyFranc($player_id, $bishop_bonuses);
			$this->notifyPayment($player_id, $bishop_bonuses, $francs, "paid", "bishop");
			$this->incStat($bishop_bonuses, 'game_annex_franc', $player_id);

			$duke_bonuses = $this->dbGetNumByLocaleAndLocationArgType("annex", $player_id, 'duke')  *  (4 *  $this->dbGetNumByColorAndLocationArg('green', 'exit_stack'));
			$francs = $this->modifyFranc($player_id, $duke_bonuses);
			$this->notifyPayment($player_id, $duke_bonuses, $francs, "paid", "duke");
			$this->incStat($duke_bonuses, 'game_annex_franc', $player_id);

			$prince_bonuses = $this->dbGetNumByLocaleAndLocationArgType("annex", $player_id,  'prince')  *  (3 *  $this->dbGetChecks($player_id));
			$francs = $this->modifyFranc($player_id, $prince_bonuses);
			$this->notifyPayment($player_id, $prince_bonuses, $francs, "paid", "prince");
			$this->incStat($prince_bonuses, 'game_annex_franc', $player_id);
		}
		$this->gamestate->nextState('gameEnd');
	}
	//////////////////////////////////////////////////////////////////////////////
	//////////// Player actions
	//////////// 



	function admitGuest($info)
	{
		// get data from ajax
		$this->checkAction("admitGuest");
		$information = explode(' ', $info);
		$room = array_pop($information); // node_id without #
		$traveler = array_pop($information); // guest_id
		$owner = array_pop($information); // room owner neutral or player id

		// put guest in room and increment global varible traveller 
		$this->dbRoomUpdateGuest_id($room, $traveler);
		$traveler_db_info = $this->dbGetGuest($traveler);
		$this->conciergeAnnexBenefit($room);
		$this->moveTraveller("admitGuest", $traveler_db_info, 'room', $room);
		$traveller_info = $this->guest_decks->getCard($traveler);
		$traveller_type = $traveller_info['type'];
		$player_name = $this->gtPlayerNameWithID($owner);

		$owner_name = (is_null($player_name)) ? $owner : $player_name;
		$this->notifyGuestRegistration($traveller_type, "room",  $traveller_info, $owner_name);
		$this->gamestate->nextState('admitGuest');
	}

	function playerAction($info)
	{
		self::setGameStateValue('peasant_selected', 0);
		$num_peasants_bribed = 0;
		$guest2 = null;
		$burial_location = null;
		$information = explode(' ', $info);
		$action = array_shift($information); // returns action_btn eg bribed_btn  or brided_2_btn for peasants bribe from js 
		$player = array_pop($information);
		$this->dbUpdateFreeActionInfo($player);
		$burial_id  = null;

		// check for paasant action and guest id  - prep in case 2 peasnts
		$guest = array_shift($information);
		if ($guest == 'peasant') { // get a peasant guest id  - 2 if 2 peasaant bribed
			self::setGameStateValue('peasant_selected', 1);
			$peasants = $this->guest_decks->getCardsInLocation("discard", 'bistro');
			$peasant = array_pop($peasants);
			$guest = $peasant["id"];
			if ($action != "killed") {
				$peasant_action_arr = explode('_', $action);
				$num_peasants_bribed  = array_pop($peasant_action_arr); // num peasants bribed
				$action = $peasant_action_arr[0];
			}
		}
		if ($action == 'burried') {
			$burial_location = array_pop($information);
			$burial_location = $this->burialSiteLegitimate($burial_location);
			if (!$burial_location) throw new BgaUserException(self::_("You must select an available burial site "));
			$burial_array = explode('_',  $burial_location);
			$burial_id = $burial_array[1];
		}
		$this->checkAction($action);
		$guest_info = $this->dbGetGuest($guest);

		if ($this->actionLegit($action, $guest_info)) {
			// handle and check accomplice payments
			$annexes = $this->dbGetGuestsByLocaleAndLocaleArg("annex", $player);
			$free_action = $this->checkForFreeAction($player, $action);
			if ($free_action == null) {
				$payment_met = $this->checkHandPayment($guest, $annexes, $action, $guest_info["ranked"], $information);
				if (!$payment_met["paid"]) {
					$this->setGameStateValue("player_action", $this->actionNumberConvert($action));
					$this->setGameStateValue("current_guest", $guest);
					if ($action == 'burried') 	$this->setGameStateValue("burial_location", intval($burial_id));
					$this->gamestate->nextState('selectAccomplice');
				} else {
					// perform the action
					$this->performAction($action, $guest_info, $player, $burial_location);
					for ($peasant_num = 2; $peasant_num <= $num_peasants_bribed; $peasant_num++) {
						$peasant = array_pop($peasants);
						$guest = $peasant["id"];
						$guest_info = $this->dbGetGuest($guest);
						$this->performAction($action, $guest_info, $player);
					}
					$annex_select_status = self::getGameStateValue('annex_room_select');
					if ($annex_select_status  == 0)   $this->gamestate->nextState('nextPlayer');
				}
			} else {
				$free_action_info = $guest;
				if ($action == 'burried') {
					$free_action_info =  $free_action_info . " " . $burial_location;
					$this->dbIncrementReserved_burials($burial_id);
				}
				$this->dbUpdateFreeActionInfo($player,  $free_action_info);
				self::notifyPlayer($player, 'free_action', '', array(
					'action' =>  $action, // 	action needed as new state happens after notify completed
					'guest' =>  $free_action_info
				));
				$this->gamestate->nextState($free_action);
			}
		} else {
			throw new feException(sprintf(self::_("%s  cannot %s , please click start over to redo your action"), $guest_info["guest_type"],  $action), true);
		}
	}

	function getAccomplices($info)
	{

self::notifyAllPlayers("test", ('${info} is sent'), array(
	'info' => $info
));
		$information = explode(' ', $info);
		$to_do = array_shift($information);
		$this->checkAction($to_do);
		if ($to_do == "SO") {
			$this->dbClearReserved_burials();
			$this->gamestate->nextState('playerTurn');
		} elseif ($to_do == "accomplice") {
			$player = array_shift($information);
			$discount = array_shift($information);
			$free_action_info = $this->dbGetFreeActionInfo($player);
			// if free_action_info is null then not possible free action use normal processing
			if (is_null($free_action_info)) 	$this->processActionWithAccomplices($information, $player, $discount);
			else $this->processFreeAction($information, $free_action_info,  $player, $discount);
		}
	}

	function processFreeAction($information, $free_action_info,  $player, $discount)
	{
		$free_action_array = explode(' ', $free_action_info);
		$action = array_shift($free_action_array);
		$guest_info = array();
		if ($action == 'burried') { // separate guests from buirals 
			$burials_array =  array();
			$victim_burial_split = $this->separateArrayEvenElementKey($free_action_array);

			$free_action_array = array_shift($victim_burial_split);
			$burial_location = array_shift($victim_burial_split);
		}
		foreach ($free_action_array as $guest_id) 	$guest_info[$guest_id]  = $this->dbGetGuest($guest_id);
		$annexes = $this->dbGetGuestsByLocaleAndLocaleArg("annex", $player);
		$payment_met = $this->checkHandPayment($free_action_array, $annexes, $action, $guest_info, $information,  $discount);

		if (!$payment_met["paid"]) {
			throw new feException(sprintf(self::_("You must play %s cards from your hand  "), $payment_met["require_payment"]), true);
		} else {
			// remove accomplices except if with priest annex
			if (!(($action == 'burried') &&  ($this->dbGetNumByLocaleAndLocationArgType("annex", $player,  "priest") > 0))) {
				//discard  non affinity accomplices
				foreach ($information as $card_id) {
					$card = $this->dbGetGuest($card_id);
					if ($card["affinity"] !=  $action) {
						$this->releaseAcccomplice($card, "spent", $player);
					}
				}
			}
		}
		foreach ($free_action_array as $guest_id) {
			$guest_info = $this->dbGetGuest($guest_id);
			($action == 'burried') ? $this->performAction($action, $guest_info, $player, $burial_location[$guest_id]) : $this->performAction($action, $guest_info, $player);
		}
		$annex_select_status = self::getGameStateValue('annex_room_select');
		if ($annex_select_status  == 0)   $this->gamestate->nextState('nextPlayer');
	}
	function processActionWithAccomplices($information, $player, $discount)
	{
		$action = $this->actionNumberConvert($this->getGameStateValue("player_action"));
		$guest = $this->getGameStateValue("current_guest");
		$burial_location = $this->getGameStateValue("burial_location");
		if ($action == 'burried') 	$burial_location = "annex_" . $burial_location;
		$guest_info = $this->dbGetGuest($guest);
		$annexes = $this->dbGetGuestsByLocaleAndLocaleArg("annex", $player);
		$payment_met = $this->checkHandPayment($guest, $annexes, $action, $guest_info["ranked"], $information, $discount);
		if (!$payment_met["paid"]) {
			throw new feException(sprintf(self::_("You must play %s cards from your hand  "), $payment_met["require_payment"]), true);
		} else {
			// remove accomplices except if with priest annex
			if (!(($action == 'burried') &&  ($this->dbGetNumByLocaleAndLocationArgType("annex", $player,  "priest") > 0))) {
				// deal with accomplices - put into locale discard all spent cards  with guest affinity != spend card affinity or affinity == none
				foreach ($information as $card_id) {
					$card = $this->dbGetGuest($card_id);
					if ($card["affinity"] !=  $action) {
						$this->releaseAcccomplice($card, "spent", $player);
					}
				}
			}
		}
		$this->performAction($action, $guest_info, $player, $burial_location);
		$annex_select_status = self::getGameStateValue('annex_room_select');
		if ($annex_select_status  == 0)   $this->gamestate->nextState('nextPlayer');
	}

	function passConfirm($info)
	{
		$information = explode(' ', $info);
		$player_id = array_pop($information);
		$checks_gained = array_pop($information);
		$action = array_pop($information);
		$this->checkAction($action);
		$francs_difference = intval($checks_gained) * - (10);
		$current_amount_francs = $this->dbGetFranc($player_id);
		$current_amount_checks = $this->dbGetChecks($player_id);
		if (($current_amount_francs + $francs_difference < 0) || ($current_amount_checks  + $checks_gained < 0)) {
			throw new BgaUserException(self::_(" You do not have the funds for your request, please restate your transaction "));
		} else {
			$francs = $this->modifyFranc($player_id, $francs_difference);
			$checks =  $this->modifychecks($player_id, intval($checks_gained));
			$this->notifyPlayerAct("pass", $checks, $francs, $player_id);
			$this->incStat(1, 'game_number_launder', $player_id);
			$this->gamestate->nextState('nextPlayer');
		}
	}
	function checkBurialSite($info)
	{
		$this->checkAction("burried");
		$burial_okay = $this->burialSiteLegitimate($info);

		if ($burial_okay) {
			$burial_arr = explode('_', $info);
			$annex_id = "annex_" . $burial_arr[1];
			$this->dbIncrementReserved_burials($burial_arr[1]);
			$player = $this->getActivePlayerId();
			self::notifyPlayer($player, 'burial_good', '', array('burial' =>  $info));
		} else {
			throw new BgaUserException(self::_("You must select an available burial site "));
		}
	}
	function doneConfirm($info)
	{
		$state = $this->gamestate->state();
		$action_capitalized = str_replace("free", "", $state['name']);
		$action = strtolower($action_capitalized);
		$this->checkAction("done");
		$free_action_info =  $action . " " . $info;
		$player = $this->getActivePlayerId();
		$this->dbUpdateFreeActionInfo($player,  $free_action_info);
		$this->gamestate->nextState('selectAccomplice');
	}

	function confirmSelection($info)
	{
		$wages_paid = false;
		$information = explode(' ', $info);
		$action = array_shift($information);
		$player_id = array_pop($information);
		$this->checkAction($action);
		if ($action == "remove") {

			$player_francs = $this->dbGetFranc($player_id);
			while (!$wages_paid) {
				$wages = $this->getWages($player_id);
				if ($player_francs >= $wages[1] - count($information)) {
					//remove accomplice to bistro or exit_stack
					foreach ($information as $card_id) {
						$card = $this->dbGetGuest($card_id);
						$this->releaseAcccomplice($card, "removed", $player_id);
					}
					$wages = $this->getWages($player_id);
					$payment = $this->playerPaysFrancs($player_id, $wages[0]);

					$this->notifyPayment($player_id, $payment[0], $payment[1], "pays", "accomplices");

					$wages_paid = true;
					$this->gamestate->nextState('endRound');
				} else {
					$most_kept = $wages[2] + $player_francs;
					throw new feException(sprintf(self::_("you can keep at most %s  accomplices "), $most_kept), true);
				}
			}
		} elseif ($action == "confirmRoom") {
			$room_seleced = $information[0];
			$guest_type = self::getGameStateValue('last_guest_type_id');
			if ($guest_type == 4) { // is monk
				$room_owner = $this->dbRoomGetOwner($room_seleced);
				if ($room_owner == "neutral") {
					$this->dbRoomUpdateOwner($room_seleced, $player_id);
					$this->notifyPlayerAction($player_id, self::_("gains room"), $room_seleced, $room_seleced);
					self::setGameStateValue('annex_room_select', 0); // set global so state can now transition away from selectRoom state
					$this->gamestate->nextState('nextPlayer');
				} else {
					throw new BgaUserException(self::_("you must select a newtral room"));
				}
			} elseif ($guest_type == 19) { // is concierge
				$room = $this->dbRoomByLocale_arg($room_seleced);
				$concierge_id = $room["concierge_id"];
				if (strlen($concierge_id) < 1) {
					$this->dbRoomUpdateConcierge_id($room_seleced, $player_id);
					$this->notifyPlayerAction($player_id, self::_(" adds service token to room"), $room_seleced, $room_seleced);
					$this->conciergeAnnexBenefit($room_seleced);
					self::setGameStateValue('annex_room_select', 0); // set global so state can now transition away from selectRoom state
					$this->gamestate->nextState('nextPlayer');
				} else {
					throw new BgaUserException(self::_("you must select a room without a service token "));
				}
			}
		}
	}


	//////////////////////////////////////////////////////////////////////////////
	//////////// Game state actions
	////////////


	// count number of travellers processed 
	// if traveller >=  room limit -> start playerTurn stt 
	function stAdmitGuest()
	{

		$traveller = self::incGameStateValue('traveller_day_count', 1);
		$num_travellers  =  $this->getNumRooms();
		$draw_deck_count = $this->guest_decks->countCardInLocation('traveler_deck');

		if (($traveller <=  $num_travellers) && ($draw_deck_count > 0)) {
			$this->guest_decks->pickCardForLocation('traveler_deck', 'display');
			$guest_location = $this->guest_decks->getCardsInLocation('display');
			$new_guest = array_pop($guest_location);
			$this->dbPopulateGuest($new_guest, 'entrance_stack', 'entrance_stack');

			$guest_info = $this->dbGetGuest($new_guest["id"]);
			$draw_deck_count = $this->guest_decks->countCardInLocation('traveler_deck');
			$cards_in_game = self::getGameStateValue('cards_in_game');
			$traveller_set = self::getGameStateValue('deck_emptied');
			$this->notifyNewGuest($guest_info, $new_guest['type'], $cards_in_game, $draw_deck_count, $traveller_set);
			$this->gamestate->nextState('assignGuestRoom');
		} else {
			self::setGameStateValue('traveller_day_count', 0);
			$this->gamestate->nextState('playerTurn');
		}
	}
	// gets next player and next starting player at round end - this is only module except setup where activeNextPlayer() is used
	// readies GameStateVaribles for next player
	function stNextPlayer()
	{
		self::setGameStateValue('grave_diggers_sent', 0);
		$this->dbClearReserved_burials();
		$round_end = false;
		$game_end = false;
		$current_player_id =  $this->getActivePlayerId();
		$this->activeNextPlayer();
		$active_player_id = $this->getActivePlayerId();
		$starting_player_id = self::getGameStateValue('starting_player');
		if ($active_player_id == $starting_player_id) {
			$action_round = self::incGameStateValue('action_round', 1);

			if ($action_round > 1) { // update game state value starting_player - to player to left of last starting player -  then do morning activities 
				self::setGameStateInitialValue('action_round', 0);
				$this->activeNextPlayer();
				$starting_player_id = $this->getActivePlayerId();
				self::setGameStateInitialValue('starting_player', $starting_player_id);

				// morning - end of round
				$guests_in_inn = $this->dbGuestsInLocale("room");

				$police_present = $this->checkTravelerArrayForColor($guests_in_inn, 'grey');
				if ($police_present) {
					self::setGameStateValue('grave_diggers_sent', 1);
					$grave_digger_fees = $this->hireGraveDiggers();

					if ($this->getPlayersNumber() == 1) {
						if ($grave_digger_fees[$active_player_id]  >= 10) {
							$game_end = true;
							$this->performEndGame();
						}
					}
				}
				$this->payRoomOwners($guests_in_inn);
				$this->checkoutGuests($guests_in_inn);

				$round_end = true;
				$this->gamestate->nextState('endRound');
			}
		}
		if (!$round_end) {
			self::giveExtraTime($this->getActivePlayerId());
			$this->gamestate->nextState('playerTurn');
		}
	}

	function stEndRound()
	{
		$starting_player_pay_status = self::getGameStateValue('start_player_paid');
		$starting_player_id = self::getGameStateValue('starting_player');

		// end round starts with player left of starting player and continues until starting player also processed - starting_player_pay_status marks this
		if ($starting_player_pay_status == 0) {
			$this->activeNextPlayer();

			$player_id = $this->getActivePlayerId();
			if ($starting_player_id == $player_id) self::setGameStateInitialValue('start_player_paid', 1);

			// process morniing annex payouts 
			$gardener_payout = $this->dbGetNumByLocaleAndLocationArgType("annex", $player_id,  "gardener") * 2;
			$worth = $this->modifyFranc($player_id, $gardener_payout); // gardener annex payouts
			if ($gardener_payout > 0) {
				$this->notifyPayment($player_id, $gardener_payout, $worth, self::_("paid"), self::_("gardener annex"));
				$this->incStat($gardener_payout, 'game_annex_franc', $player_id);
			}

			$wages = $this->getWages($player_id);

			if ($wages[0] > $worth) { // wage payment happens during payAccomplice state
				$this->gamestate->nextState('payAccomplice');
			} else {
				$payment = $this->playerPaysFrancs($player_id, $wages[0]);
				$this->notifyPayment($player_id, $payment[0], $payment[1], "pays", "accomplices");
				$this->gamestate->nextState('endRound');
			}
		} else { // start new round
			// check for end game trigger 
			$end_game = self::getGameStateValue('last_round');
			if ($end_game == 1) $this->performEndGame();

			$this->gamestate->changeActivePlayer($starting_player_id);
			self::setGameStateInitialValue('start_player_paid', 0);
			$emptied = $this->manageTravellerDeck();
			if ($emptied > 1) $this->notifyGameEnd();
			$this->gamestate->nextState('admitGuest');
		}
	}

	//////////////////////////////////////////////////////////////////////////////
	//////////// Zombie
	////////////

	/*
        zombieTurn:
        
        This method is called each time it is the turn of a player who has quit the game (= "zombie" player).
        You can do whatever you want in order to make sure the turn of this player ends appropriately
        (ex: pass).
        
        Important: your zombie code will be called when the player leaves the game. This action is triggered
        from the main site and propagated to the gameserver from a server, not from a browser.
        As a consequence, there is no current player associated to this action. In your zombieTurn function,
        you must _never_ use getCurrentPlayerId() or getCurrentPlayerName(), otherwise it will fail with a "Not logged" error message. 
    */

	function zombieTurn($state, $active_player)
	{
		$statename = $state['name'];

		if ($state['type'] === "activeplayer") {
			switch ($statename) {
				default:
					$this->gamestate->nextState("zombiePass");
					break;
			}

			return;
		}

		if ($state['type'] === "multipleactiveplayer") {
			// Make sure player is in a non blocking status for role turn
			$this->gamestate->setPlayerNonMultiactive($active_player, '');

			return;
		}

		throw new feException("Zombie mode not supported at this game state: " . $statename);
	}

	///////////////////////////////////////////////////////////////////////////////////:
	////////// DB upgrade
	//////////

	/*
        upgradeTableDb:
        
        You don't have to care about this until your game has been published on BGA.
        Once your game is on BGA, this method is called everytime the system detects a game running with your old
        Database scheme.
        In this case, if you change your Database scheme, you just have to apply the needed changes in order to
        update the game database and allow the game to continue to run with your new version.
    
    */

	function upgradeTableDb($from_version)
	{
		// $from_version is the current version of this game database, in numerical form.
		// For example, if the game was running with a release of your game named "140430-1345",
		// $from_version is equal to 1404301345

		// Example:
		//        if( $from_version <= 1404301345 )
		//        {
		//            // ! important ! Use DBPREFIX_<table_name> for all tables
		//
		//            $sql = "ALTER TABLE DBPREFIX_xxxxxxx ....";
		//            self::applyDbUpgradeToAllDB( $sql );
		//        }
		//        if( $from_version <= 1405061421 )
		//        {
		//            // ! important ! Use DBPREFIX_<table_name> for all tables
		//
		//            $sql = "CREATE TABLE DBPREFIX_xxxxxxx ....";
		//            self::applyDbUpgradeToAllDB( $sql );
		//        }
		//        // Please add your future database scheme changes here
		//
		//


	}
}
