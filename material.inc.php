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
 * material.inc.php
 *
 *
 * Here, you can describe the material of your game with PHP variables.
 *   
 * This file is loaded in your game logic class constructor, ie these variables
 * are available everywhere in your game logic code.
 *
 */


$this->guests = array(
	'cultivator' =>	array( 'color' => 'red', 'affinity' => 'built',
		'annex' => 'vegetable garden', 'ranked'=> 0, 'pocket'=>8,
		'benefit' => clienttranslate('Immediately gain 1F per red annex you have built, including this one')),
	'mechanic' =>	array( 'color' => 'red', 'affinity' => 'built',
		'annex' => 'workshop', 'ranked'=> 1, 'pocket'=> 12,
		'benefit' => clienttranslate('From now on, play 1 fewer accomplice to perform the Build an Annex action')),
	'distiller' =>	array( 'color' => 'red', 'affinity' => 'built',
		'annex' => 'distillery', 'ranked'=> 2, 'pocket'=> 18,
		'benefit' => clienttranslate('At the end of the round, do not pay the wages for one of the accomplices in your hand')),
	'gardener' =>	array( 'color' => 'red', 'affinity' => 'built',
		'annex' => 'gardens', 'ranked'=> 3, 'pocket'=> 26,
		'benefit' => clienttranslate('At the end of eachround, gainan additional 2F during the Travelers Leave phase')),
	'landscaper' =>	array( 'color' => 'red', 'affinity' => 'built',
		'annex' => 'park', 'ranked'=> 3, 'pocket'=> 26,
		'benefit' => clienttranslate('Immediately gain 4F. At the end of the game, gain 4F per red card in the Exit Stack')),
	'butcher' =>	array( 'color' => 'red', 'affinity' => 'built',
		'annex' => 'butcher shop', 'ranked'=> 3, 'pocket'=> 26,
		'benefit' => clienttranslate('From now on, you can kill as many travelers as you want in one Kill a Guest action')),
	'newsboy' =>	array( 'color' => 'blue', 'affinity' => 'bribed',
		'annex' => 'kiosk', 'ranked'=> 0, 'pocket'=> 8,
		'benefit' => clienttranslate('Immediately gain 1F per blue annex you have built, including this one')),
	'representative' =>	array( 'color' => 'blue', 'affinity' => 'bribed',
		'annex' => 'parlor', 'ranked'=> 1, 'pocket'=> 12,
		'benefit' => clienttranslate('From now on, play 1 fewer accomplice to perform the Bribe a Guest action' )),
	'concierge' =>	array( 'color' => 'blue', 'affinity' => 'bribed',
		'annex' => 'room service', 'ranked'=> 2, 'pocket'=> 18,
		'benefit' => clienttranslate('From now on, when a Guest rents this room, immediately gain money equal to his ranked' )),
	'grocer' =>	array( 'color' => 'blue', 'affinity' => 'bribed',
		'annex' => 'grocery', 'ranked'=> 3, 'pocket'=> 26,
		'benefit' => clienttranslate('Immediately gain 4F. At the end of the game, gain 4F per blue card in the Exit Stack' )),
	'shopkeeper' =>	array( 'color' => 'blue', 'affinity' => 'bribed',
		'annex' => 'shop', 'ranked'=> 3, 'pocket'=> 26,
		'benefit' => clienttranslate('From now on, you can bribe as many travelers as you want in one Bribe a Guest action' )),
	'brewer' =>	array( 'color' => 'blue', 'affinity' => 'bribed',
		'annex' => 'brewery', 'ranked'=> 3, 'pocket'=> 26,
		'benefit' => clienttranslate('From now on, you can bribe up to four peasants simultaneously in one Bribe a Guest action' )),
	'baron' =>	array( 'color' => 'green', 'affinity' => 'none',
		'annex' => 'large chandelier', 'ranked'=> 0, 'pocket'=> 8,
		'benefit' => clienttranslate('Immediately gain 4F' )),
	'viscount' =>	array( 'color' => 'green', 'affinity' => 'none',
		'annex' => 'king size bed', 'ranked'=> 1, 'pocket'=> 12,
		'benefit' => clienttranslate('Immediately gain 6F') ),
	'count' =>	array( 'color' => 'green', 'affinity' => 'none',
		'annex' => 'dining room', 'ranked'=> 2, 'pocket'=> 18 ,
		'benefit' => clienttranslate('Immediately gain 9F') ),
	'duke' =>	array( 'color' => 'green', 'affinity' => 'none',
		'annex' => 'stables', 'ranked'=> 3, 'pocket'=> 26 ,
		'benefit' => clienttranslate('Immediately gain 4F. At the end of the game, gain 4F per green card in the Exit Stack')),
	'prince' =>	array( 'color' => 'green', 'affinity' => 'none',
		'annex' => 'greenhouse', 'ranked'=> 3, 'pocket'=> 26,
		'benefit' => clienttranslate('At the end of the game, gain 3F per check you have' )),
	'marquis' =>	array( 'color' => 'green', 'affinity' => 'none',
		'annex' => 'pavilion', 'ranked'=> 3, 'pocket'=> 26 ,
		'benefit' => clienttranslate('Immediately gain 18F' )),
	'peacekeeper' =>	array( 'color' => 'grey', 'affinity' => 'killed',
		'annex' => 'none', 'ranked'=> 0, 'pocket'=> 8,
		'benefit' => clienttranslate('none'  )),
	'brigadier' =>	array( 'color' => 'grey', 'affinity' => 'killed',
		'annex' => 'none', 'ranked'=> 1, 'pocket'=> 12,
		'benefit' => clienttranslate('none'  )),
	'brigadier_chief' =>	array( 'color' => 'grey', 'affinity' => 'killed',
		'annex' => 'none', 'ranked'=> 2, 'pocket'=> 18,
		'benefit' => clienttranslate('none'  )),
	'major' =>	array( 'color' => 'grey', 'affinity' => 'killed',
		'annex' => 'none', 'ranked'=> 3, 'pocket'=> 26 ,
		'benefit' => clienttranslate('none' ) ),
	'novice' =>	array( 'color' => 'purple', 'affinity' => 'burried',
		'annex' => 'altar', 'ranked'=> 0, 'pocket'=> 8 ,
		'benefit' => clienttranslate('Immediately gain 1F per purple annex you have built, including this one') ),
	'monk' =>	array( 'color' => 'purple', 'affinity' => 'burried',
		'annex' => 'bedroom', 'ranked'=> 1, 'pocket'=> 12,
		'benefit' => clienttranslate('Immediately replace one of the white Key tokens with one of your Key tokens') ),
	'abbot' =>	array( 'color' => 'purple', 'affinity' => 'burried',
		'annex' => 'cellar', 'ranked'=> 2, 'pocket'=> 18,
		'benefit'  => clienttranslate('From now on, play 1 fewer accomplice to perform the Bury a Corpse action' )),
	'priest' =>	array( 'color' => 'purple', 'affinity' => 'burried',
		'annex' => 'chapel', 'ranked'=> 3, 'pocket'=> 26,
		'benefit' => clienttranslate('From now on, you are not required to discard accomplices of the other types when you play them to perform a Bury a Corpse action.' )),
	'bishop' =>	array( 'color' => 'purple', 'affinity' => 'burried',
		'annex' => 'bishopric', 'ranked'=> 3, 'pocket'=> 26,
		'benefit' => clienttranslate('Immediately gain 4F. At the end of the game, gain 4F per purple card in the Exit Stack') ),
	'archbishop' =>	array( 'color' => 'purple', 'affinity' => 'burried',
		'annex' => 'crypt', 'ranked'=> 3, 'pocket'=> 26,
		'benefit' => clienttranslate('From now on, you can bury as many corpses as you want in one Bury a Corpse action') ),
	'peasant2' =>	array( 'color' => 'none', 'affinity' => 'none',
		'annex' => 'none', 'ranked'=> 0, 'pocket' => 4,
		'benefit' => clienttranslate('none' )),
	'peasant1' =>	array( 'color' => 'none', 'affinity' => 'none',
		'annex' => 'none', 'ranked'=> 0, 'pocket' => 4,
		'benefit' => clienttranslate('none'  ))
);



