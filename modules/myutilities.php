<?php
/*  Utilities */

trait MyUtilities {
	use BIDatabase;
	
	
	// function copied from Alena Laskavaia <laskava@gmail.com>
	protected function initStats() {
		$all_stats = $this->getStatTypes();
		$player_stats = $all_stats["player"];
		foreach ( $player_stats as $key => $stat ) {
			if ( self::startWith( 'game', $key )) $this->initStat('player', $key, 0);
			if ($key === 'turns_number') $this->initStat('player', $key, 0);
		}
		
		$table_stats = $all_stats["table"];
		foreach ( $table_stats as $key => $stat ) {
			if ( self::startWith( 'table', $key )) $this->initStat('table', $key, 0);
			if ($key === 'turns_number') $this->initStat('table', $key, 0);
		}
	}
	
	// function copied from Alena Laskavaia <laskava@gmail.com>
	protected function startWith( $needle, $haystack ) {
		$offset =  strlen($haystack) - strlen( $needle);
		return $needle === ""|| strrpos( $haystack, $needle, - $offset ) !== false;
	}
	
}