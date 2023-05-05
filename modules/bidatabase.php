<?php
/* Database Utilities */

trait BIDatabase {

// multiple tables
// multiple tables
	protected function dbIncrementReserved_burials(  $burial_id, $amount=1 ) {
		if ( $burial_id < 20000 )	self::dbGuestIncrementReserved_burials( $burial_id, $amount );
		else 	self::dbPlayerIncrementReserved_burials( $burial_id, $amount );
	}
	protected function dbNumReserved_burials(  $burial_id ) {
		if ( $burial_id < 20000 )	return self:: dbGuestNumReserved_burials($burial_id );
		else 	return self::dbPlayerNumReserved_burials( $burial_id );
	}
	protected function dbClearReserved_burials(  $amount=0 ) {
		$this->DbQuery("UPDATE guest_deck SET reserved_burials ='$amount' " );
		$this->DbQuery("UPDATE player SET reserved_burials ='$amount' " );
	}

	
// db player
	protected function dbPlayerNumReserved_burials($player_id) {
		return $this->getUniqueValueFromDB("SELECT reserved_burials FROM player WHERE player_id='$player_id'" );
	}	
	protected function dbPlayerIncrementReserved_burials( $player_id, $amount=1 ) {
		$reserved = self::dbPlayerNumReserved_burials($player_id) ;
		$reserved += $amount;
		$this->DbQuery("UPDATE player SET reserved_burials ='$reserved' WHERE player_id='$player_id'" );
	}
	protected function modifyFranc( $player_id, $amt ) {
		$current_franc = $this->dbGetFranc($player_id);
		$current_franc += $amt;
		$current_franc = ( $current_franc > 40) ? 40: $current_franc;
		$this->dbUpdateFranc($player_id, $current_franc);	
		return $current_franc;
	}
	protected function dbUpdateFranc($player_id, $amt) {
		$this->DbQuery("UPDATE player SET franc ='$amt' WHERE player_id='$player_id'");
	}	
	protected function dbGetFranc($player_id) {
		return $this->getUniqueValueFromDB("SELECT franc FROM player WHERE player_id='$player_id'");
	}
	protected function modifychecks( $player_id, $amt ) {
		$current_checks = $this->dbGetChecks($player_id);
		$current_checks += $amt;
		$this->dbUpdateChecks($player_id, $current_checks);	
		return $current_checks;
	}
	protected function dbUpdateChecks($player_id, $amt) {
		$this->DbQuery("UPDATE player SET checks ='$amt' WHERE player_id='$player_id'");
	}	
	protected function dbGetChecks($player_id) {
		return $this->getUniqueValueFromDB("SELECT checks FROM player WHERE player_id='$player_id'");
	}
	protected function dbUpdateFreeActionInfo($player_id, $info='' ) {
		$this->DbQuery("UPDATE player SET free_action_info =NULLIF('$info', '') WHERE player_id='$player_id'");
	}
	protected function dbGetFreeActionInfo($player_id) {
		return $this->getUniqueValueFromDB("SELECT free_action_info FROM player WHERE player_id='$player_id'");
	}
	protected function dbSetScore($player_id, $count) {
       $this->DbQuery("UPDATE player SET player_score='$count' WHERE player_id='$player_id'");
	}
    protected function dbGetScore($player_id) {
       return $this->getUniqueValueFromDB("SELECT player_score FROM player WHERE player_id='$player_id'");
	}
	protected function dbSetAuxScore($player_id, $count) {
       $this->DbQuery("UPDATE player SET player_score_aux='$count' WHERE player_id='$player_id'");
	}
    protected function dbGetAuxScore($player_id) {
       return $this->getUniqueValueFromDB("SELECT player_score_aux FROM player WHERE player_id='$player_id'");
	}
// db guest
	protected function dbPopulateGuest( $placement, $locale, $locale_arg=0) { // takes placement - deck.php array and puts info from material into guest db 
		$guest_type = $placement[ 'type' ];
		$guest_type_id = $placement[ 'type_arg' ]; // guest type id needed for js stock created with $this->guests deck type_arg creation
		
		$guest = $this->guests[ $guest_type];
		$color = $guest[ "color" ];
		$affinity = $guest[ "affinity" ]; 
		$annex = $guest[ "annex" ]; 
		$ranked = $guest[ "ranked" ]; 
		$pocket = $guest[ "pocket" ];
		$benefit = $guest[ "benefit" ];

		$guest_id = $placement[ "id" ];
		$this->DbQuery("UPDATE guest_deck SET card_location='$locale', card_location_arg='$locale_arg', color='$color', affinity='$affinity', annex='$annex', ranked='$ranked', pocket='$pocket', benefit='$benefit'  WHERE card_id='$guest_id'");

	}
	protected function dbGetAllGuests() {
		return $this->getCollectionFromDB( "SELECT card_id guest_id, card_type guest_type, card_type_arg type_id,  card_location locale, card_location_arg locale_arg, color , affinity , annex , ranked, pocket, benefit  FROM guest_deck WHERE ranked IS NOT NULL" );
	}
	protected function dbGetGuest($guest_id) {
		
		return self::getObjectFromDB("SELECT card_id guest_id, card_type guest_type, card_type_arg type_id,  card_location locale, card_location_arg locale_arg, color , affinity , annex , ranked, pocket, benefit  FROM guest_deck  WHERE card_id='$guest_id'" );
	}
	// counts
	protected function dbGetNumInLocationArg( $locale_arg ) {
		$sql = "SELECT COUNT(card_id) FROM guest_deck WHERE card_location_arg = '". $locale_arg ."'";
		return self::getUniqueValueFromDB($sql);	
	}
	protected function dbGetNumByLocaleAndLocationArg($locale, $locale_arg ) {
		$sql = "SELECT COUNT(card_id) FROM guest_deck WHERE card_location = '" . $locale ."' AND card_location_arg = '" . $locale_arg ."'";
		return self::getUniqueValueFromDB($sql);	
	}
	protected function dbGetNumByLocaleAndLocationArgType($locale, $locale_arg, $type ) {
		$sql = "SELECT COUNT(card_id) FROM guest_deck WHERE card_type = '" .  $type  ."' AND card_location = '" . $locale ."' AND card_location_arg = '" . $locale_arg ."'";
		return self::getUniqueValueFromDB($sql);	
	}
	protected function dbGetNumByLocaleAndLocationArgColor($locale, $locale_arg, $color ) {
		$sql = "SELECT COUNT(card_id) FROM guest_deck WHERE color = '" . $color  ."' AND card_location = '" . $locale ."' AND card_location_arg = '" . $locale_arg ."'";
		return self::getUniqueValueFromDB($sql);	
	}
	protected function dbGetNumByColorAndLocationArg($color, $locale_arg ) {
		$sql = "SELECT COUNT(card_id) FROM guest_deck WHERE color = '" . $color ."' AND card_location_arg = '" . $locale_arg ."'";
		return self::getUniqueValueFromDB($sql);	
	}
	protected function dbGuestNumReserved_burials($guest_id) {
		return $this->getUniqueValueFromDB("SELECT reserved_burials FROM guest_deck WHERE card_id='$guest_id'" );
	}	
	
	// updates
	protected function dbGuestIncrementReserved_burials( $guest_id, $amount=1 ) {
		$reserved = self::dbGuestNumReserved_burials($guest_id) ;
		$reserved += $amount;
		$this->DbQuery("UPDATE guest_deck SET reserved_burials ='$reserved' WHERE card_id='$guest_id'" );
	}
	protected function dbUpdateLocaleGuestByGuestID( $locale, $guest_id, $locale_arg=0 ) {
		$this->DbQuery("UPDATE guest_deck SET card_location ='$locale', card_location_arg='$locale_arg'  WHERE card_id='$guest_id'");
	}
	protected function dbUpdateLocaleGuestByLocale( $new_locale, $old_locale, $locale_arg=0 ) {
		$this->DbQuery("UPDATE guest_deck SET card_location ='$new_locale', card_location_arg='$locale_arg'  WHERE card_location='$old_locale'");
	}


	
	// get set of guests

	protected function dbGetGuestsByLocaleAndLocaleArg($locale, $locale_arg) { // returns all aneexes otherewise empty array so less programming
		$guests  = self::getCollectionFromDB( "SELECT card_id guest_id, card_type guest_type, card_type_arg type_id,  card_location locale, card_location_arg locale_arg, color , affinity , annex , ranked, pocket, benefit  FROM guest_deck  WHERE card_location ='$locale' AND card_location_arg='$locale_arg'" );
		if (is_null($guests )) {
			$guests = array();
		}
		return $guests;
	}
	protected function dbGuestsInLocale($locale) { // returns all aneexes otherewise empty array so less programming
		$guests  = self::getCollectionFromDB( "SELECT card_id guest_id, card_type guest_type, card_type_arg type_id,  card_location locale, card_location_arg locale_arg, color , affinity , annex , ranked, pocket, benefit  FROM guest_deck  WHERE card_location='$locale'" );
		if (is_null($guests  )) {
			$guests  = array();
		}
		return $guests;
	}
// db room - necesaary to keep track of room owner
	protected function dbPopulateRoom( $locale_arg, $owner, $color=null, $guest_id=null, $concierge_id=null ) { // takes placement - deck.php array and puts info from material into guest db 
	
		$sql = "INSERT INTO room ( locale_arg, owner_id, color,  guest_id, concierge_id )
		VALUES( '"  .$locale_arg ."', '" .$owner ."', '" .$color ."', '"  .$guest_id ."', '"  .$concierge_id  ."' )";
	
		self::DbQuery( $sql );
	}
	protected function dbRommsGetAll() {
		return $this->getCollectionFromDB( "SELECT * FROM room" );
	}
	protected function dbRoomByLocale_arg( $locale_arg ) {
		return   self::getObjectFromDB(  "SELECT * FROM room WHERE locale_arg='$locale_arg'" );
	}
	protected function dbGetRoomsByGuest($guest_id=null) { // returns rooms guest_id not residing- if blank returns only rooms with guests
		return self::getCollectionFromDB( "SELECT * FROM room WHERE guest_id!='$guest_id'" );
	}
	protected function dbRoomUpdateGuest_id( $locale_arg, $guest_id=null ) {
		$this->DbQuery("UPDATE room SET guest_id ='$guest_id' WHERE locale_arg='$locale_arg'");
	}
	protected function dbRoomUpdateOwner( $locale_arg, $player_id ) {
		$color = $this->getPlayerColorById($player_id);
		$this->DbQuery("UPDATE room SET owner_id ='$player_id', color='$color' WHERE locale_arg='$locale_arg'");
	}
	protected function dbRoomUpdateConcierge_id( $locale_arg, $concierge_id=null ) {
		$this->DbQuery("UPDATE room SET concierge_id ='$concierge_id' WHERE locale_arg='$locale_arg'");
	}
	protected function dbRoomGetOwner( $locale_arg ) {
		return self::getUniqueValueFromDB(  "SELECT owner_id FROM room WHERE locale_arg='$locale_arg'" );
	}
	protected function dbRoomSetAllRoomsGuest_id( $guest_id=null ) {
		$this->DbQuery("UPDATE room SET guest_id ='$guest_id'");
	}

}