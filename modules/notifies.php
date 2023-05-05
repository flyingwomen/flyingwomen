<?php
/* All notiflies for game */

trait Notifies
{
	use BIDatabase;

	protected function gtPlayerNameWithID($player_id)
	{
		$players = self::loadPlayersBasicInfos();
		if (array_key_exists($player_id, $players)) {
			return $players[$player_id]["player_name"];
		} else {
			return null;
		}
	}
	protected function notifyNewGuest($guest_info, $new_guest, $cards_in_game, $deck_size, $deck_set)
	{ // for new travelers
		self::notifyAllPlayers("assignGuest", clienttranslate('${traveler} is new guest'), array(
			'guest_info' => $guest_info,
			'traveler' => $new_guest,
			'cards_in_game' => $cards_in_game,
			'deck_size' => $deck_size,
			'deck_set' =>  $deck_set,
		));
	}
	protected function notifyGuestLocation($guest_id, $action = "moved to")
	{ // for guest moves 
		$guest = $this->dbGetGuest($guest_id);
		self::notifyAllPlayers("guest_location", clienttranslate('${traveler} ${action} ${locale}'), array(
			'guest' => $guest,
			'action' =>  $action,
			'traveler' => $guest["guest_type"],
			'locale' => $guest["locale"],
			'locale_arg' => $guest["locale_arg"]
		));
	}
	protected function notifyPlayerAction($player_id, $action1, $action2, $affected)
	{
		$player_name = $this->getPlayerNameById($player_id);
		self::notifyAllPlayers("playerAct", clienttranslate('${player_name} ${action1} ${action2}'), array(
			'player_name' => $player_name,
			'player_id' => $player_id,
			'player_color' => $this->getPlayerColorById($player_id),
			'action1' => $action1,
			'action2' => $action2,
			'affected' => $affected
		));
	}

	protected function notifyPayment($player_id, $payment, $player_francs, $action, $payee)
	{
		$player_name = $this->getPlayerNameById($player_id);
		$score = $this->setScore($player_id);
		self::notifyAllPlayers("payments", clienttranslate('${player_name} ${action} ${payment} francs for ${payee}'), array(
			'player_name' => $player_name,
			'player_id' => $player_id,
			'payment' =>  $payment,
			'action' => $action,
			'player_francs' => $player_francs,
			'player_checks' => $this->dbGetChecks($player_id),
			'payee' => $payee,
			'score' => $score
		));
	}

	protected function notifyGuestRegistration($traveller_type, $locale, $traveller_info, $owner_name = "Hotel")
	{
		self::notifyAllPlayers("admission", clienttranslate('HOTEL LOG - ${traveler} can be found in ${locale} of ${owner_name}'), array(
			'owner_name' => $owner_name,
			'traveler' => $traveller_type,
			'locale' => $locale,
			'traveller_info' => $traveller_info
		));
	}

	protected function notifyGuestAction($action, $traveller_type, $traveler_id, $old_locale, $player)
	{ // notifies all of action and notifies private player
		$traveller_info = $this->dbGetGuest($traveler_id); // get updated traveller info
		$num_in_bistro = $this->dbGetNumInLocationArg("bistro");
		$player_name = $this->getPlayerNameById($player);


		self::notifyAllPlayers("log_action", clienttranslate('${traveler} has been ${action} by ${player_name}'), array(
			'action' => $action,
			'player_id' => $player,
			'player_name' => $player_name,
			'traveler' => $traveller_type,
			'traveller_info' => $traveller_info,
			'old_locale' => $old_locale,
			'num_in_bistro' => $num_in_bistro,
			'player_kill' => $this->dbGetNumByLocaleAndLocationArg("killed", $player)
		));

		self::notifyPlayer($player, "action", "", array(
			'action' => $action,
			'traveller_info' => $traveller_info,
			'old_locale' => $old_locale
		));
	}
	protected function notifyPlayerAct($action, $num_checks, $num_francs, $player)
	{
		$player_name = $this->getPlayerNameById($player);

		self::notifyAllPlayers("playerFrancAct", clienttranslate('${player_name} ${action}'), array(
			'action' => $action,
			'player_name' => $player_name,
			'player_id' => $player,
			'checks' =>  $num_checks,
			'francs' =>  $num_francs
		));
	}

	protected function notifyAboutGraveDiggers()
	{
		$num_in_bistro = $this->dbGetNumInLocationArg("bistro");
		$exit_stack = $this->dbGetGuestsByLocaleAndLocaleArg('discard', 'exit_stack');
		self::notifyAllPlayers("morning", clienttranslate('local grave diggers seen hanging around inn'), array(
			'exit_stack' => $exit_stack,
			'num_in_bistro' => $num_in_bistro,
			'all_players_kill' => $this->getAllPlayersNumKilled()
		));
	}
	protected function notifyGameEnd()
	{
		self::notifyAllPlayers("gameEnd", clienttranslate('Game End Triggered. This is the last round'), array());
	}
}
