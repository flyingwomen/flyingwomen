<?php
/* Traveler Utilities */

trait Travelers {
	use BIDatabase;
	use Notifies;
	
	// useful functions for any project
	function separateArrayEvenElementKey( $orginal_array, $even_array=array(), $odd_array=array()) { // returns array even element key of odd element
		if ( count( $orginal_array ) > 0 ) {
			$odd_key = array_shift( $orginal_array);
			array_push($even_array,$odd_key  );
			$odd_value =  array_shift( $orginal_array);
			$odd_array[ $odd_key ] = $odd_value;
			return $this->separateArrayEvenElementKey( $orginal_array, $even_array, $odd_array);
		} else {
			return  array( $even_array, $odd_array ) ;
		}
	}	
	protected function actionNumberConvert( $action ) {
		switch ( $action ) {
			case  'bribed' : return 1;
			case 'killed'  : return 2; 
			case 'built'   : return 3;
			case 'burried' : return 4; 
			case  1 : return 'bribed';
			case  2 : return 'killed'; 
			case  3 : return 'built' ;
			case  4 : return 'burried'; 
		}
	}
	protected function getNumRooms() {
		$num_players = self::getPlayersNumber();
		return   (  $num_players < 4 ) ? $num_players + 3 : $num_players + 4;
	}

	protected function setScore( $player_id) { 
		$francs = $this->dbGetFranc($player_id);
		$checks = $this->dbGetChecks($player_id);
		$wealth =  $checks* 10  + $francs ;
		$locale_arg = "annex_" . $player_id ;
		$number_buried = $this->dbGetNumByLocaleAndLocationArg("burried", $locale_arg );

		$this->dbSetScore( $player_id, $wealth);
		$this->dbSetAuxScore($player_id, $number_buried );
		
		return $wealth;
	}
	
	protected function checkHandPayment($guest_id, $annexes, $action, $rank, $payment /* cards spent */, $refused_discount=0) { 
		$payed = count( $payment);
		$require_payment = 0;

		if ( is_array($guest_id ) ) { // for bonus free actions
			foreach ( $guest_id as $current_id   ) {	
				$require_payment += $rank[$current_id]["ranked"];
				foreach ($payment as $accomplice_id ) {
					if ( $accomplice_id == $current_id ) return array( "paid"=>false, "require_payment"=>$require_payment);
				}	
			}
		} else {	
			$require_payment = $rank;
			foreach ($payment as $accomplice_id ) {
				if ( $accomplice_id == $guest_id ) return array( "paid"=>false, "require_payment"=>$require_payment);
			}
			
		}
		$deduction = self::annexCount( $annexes, $action ) - $refused_discount;
		$require_payment -= $deduction;
		$require_payment = ( $require_payment < 0 ? 0: $require_payment);
		$paid = $payed == $require_payment   ? true :  false ;
		return  array( "paid"=>$paid, "require_payment"=>$require_payment);
	}
	
	protected function actionLegit( $action, $guest ){
		$legitimacy = false;
		switch ($action) {
			case "built":
				if ( $guest["annex"] != "none" ) $legitimacy = true;
				break;
			default:
				$legitimacy = true;
				break;
		}
		return $legitimacy;
	}
	
	protected function burialSiteLegitimate ( $annex) { //if not legit return false else returns amended locale_arg top_card replaced with annex else
		$burial_arr = explode( '_', $annex);
		$amend_locale_arg = "annex_" .$burial_arr[1];
		$annex_num_burried = $this->dbGetNumInLocationArg( $amend_locale_arg );
		$num_rserved = $this->dbNumReserved_burials( $burial_arr[1] );
		$annex_num_burried += $num_rserved ;

		if ($burial_arr[1] > 20000 /* is player_aid annexe*/) {
 			if  ($annex_num_burried <= 0)  return $amend_locale_arg;
		} else { 
			$annex_info = $this->dbGetGuest($burial_arr[1]);
			if ( $annex_num_burried  < $annex_info['ranked']) return $amend_locale_arg;
			else return false;
		}
	}
	
	protected function getWages( $player_id ) {
		$wages = [];
		$discount = $this->dbGetNumByLocaleAndLocationArgType("annex", $player_id, "distiller"  );
		$num_accomplices = $this->dbGetNumByLocaleAndLocationArg('hand', $player_id  );
		$must_pay = $num_accomplices - $discount; 
		array_push( $wages, $must_pay, $num_accomplices, $discount );
		return $wages;
	} 
	
	protected function playerPaysFrancs( $player_id, $amt) { //when paying esnures player fracnc not less than 0  -returns array[0]  payment ,array[0]  franc in db player -  takes payments from db player francs or what they have - no negative values
		$results = [];
		$payment = $amt;
		$pre_payment_wealth =  $money_in_hand = $this->dbGetFranc($player_id);
		$money_in_hand -= $amt;
		if ( $money_in_hand < 0 ) {
			$money_in_hand = 0; 
			$payment = $pre_payment_wealth;
		} 
		$this->dbUpdateFranc( $player_id, $money_in_hand );
		array_push( $results, $payment, $money_in_hand );
		return $results;
		
	}

	protected function hireGraveDiggers() { // for each player - 1- charge $10 for all its guest with kill in locale 2- updates franc in db guest 3-notify everyone - returns array with what each player paid
		$players = self::loadPlayersBasicInfos();
		$non_burried_deads = $this->dbGuestsInLocale('killed');
		$burial_cost_by_player = array();
		foreach ( $players as $player_id => $player ) {
			$player_owes = 0;

			foreach ( $non_burried_deads as $corpse ) {
				if ( $corpse["locale_arg"] == strval($player_id ) ) 	$player_owes += 10;
				if (( $corpse["guest_type"] == 'peasant1' ) || ( $corpse["guest_type"] == 'peasant2' )) $this->dbUpdateLocaleGuestByGuestID( 'discard', $corpse["guest_id"], 'bistro' );
				else $this->dbUpdateLocaleGuestByGuestID( 'discard', $corpse["guest_id"], 'exit_stack' );
			}	
			$franc_amount = $this->dbGetFranc($player_id);
			$check_amount = $this->dbGetChecks($player_id);
			$num_bodies = intdiv($player_owes , 10 );
			$num_bodies_tobe_paid_with_checks = null;
			$num_bodies_payable_with_francs = intdiv( $franc_amount, 10 );	
			$num_bodies_paid_with_francs  = ( $num_bodies > $num_bodies_payable_with_francs ) ?  $num_bodies_payable_with_francs : 	$num_bodies ;
		
			$num_bodies_not_paid =$num_bodies -  $num_bodies_paid_with_francs ;
			if ( $num_bodies_not_paid > 0 ) {
				if ( $check_amount > 0 ) {
					$num_bodies_tobe_paid_with_checks = $num_bodies_not_paid  > $check_amount? $check_amount : $num_bodies_not_paid ;
					$this->modifychecks( $player_id, 0 - $num_bodies_tobe_paid_with_checks );
				}
			}
			$francs_paid = $num_bodies_paid_with_francs *10;
			
			if ( ( $num_bodies_paid_with_francs + $num_bodies_tobe_paid_with_checks ) < $num_bodies ) $francs_paid = $franc_amount;
	
			$payment = self::playerPaysFrancs( $player_id, $francs_paid);
			$check_payment = $num_bodies_tobe_paid_with_checks  * 10;
			$payment[0] += $check_payment;
			$burial_cost_by_player[ $player_id ] = $payment[0];
			
			$this->incStat( $payment[0] , 'game_gravedigger_franc', $player_id );
			$this->notifyPayment( $player_id, $payment[0], $payment[1],  self::_("pays"),  self::_( "local grave diggers" ));
		}
		$this->notifyAboutGraveDiggers();
		return $burial_cost_by_player;
	}
	
	
	protected function payRoomOwners() { // room db necessary to keep track of room owner
		
		$guests_in_romms = $this->dbGetRoomsByGuest();
		foreach ( $guests_in_romms as $room ) {
			if ( $room["owner_id"] !=  "neutral" ) {
				$wealth = $this->modifyFranc( $room["owner_id"], 1);
				$this->notifyPayment( $room["owner_id"], 1, $wealth,  self::_("paid") ,  self::_( "hotel fees")  ) ;
			}
		}
		
	}


	protected function checkTravelerArrayForColor( $travelers, $color ) { 
		$found = false;
		foreach ($travelers as $traveler) {
			if  ( $traveler["color"] ==  $color ) $found = true;
		}
		return $found;
	}
	

	
	protected function getPocketMoney( $guest_type, $francs_earned,  $annex, $player ) {

		$annex_arr = explode("_", $annex);
		$annex_info = $this->dbGetGuest($annex_arr[1]);
		
		if ( is_null( $annex_info )) $annex_owner = $annex_arr[1];
		else $annex_owner = $annex_info[ 'locale_arg'];

		if ( $annex_owner == $player ) {
			$wealth = $this->modifyFranc(  $player, $francs_earned );
			$this->notifyPayment(  $player, $francs_earned , $wealth,  self::_(" paid "), $guest_type);	
			$this->incStat( $francs_earned , 'game_bury_franc', $player );
		} else { 
			$francs_earned /= 2; 
			$wealth = $this->modifyFranc(   $player, $francs_earned );
			$this->notifyPayment(  $player, $francs_earned, $wealth ,  self::_(" paid "), $guest_type );
			$this->incStat( $francs_earned , 'game_bury_franc', $player );
			$wealth  = $this->modifyFranc(   $annex_owner, $francs_earned );
			$this->notifyPayment(  $annex_owner, $francs_earned, $wealth , self::_( " paid " ), $guest_type );
			$this->incStat( $francs_earned , 'game_bury_franc', $annex_owner );
		}
	}
	
	//does everything to built annex after moveTraveller -returns number francs gain
	protected function builtAnnexBonuses( $guest_type, $guest_type_id,  $player ) {

		switch ($guest_type) {
			case "cultivator":
				return $this->dbGetNumByLocaleAndLocationArgColor( "annex", $player, "red" );
				break;
			case "landscaper":
				return 4;
				break;
			case "newsboy":
				return $this->dbGetNumByLocaleAndLocationArgColor( "annex", $player, "blue" );
				break;
			case "concierge":
				self::setGameStateValue(  'annex_room_select', 1 );	// so game does not fall into state nextplayer		
				self::setGameStateValue('last_guest_type_id', $guest_type_id ); //set  global varible so guest info avail for next state
				$this->gamestate->nextState( "selectRoom" );
				break;
			case "grocer":
				return 4;
				break;
			case "novice":
				return $this->dbGetNumByLocaleAndLocationArgColor( "annex", $player, "purple" );
				break;
			case "monk":
				self::setGameStateValue(  'annex_room_select', 1 );		// so game does not fall into state nextplayer			
				self::setGameStateValue('last_guest_type_id', $guest_type_id ); //set  global varible so guest info avail for next state
				$this->gamestate->nextState( "selectRoom" );
				break;
			case "bishop":
				return 4;
				break;
			case "baron":
				return 4;
				break;
			case "viscount":
				return 6;
				break;
			case "count":
				return 9;
				break;
			case "duke":
				return 4;
				break;
			case "marquis":
				return 18;
				break;
		} 	
		return 0;
	}
	
	//transitions to appropriate state based on whether player has annex for infinite bride , killed or bury actios 
	protected function checkForFreeAction( $player, $action ) { 
		$peasant_selected = $this->getGameStateValue( 'peasant_selected' );
		switch ($action) {
			case "bribed":
				if (!$peasant_selected ) { 
					if  ( $this->dbGetNumByLocaleAndLocationArgType( "annex", $player, 'shopkeeper' ) > 0)	return  'freeBribed' ;
				}
				break;
			case "killed":
				if (!$peasant_selected ) { 
					if  ( $this->dbGetNumByLocaleAndLocationArgType( "annex", $player,  'butcher' ) > 0)	return 'freeKilled' ;
				}
				break;
			case "burried":
				if  ($this->dbGetNumByLocaleAndLocationArgType( "annex", $player, 'archbishop' ) > 0 )  return  'freeBurried' ;
				break;
		} 	
		return null;	
	}
	protected function conciergeAnnexBenefit(  $room_seleced) {

		$room = $this->dbRoomByLocale_arg(  $room_seleced  );
		$concierge_id = $room[ "concierge_id" ];
		if (  strlen($concierge_id) > 1 ) {// so no errant history notifies
			$guest = $this->dbGetGuest($room["guest_id"]);
			if (!empty($guest)) {
				$guest_rank = $guest["ranked"];
				$wealth = $this->modifyFranc( $concierge_id, $guest_rank );
				$this->notifyPayment( $concierge_id, $guest_rank, $wealth, self::_( "paid" ), self::_(" from concierge" )) ;
				$this->incStat( $guest_rank  , 'game_annex_franc', $concierge_id );
			}
		}
	}
	
	protected function annexCount( $annexes,  $query ) {
		$num_annexes = 0;
		foreach ($annexes as $annex) {
			switch ($query) {
				case "bribed":
					if  ( $annex["guest_type"] ==  'representative' ){
						$num_annexes++;
					}
					break;
				case "built":
					if  ( $annex["guest_type"] ==  'mechanic' ){
						$num_annexes++;
					}
					break;
				case "burried":
					if  ( $annex["guest_type"] ==  'abbot' ){
						$num_annexes++;
					}
			}
		}	
		return $num_annexes;
	}
}