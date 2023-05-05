/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * BI implementation : © <Your name here> <Your email address here>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * bi.js
 *
 * BI user interface script
 * 
 * In this file, you are describing the logic of your user interface, in Javascript language.
 *
 */
"use strict";
define([
    "dojo","dojo/_base/declare",
    "ebg/core/gamegui",
    "ebg/counter",
	"ebg/stock", 
	"ebg/expandablesection", 
	g_gamethemeurl + "modules/room.js",
	g_gamethemeurl + "modules/token.js",	
	g_gamethemeurl + "modules/Input.js",	
	g_gamethemeurl + "modules/guest.js"
],
function (dojo, declare) {
    return declare("bgagame.bi", ebg.core.gamegui, {
        constructor: function(){
            console.log('bi constructor');
			this.cardwidth =  139;
			this.cardheight = 210;
			this.num_players = 0;
			this.rooms = []; // array key is room  eg bottom_card1 for player room or top_card1 for neutral rooms
			this.travellers = []; // stores new traveller Id as key to guest instances
			this.tokens = []; // player tokens
			this.info_sent = null; // information that will be sent to php
			this.player_stock_selected = []; // hand cards selected or number peasants selected from bistro
			this.selected_burial = null;
			this.selecting_burial = false;
			this.free_burials = [];
			this.checks_to_add = 0;
			this.game_state ;
			this.brewer_annex_player = null;
			this.action ; //for free action so know when to add burual site to this.info_sent
			this.discount = 0; //total accmplice discount selected
        },
        
        
        setup: function( gamedatas ) 		{
console.log( " in setup: function gamedatas is " );
			this.game_state = gamedatas.game_state;
console.log("gamedatas is");
console.log(gamedatas);
			this.createCardStats(gamedatas.cards_in_game, gamedatas.travellers_in_deck, gamedatas.deck_num );
            this.addTooltip( 'exit_stack', _('Click to see exit stack'), '' );
			dojo.connect( $( 'exit_stack' ), 'onclick', this, 'onExit_stack' );
			var discard_display = document.querySelector("#discard");
			discard_display.addEventListener("click", this.onExit_stack);
			dojo.connect( $('passform'), 'onchange', this, 'onUpdateFrancDisplay' );
			dojo.connect( $('btnsubmit'), 'onclick', this, 'onConfirmLaunder' );
			let filter = new FormKeyFilter();;
			document.getElementById("quantity").addEventListener("keydown", filter);
			
            this.setupPlayerPanelandToken(gamedatas.players, gamedatas.player_kills);
			this.setupRooms( gamedatas.rooms, gamedatas.players);
			this.setupBistro(  gamedatas.bistro_numbers) ;
			this.setupPlayerHand();
			this.setupBonusVictims();
			//next 2 steps for displaying guests
			this.setupAmnexes(this.gamedatas.display ); //must built annexes first
	
			// place rest of guests - all non annex
			this.setupGuestsDisplay(this.gamedatas.display );

			this.reactivateActionSelect();
			
			if (this.isCurrentPlayerActive()) {
				if ( (gamedatas.game_state == 'freeBribed' ) || (gamedatas.game_state == 'freeKilled' ) || (gamedatas.game_state == 'freeBurried' ) ) 	{
					const action = gamedatas.game_state.replace('free','');
					this.prepareFreeActions( gamedatas.free_action_first_victim, action.toLowerCase() );
				}
			}
			this.setupThisAction( gamedatas.player_action);
            this.setupNotifications();
        },
		//////////////////////////////////////////
		///// setup functions

		setupPlayerPanelandToken: function( players, player_kills) {
			for( var player_id in players ){
				this.num_players++;
				var player =players[player_id];
						
				//Setting up players panel icons				
				const player_board_div = $('player_board_'+player_id);
				dojo.place( this.format_block('jstpl_player_board', player ), player_board_div );
				
				//setting up franc tokens updating players panel icons values
				let player_id_str = "p" + player_id.toString();
				this.tokens[ player_id_str ] = new Token( player_id, player.player_color ,player.checks );
				this.setToken( player_id, player.franc, player.checks );
				this.setCadaverInnerhtml( player_id , player_kills[ player_id ]);
				this.addTooltip( "cadaver_panel_p" + player_id, _('Number of corpse to bury'), '' );
				const player_aid_id = "annex_" + player_id ;
				dojo.connect(  $(player_aid_id), 'onclick', this, 'onBurialSite' );
				this.addTooltipToPlayerAnnex(player_aid_id );
			}
		},
		addTooltipToPlayerAnnex: function(player_aid_id ) {
			const header = _("This player aid is a rank-1 annex, you can bury a corpse under it");
			const phase1 = _("Phase 1 - Welcome Travlers");
			const phase2 = _("Phase 2 - Player Action - 2 actions rounds");
			const phase2L1 = _("BRIBE A GUEST: Take 1 traveler or 2 peasants from the inn into your hand");
			const phase2L2 = _("BUILD AN ANNEX: Place an accomplice from your hand on the table -living side");
			const phase2L3 = _("KILL A GUEST: Place a guest from the inn on the table -dead side");
			const phase2L4 = _("BURY A CORPSE: Tuck a corpse under an annex");	
			const phase2L5 = _("PASS: Do nothing or launder money");	
			const phase3 = _("Phase 3 – End of Round");	
			const phase3L1 = _("Police Investigation");
			const phase3L2 = _("Travelers Leave");	
			const phase3L3 = _("Pay Wages");
			const html = `
				<Tip>
					<h3>${ header}</h3><hr>
					<ul>
						<li>${phase1} </li>
						<li>${phase2}
							<ul>
								<li> &nbsp;&nbsp;&nbsp; ${phase2L1} </li>
								<li> &nbsp;&nbsp;&nbsp; ${phase2L2} </li>
								<li> &nbsp;&nbsp;&nbsp; ${phase2L3} </li>
								<li> &nbsp;&nbsp;&nbsp; ${phase2L4} </li>
								<li> &nbsp;&nbsp;&nbsp; ${phase2L5} </li>
							</ul>
						</li>
						<li>${phase3}
							<ul>
								<li> &nbsp;&nbsp;&nbsp; ${phase3L1} </li>
								<li> &nbsp;&nbsp;&nbsp; ${phase3L2} </li>
								<li> &nbsp;&nbsp;&nbsp; ${phase3L3} </li>
							</ul>
						</li>
					</ul> 	
				</Tip>
			`;
			this.addTooltipHtml( player_aid_id, html );
		},
		setupRooms: function( rooms, players) {
			for( var room_id in rooms ) {
				let room = rooms[room_id];
				this.rooms[room_id] = new Room( room_id, room.color, room.owner_id, room.guest_id );
				this.rooms[room_id].createElement();
				if (( this.game_state != "playerTurn" ) && (this.isCurrentPlayerActive())) this.rooms[room_id].activateSelect();
				if ( room.concierge_id !== "" ) {
					this.rooms[room_id].placeServiceToken( players[room.concierge_id].player_color );
				}
				dojo.connect( $( room_id ), 'onclick', this, 'onRoomSelect' );
			}
		},
		setupBistro: function(  bistro_numbers) {
			Guest.num_peasants_in_bistro = bistro_numbers;
			if ( Guest.num_peasants_in_bistro > 0 ) {
				const bistro_node =  document.querySelector("#bistro");
				if ( this.game_state == "playerTurn") bistro_node.classList.add("select");	
			}
			dojo.connect( $( 'bistro' ), 'onclick', this, 'onBistro' );
		},
		setupPlayerHand: function() {
			this.playerhand = new ebg.stock();
			this.playerhand.create( this, $( 'myhand' ), this.cardwidth, this.cardheight );
			this.playerhand.image_items_per_row = 28;
			for ( let card=0; card<=30; card++ ) {
				this.playerhand.addItemType( card, card, g_gamethemeurl + 'img/card.png', card );
			}      
			dojo.connect( this.playerhand, 'onChangeSelection', this, 'onPlayerHandSelectionChanged' );	
			this.addTooltip( "myhand", _('My Hand of my accomplices '), '' );
		},
		setupBonusVictims: function() {
			this.free_victims = new ebg.stock();
			this.free_victims.create( this, $( 'bonus_victims' ), this.cardwidth, this.cardheight );
			this.free_victims.image_items_per_row = 28;
			for ( let card=0; card<=30; card++ ) {
				this.free_victims.addItemType( card, card, g_gamethemeurl + 'img/card.png', card );
			}  
		},
		setupAmnexes: function( display ) {
			for ( let annex in display) {
				let traveler = display[annex];
				if ( traveler.locale == 'annex' ) {
					this.travellers[ traveler.guest_id.toString() ] = new Guest( traveler );
					this.placeGuest( annex, traveler.locale, traveler.locale_arg,traveler.type_id , traveler ); 
				}
			}
		},
		setupGuestsDisplay: function(display ) {
			for ( let j in display) {
				let traveler = display[j];
				if ( traveler.locale != 'annex' ) {
					this.travellers[ traveler.guest_id.toString() ] = new Guest( traveler );
					this.placeGuest( j, traveler.locale, traveler.locale_arg,traveler.type_id , traveler ); 
				}
			}
		},
		setupThisAction: function ( player_action) {
			switch (parseInt(player_action)) {
				case  1 :
					this.action = 'bribed'; 
					break;
				case  2 : 
					this.action = 'killed'; 
					break;
				case  3 :
					this.action =  'built' ;
					break;
				case  4 : 
					this.action =  'burried'; 
					break;
				default:
					this.action = null; 
			}
		},
        ///////////////////////////////////////////////////
        //// Game & client states
        
        onEnteringState: function( stateName, args )      {
            console.log( 'Entering state: '+stateName );

            this.game_state = stateName;
            switch( stateName ) {
				case 'assignGuestRoom':
					this.free_victims.removeAll();
					this.hideBonus_Victims();
					this.info_sent = null;
					Guest.last_test_location = null;
					const bistro_node =  document.querySelector("#bistro");
					bistro_node.classList.remove("select");
                    break;
				case 'playerTurn':
					this.free_victims.removeAll();
					this.hideBonus_Victims();
					const entrance_node = document.querySelector("#entrance_stack" );	
					entrance_node.setAttribute("class", "card_back card" ); 
					this.onUnDo();
					break;
				case 'endRound':
					for( let room in this.rooms ) { 
						this.rooms[room].vacateRoom();
						this.rooms[room].activateSelect();
						this.removeTooltip( room );
					}
				case 'freeBistro':
					this.onBistro();
					break;
				case 'payAccomplice':
					this.info_sent = null;
					break; 
				case 'selectAccomplice':	
					if (this.isCurrentPlayerActive() )  {
						for ( var traveller in this.travellers ) {
							if ( this.travellers[ traveller].locale ==  "annex" && this.travellers[ traveller].locale_arg == this.getActivePlayerId() )  {		
								const option_id = this.travellers[  traveller ].createBenefitOption(  this.action );
								dojo.connect( $( option_id ), 'onclick', this, 'onNo' );
							}
						}
					}
					break;
				default:
			}
        },

        onLeavingState: function( stateName )      {
            console.log( 'Leaving state: '+stateName );
            switch( stateName ) {
				case 'playerTurn':
					this.removeAllSelect();
					this.removeAnnexSelect()
					break;
   
				  default:
			}
        }, 

        onUpdateActionButtons: function( stateName, args )      {
console.log("in onUpdateActionButtons state is " + stateName);                     
            if( this.isCurrentPlayerActive() )
            {   
                switch( stateName )  {
					case 'assignGuestRoom':
						this.addActionButton( 'room_confirm_btn', _('Confirm Room Assignment'), 'onConfirmAssignRoom' );
						break;
					case 'playerTurn':
						this.addActionButton( 'undo_btn', _('start over'), 'onUnDo');
						dojo.style( 'undo_btn', 'background-color', 'coral' );
						this.addActionButton( 'bribed_btn', _('bribe'), 'onPerformAction' ); 
						this.addActionButton( 'killed_btn', _('kill'), 'onPerformAction' ); 
						this.addActionButton( 'built_btn', _('built Annex'), 'onPerformAction' ); 
						this.addActionButton( 'burried_btn', _('bury'), 'onBurial' ); 
						this.addActionButton( 'pass_btn', _('pass'), 'onPass' ); 
						break
					case 'selectAccomplice':
						this.addActionButton( 'undo_btn', _('start over'), 'onStartOver');
						dojo.style( 'undo_btn', 'background-color', 'coral' );
						this.addActionButton( 'accomplice_btn', _('comfirm accomplice'), 'onConfirmAccomplice' ); 
						break
					case 'freeBribed':
						this.addActionButton( 'undo_btn', _('start over'), 'onStartOver');
						dojo.style( 'undo_btn', 'background-color', 'coral' );
						this.addActionButton( 'bribed_btn', _('bribe'), 'onNext' ); 
						this.addActionButton( 'done_btn', _('done bribing'), 'onDone' ); 
						break;
					case 'freeKilled':
						this.addActionButton( 'undo_btn', _('start over'), 'onStartOver');
						dojo.style( 'undo_btn', 'background-color', 'coral' );
						this.addActionButton( 'killed_btn', _('kill'),  'onNext' ); 
						this.addActionButton( 'done_btn', _('done killing'), 'onDone' ); 
						break;
					case 'freeBurried':
						this.addActionButton( 'undo_btn', _('start over'), 'onStartOver');
						dojo.style( 'undo_btn', 'background-color', 'coral' );
						this.addActionButton( 'burried_btn', _('bury'), 'onBurial' ); 
						this.addActionButton( 'done_btn', _('done burying'), 'onDone' ); 
						break;
					case 'payAccomplice':
						this.addActionButton( 'remove_btn', _('Confirm'), 'onConfirmSelection' );
						break;
					case 'selectRoom':
						this.addActionButton( 'confirmRoom_btn', _('Confirm Room'), 'onConfirmSelection' );
						break;				
                }
            }
        },		

        ///////////////////////////////////////////////////
        //// Utility methods

        placeGuest: function( guest_id, locale, locale_arg,type_id, traveler_info  )   {
			switch(locale)  {
				case "hand":
					if ( locale_arg == this.player_id ) {
							this.playerhand.addToStockWithId( type_id, guest_id  );
					}
					break;
				case "room":
					let html1 = this.travellers[guest_id ].place( locale_arg );
					if ( this.game_state == "playerTurn" ) {
						this.rooms[ locale_arg ].activateSelect();
					} else if ( this.game_state ==  "assignGuestRoom" ) {
						this.rooms[ locale_arg ].deActivateSelect();
					}
					this.addTooltipHtml( locale_arg, html1, 0 );
					this.rooms[locale_arg  ].newGuest(guest_id);
					break;
				case "entrance_stack":
					Guest.new_guest = guest_id;
					let html2 = this.travellers[guest_id ].place( locale_arg, locale);
					this.addTooltipHtml( locale_arg, html2, 0 );
					break;
				case "annex": 
					if ( traveler_info["guest_type"] == 'brewer' ) 		this.brewer_annex_player = locale_arg;
					const annex_info = this.travellers[guest_id].builtAnnex(locale, locale_arg);
					this.addTooltipHtml( "annex_" + guest_id, annex_info.tooltip, 0 );
					dojo.connect( $( "annex_" + guest_id ), 'onclick', this, 'onBurialSite' );
					break;
				case "discard":
					this.handleSpentCards(traveler_info );
					break;
				case "killed": 
					let corpse_id = this.travellers[guest_id].killStash( locale, locale_arg );
					dojo.connect( $( corpse_id ), 'onclick', this, 'onCorpse' );
					break;
				case "burried":
					this.travellers[guest_id].bury(locale, locale_arg);
					break;
			}
		},
		
		setToken: function( player, francs, checks ) {
			const player_id =  player.toString();
			this.tokens["p" + player].remove();
			this.tokens["p" + player].place(francs);
			//update franc in player_panel
			let span_id = "franc_p" + player_id;
			$( span_id ).innerHTML = francs ;
			const check_span_id = "check_p" + player_id;
			$( check_span_id ).innerHTML = checks ;
			const player_key = "p" + player_id;
			this.tokens[player_key].updateChecks( checks);	
		},
		setCadaverInnerhtml: function( player_id, value ) {
			let span_id = "cadaver_panel_p" + player_id;
			$( span_id ).innerHTML = value;	
		},
		createCardStats: function( cards_in_game, travellers_in_deck, deck_num ) {
			const guests_arrived = cards_in_game - travellers_in_deck;
			const html = `
				Guests: ${guests_arrived} / ${cards_in_game}   <br>        Season: ${++deck_num} of 2 
			`;
			document.getElementById("card_stats").innerHTML = html;
		},
		removeAllSelect: function() {
			const selectable_elements = document.querySelectorAll(".select");
			selectable_elements.forEach( element => element.classList.remove( "select" ) );
		},
		reactivateActionSelect: function() { //for active player only activates only vacant rooms, corpses and bistro
			if ( this.isCurrentPlayerActive() ) {
				const player_killed = document.getElementById("killed_" + this.player_id );

				let killings = player_killed.querySelectorAll(".card");
				for ( var kill of killings )  { 
					kill.classList.add( "select" );
				}	

				for( var room_id in this.rooms ){
					if (!this.rooms[ room_id].isVacant() ) 	this.rooms[ room_id].activateSelect(); 
 				}
				
				if ( Guest.num_peasants_in_bistro > 0 ) {
					const bistro_node =  document.querySelector("#bistro");
					if ( this.game_state == "playerTurn") bistro_node.classList.add("select");	
				}
			}
		},
		addAnnexSelect: function() {
			let annexed_elements = document.querySelectorAll(".annexed");
			annexed_elements.forEach( ( element )=>{
				element.classList.add( "select" );
				element.classList.add( "pulsate" );
			});
		},
		removeAnnexSelect: function() {
			let annexed_elements = document.querySelectorAll(".annexed");
			annexed_elements.forEach( ( element )=>{
				element.classList.remove( "select" );
				element.classList.remove( "pulsate" );
			});
		},
		emptyElement: function( element_id ) {
			this.removeTooltip( element_id  );
			const parent_node =  document.querySelector("#" + element_id );
			while (parent_node.hasChildNodes()) {
				parent_node.removeChild(parent_node.firstChild);
			}

		},
		handleSpentCards: function(traveller_info) {
			const traveller_id = traveller_info.guest_id.toString();
			this.travellers[traveller_id].spendGuest( traveller_info);
			if ((traveller_info.guest_type == "peasant1") || (traveller_info.guest_type == "peasant2")) 
				this.createBistroTooltip();
			else 
				this.travellers[traveller_id].joinDiscard();
		},
		createBistroTooltip: function() {
			this.removeTooltip( "bistro");
			const bistro_html = Guest.createBistroHTMLToolTip();
			this.addTooltipHtml( "bistro", bistro_html, 0 );
		},
		sendInfo: function( ) {
			if ( this.info_sent ) {
				this.ajaxcall( "/bi/bi/playerAction.html", { 
					id: this.info_sent,
					lock: true 
				}, this, function( result ) {  }, function( is_error) { } ); 
			}			
		},

		packageCheckPlayer_stock_selected: function() {
			this.player_stock_selected.forEach(	element => {
				if ( element.id == Guest.selected_for_action ) {
					this.playerhand.unselectItem( Guest.selected_for_action );
				} else {
					this.info_sent.push(element.id);
				}
			});
		},
		packageActionInfo: function( action_id ) {
			this.info_sent = [];
			this.action = action_id;
			this.info_sent.push(action_id ); 	
			this.info_sent.push(Guest.selected_for_action ); 	
			if ( action_id == "burried" ) {
				this.info_sent.push(this.selected_burial);
			}
			this.info_sent.push(this.getActivePlayerId());
			const package_info = this.info_sent.join(" ");
			this.info_sent = package_info;
		},

		removeElement: function( element_id ) {
			let location_node = document.querySelector('#' + element_id );
			if (typeof( (location_node) != 'undefined' ) && (location_node != null)) {
				location_node.remove();
			}
		},
		
		elementExists: function( element_id ) {
			let location_node = document.querySelector('#' + element_id );
			if (typeof( (location_node) != 'undefined' ) && (location_node != null)) {
				return true;
			}
		},

		displayRoomGuest: function( room ) {
			this.removeTooltip( "top"  );
			this.info_sent = null;
			Guest.selected_for_action = this.rooms[ room ].getGuest();

			const tooltip_html = this.travellers[ Guest.selected_for_action.toString() ].contactGuest( "top" );
			this.addTooltipHtml( "top" , tooltip_html, 0 );
		},
		prepareFreeActions: function( guest_info, action ) { 
			if ( action != "burried"  ) {  // action needed as next state when notify completes
				const guest_location_arg = this.travellers[guest_info].getLocation_arg() ;
				this.rooms[guest_location_arg].showRoomSelected();
				const guest_type_id = this.travellers[ guest_info ].getTypeID();
				this.free_victims.addToStockWithId( guest_type_id, guest_info );
				this.showBonus_Victims();
			} else	if ( action == "burried" ) { 
				this.free_burials = [];
				const free_action_array = guest_info.split(" ");
				const guest_id =  free_action_array.shift();
				this.free_burials[ guest_id ] =  free_action_array.shift();
				this.travellers[guest_id].showCorpseSelected();
			}
			this.clearVariblesForNextFreeAction();
		},
		hideBonus_Victims: function() {
			const bonus_victims_node = document.getElementById("bonus");
			bonus_victims_node.classList.add("hide");
			bonus_victims_node.classList.remove("display_flex_column");
		},
		showBonus_Victims: function() {
			const bonus_victims_node = document.getElementById("bonus");
			bonus_victims_node.classList.remove("hide");
			bonus_victims_node.classList.add("display_flex_column");
		},
		clearVariblesForNextFreeAction(action_button){
			dojo.query( '#' +action_button ).removeClass( 'disabled' );
			Guest.selected_for_action = null;
			Room.last_selected_room = null;
			this.emptyElement( "top" );
		},	
        ///////////////////////////////////////////////////
        //// Player's action
        
		onExit_stack: function(evt) {
			const exit_stack = document.querySelector("#discard");
			exit_stack.classList.toggle("hide");
		},
        onUpdateFrancDisplay: function(evt) {

			let val = null;
			const player_key = "p" + this.getActivePlayerId();

			let num_francs = this.tokens[ player_key  ].francs;
			let num_checks = this.tokens[ player_key  ].checks;
			const max_checks_added = num_francs / 10 ;
			const check_quantity_node = document.getElementById("quantity");

			if (!isNaN( val = parseInt(check_quantity_node.value))) {

				if ( val < -(num_checks) ) {
					check_quantity_node.value = -(num_checks) ;
					val = -num_checks;
				}
				const max_checks_added =  Math.floor( num_francs / 10 );
				if ( val > max_checks_added ) {
					check_quantity_node.value = max_checks_added ;
					val = max_checks_added;
				}
				var new_num_francs = parseInt(num_francs) - val * 10;
				
			}
			document.getElementById('franc_amount').value = (new_num_francs > 40) ? 40: new_num_francs ;	
			document.getElementById('checks_amount').value = parseInt( num_checks ) + val;	
			this.checks_to_add = val;
	
		},
		onConfirmLaunder: function(evt) {
			const pass_form = document.querySelector("#passform");
			pass_form.classList.add("hide");
			this.info_sent = [];
			this.info_sent.push("pass"); 	
			this.info_sent.push(this.checks_to_add ); 	
			this.info_sent.push(this.getActivePlayerId());
			const package_info = this.info_sent.join(" ");
			this.info_sent = package_info;
			this.ajaxcall( "/bi/bi/passConfirm.html", { 
						id: this.info_sent,
						lock: true 
					}, this, function( result ) {  }, function( is_error) { } ); 
		},
		onConfirmAssignRoom: function(evt) {
			if ( this.info_sent != null ) {
				if( this.checkAction( "admitGuest" ) ) {
					this.ajaxcall( "/bi/bi/admitGuest.html", { 
						id: this.info_sent,
						lock: true 
					}, this, function( result ) {  }, function( is_error) { } ); 
				}
			}
		},
		onConfirmSelection: function( evt ) {
			let action_arr = evt.target.id.split("_");

			if( this.checkAction( action_arr[0] ) ) {
				this.info_sent = [];
				this.info_sent.push( action_arr[0] );
				switch(action_arr[0]) {
					case 'remove':
						this.player_stock_selected = this.playerhand.getSelectedItems();
						this.player_stock_selected.forEach(	element => {
							this.info_sent.push(element.id);
						});
						break;
					case 'confirmRoom':
						this.rooms[ Room.last_selected_room ].removeRoomSelected();
						this.info_sent.push(Room.last_selected_room);
						break;
				}
				this.info_sent.push(this.getActivePlayerId());
				const package_info = this.info_sent.join(" ");
				this.info_sent = package_info;

				this.ajaxcall( "/bi/bi/confirmSelection.html", { 
					id: this.info_sent,
					lock: true 
				}, this, function( result ) {  }, function( is_error) { } ); 
			}
			
		},	
		onConfirmAccomplice: function( evt ) {
			this.info_sent = [];
			this.info_sent.push( "accomplice" );
			this.info_sent.push(this.getActivePlayerId());
			this.info_sent.push( this.discount );
			this.packageCheckPlayer_stock_selected();
			this.info_sent = this.info_sent.join(" ");
			this.sendInfoGetAccomplicesState();
		},
		onStartOver: function(evt ) {
			if ( this.checkAction( "startOver" )  ) {
console.log("in startover function");
				this.info_sent = [];
				this.info_sent.push( "SO"  );
				Room.removeAllRoomSelected();
				this.free_victims.removeAll();
				for ( let i in this.free_burials ) 	  this.travellers[ i ].showCorpseNotSelected() ;
				this.sendInfoGetAccomplicesState();
			}
		},
		sendInfoGetAccomplicesState: function() {
console.log("in sendInfoGetAccomplicesState function");
			this.ajaxcall( "/bi/bi/getAccomplices.html", { 
				id: this.info_sent,
				lock: true 
			}, this, function( result ) {  }, function( is_error) { } ); 	
		},
		
		onBurial: function(evt) {
			const action_arr = evt.target.id.split("_");
			if ( Guest.selected_for_action ) {
				if ( this.checkAction( action_arr[0] ) ) {
					this.selected_burial == null;
					this.removeAllSelect();
					this.addAnnexSelect();
					dojo.addClass( 'burried_btn', 'disabled');//disable the button
					this.showMessage(_(" you must select an available burial site "), "info" );
					this.selecting_burial = true;
				}	
			}
						
		},
		
		onPerformAction: function(evt) {
			const action_arr = evt.target.id.split("_");
			if ( Guest.selected_for_action ) {
				if( this.checkAction( action_arr[0] ) ) {
					const sending = this.packageActionInfo( action_arr[0] );
					this.sendInfo();
				}
			}
		},
		
		onBribePeasants: function(evt) { // basically bribe  - tailored for peasnts bribe
			const evt_id = evt.target.id;
			const action_str = evt_id.slice( 0, evt_id.length - 4 );
		
			if ( Guest.selected_for_action ) {
				if( this.checkAction( "bribed" ) ) {
					const sending = this.packageActionInfo( action_str );
					this.sendInfo();
				}
			}			
		},		
		onPass: function(evt) {
			if( this.checkAction( "pass" )  && this.isCurrentPlayerActive() ) {
				this.onUnDo();
				const pass_form = document.querySelector("#passform");
				pass_form.classList.remove("hide");
				document.getElementById("quantity").value = 0;

				const player_key = "p" + this.player_id ;
				let num_francs = this.tokens[ player_key  ].francs;
				let num_checks = this.tokens[ player_key  ].checks;
				
				document.getElementById('franc_amount').value = "" ;	
				document.getElementById('checks_amount').value = "";	
			}
		},
		
		onUnDo: function(evt) {
			Guest.selected_for_action = null;
			Room.last_selected_room = null;
			this.checks_to_add = 0;
			this.discount = 0;
			this.info_sent = null;
			this.selecting_burial = false;
			this.emptyElement( "top" );
			this.playerhand.unselectAll();
			this.player_stock_selected = [];
			this.reactivateActionSelect();
			const pass_form = document.querySelector("#passform");
			pass_form.classList.add("hide");
			document.querySelectorAll('.option_box').forEach(e => e.remove());
			if ( this.game_state == 'playerTurn' ) 		this.hideBonus_Victims();
			if ( this.isCurrentPlayerActive() ) {
				dojo.query( '#built_btn' ).removeClass( 'disabled' );
				dojo.query( '#bribed_btn' ).removeClass( 'disabled' );
				dojo.query( '#burried_btn' ).removeClass( 'disabled' );
				dojo.query( '#killed_btn' ).removeClass( 'disabled' );
				this.removeElement('bribed_1_btn' );
				this.removeElement('bribed_2_btn' );
				this.removeElement('bribed_3_btn' );
				this.removeElement('bribed_4_btn' );
			}
		}, 
		
		onBistro: function() {
			if ( this.game_state == "playerTurn" ) {
				if ( this.isCurrentPlayerActive()  ) { 
					if ( Guest.num_peasants_in_bistro > 0 ) {
						if ( this.game_state == "playerTurn"  ) {
							dojo.addClass( 'built_btn', 'disabled');
							dojo.addClass( 'burried_btn', 'disabled');
							dojo.addClass( 'bribed_btn', 'disabled');
							const player = this.getActivePlayerId() ;
							const player_bribing_max = ( player == this.brewer_annex_player) ? 4 : 2;
							const max_peasant_bribed = ( Guest.num_peasants_in_bistro > player_bribing_max ) ? player_bribing_max : Guest.num_peasants_in_bistro ;				
							for ( var peasant_num=1; peasant_num <= max_peasant_bribed ; peasant_num++ ) { 
							
								const button_id = "bribed_" +peasant_num + "_btn";
								const button_label = "bribe " + +peasant_num + " peasant";
								if ( !this.elementExists( button_id) ) this.addActionButton(  button_id, _( button_label ), 'onBribePeasants'); 
							}
						}
						Guest.selected_for_action = "peasant";
						Guest.contactPeasant( "top" );
						const tooltip_html = Guest.createPeasantHTMLToolTip();
						this.addTooltipHtml( "top" , tooltip_html, 0 );
					}
				}
			}
		},

		onPlayerHandSelectionChanged: function(evt) {
			const temp = this.playerhand.getSelectedItems(); // hold selected hand items so can start afresh
			this.player_stock_selected = temp;
			if ( this.player_stock_selected.length > 0 ) { 
				switch( this.game_state) {
					case "playerTurn":
						if (this.isCurrentPlayerActive() ) {
							if ( Guest.selected_for_action == null )  {
								this.onUnDo(); // remove hand selection
								Guest.selected_for_action = temp[0].id;
								const tooltip_html = this.travellers[ Guest.selected_for_action.toString() ].contactGuest( "top" );
								this.addTooltipHtml( "top" , tooltip_html, 0 );
								dojo.addClass( 'killed_btn', 'disabled');
								dojo.addClass( 'bribed_btn', 'disabled');
								dojo.addClass( 'burried_btn', 'disabled');
								dojo.query( '#built_btn' ).removeClass( 'disabled' );
							} 
						}
						break;
					case "payAccomplice":
						this.player_stock_selected = this.playerhand.getSelectedItems();

						break;
					default:

				}
			}
		},
		onCorpse: function(evt) {
			if (( this.game_state == "playerTurn" ) || ( this.game_state == "freeBurried" ) || ( this.isCurrentPlayerActive() )) {
				let corpse_info = evt.target.id.split("_");
				if (this.travellers[ corpse_info[1] ].localeAndLocale_argConfirm( "killed", this.player_id )) { // ensure player chooses only own corpses
					if (this.isCurrentPlayerActive() ) {
						if ( this.game_state == "playerTurn" ) {
							dojo.addClass( 'killed_btn', 'disabled');
							dojo.addClass( 'bribed_btn', 'disabled');
							dojo.addClass( 'built_btn', 'disabled');
						}
						dojo.query( '#burried_btn' ).removeClass( 'disabled' );
						const tooltip_html = this.travellers[ corpse_info[1] ].contactGuest();
						this.addTooltipHtml( "top" , tooltip_html, 0 );
						Guest.selected_for_action = corpse_info[1];
					}
				}
			}
		},
		onBurialSite: function(evt) {
			if (this.isCurrentPlayerActive()  && this.selecting_burial ){
				const sibling_node = document.querySelector( "#" + evt.target.id);
				const parent_node = sibling_node.parentNode.id;
				this.selected_burial = parent_node;
				this.reactivateActionSelect();
				this.removeAnnexSelect();			
				if ( this.game_state == "playerTurn" )    {
					this.selecting_burial = false;
					const sending = this.packageActionInfo( 'burried' );
					this.sendInfo();
				} else if ( this.game_state == "freeBurried" ) {
					this.ajaxcall( "/bi/bi/checkBurialSite.html", { 
						id: this.selected_burial,
						lock: true 
					}, this, function( result ) {  }, function( is_error) { } ); 
				}
			}
		},
		
		onRoomSelect: function(evt) {
			if (this.isCurrentPlayerActive() ) {
				evt.preventDefault();
				dojo.stopEvent(evt);
				evt.stopPropagation();
				let room = evt.target.id;
				//  if target id is service token change to room
				const current_node = document.getElementById( room );
				if  ( current_node.classList.contains("serviceToken") ) 	room = 	current_node.parentNode.id;
				const active_player = this.getActivePlayerId();
				if ( ! current_node.classList.contains("overlay")	) { 
					switch( this.game_state) {

						case "assignGuestRoom": // remove guest from last test room, put in new room 
							this.info_sent =  this.rooms[room].owner + " " + Guest.new_guest + " " + room;		

							if ( Guest.last_test_location ) {
								this.travellers[ Guest.new_guest.toString() ].removeImageFromRoom( Guest.last_test_location );
							}

							Guest.last_test_location = room;
							if ( this.rooms[ room ].isVacant() ) { 
								let html = this.travellers[ Guest.new_guest.toString() ].roomTest( room );
							} else {
								this.info_sent = null;
							}
							break;
						case "playerTurn":
							this.onUnDo();
							
							if ( this.isCurrentPlayerActive() ) { 
								if ( !this.rooms[ room ].isVacant() ){
									dojo.addClass( 'built_btn', 'disabled');
									dojo.addClass( 'burried_btn', 'disabled');
									dojo.query( '#bribed_btn' ).removeClass( 'disabled' );
									dojo.query( '#killed_btn' ).removeClass( 'disabled' );
								
									this.displayRoomGuest( room );
								}
							}
							break;
						case "freeBribed":
						case "freeKilled":
							this.onUnDo();
							if ( this.isCurrentPlayerActive() ) { 
								if ( !this.rooms[ room ].isVacant() ){
									this.displayRoomGuest( room );	
									Room.last_selected_room = room;
								}
							}
							break;
						case "selectRoom":
							Room.removeAllRoomSelected();
							Room.last_selected_room = room;
							this.rooms[room].showRoomSelected();
							break;
						default:
					} 
				}
			}
		},
		
		onNext: function( evt ) {
			const action_arr = evt.target.id.split("_");
			if ( Guest.selected_for_action ) {
				if( this.checkAction( action_arr[0] ) ) {
					this.free_victims.addToStockWithId(this.travellers[ Guest.selected_for_action.toString() ].getTypeID(), Guest.selected_for_action );
					this.rooms[Room.last_selected_room].showRoomSelected();
					this.clearVariblesForNextFreeAction( evt.target.id );
				}	
			}
		},
		
		onNo: function( evt ) {
			var target_id_arr = evt.target.id.split( "_");
			if ( target_id_arr[0] == 'no' && !isNaN( target_id_arr[1] ) ) { 
				// if element exists, removeBenefitOption() returns 1 - counter onclick repeats
				const discount_rejection = this.travellers[  target_id_arr[1] ].removeBenefitOption(); 
				if (  discount_rejection == 1 )  this.discount++;
			}
		},
		onDone: function(evt) {
			if( this.checkAction( "done"  )  && this.isCurrentPlayerActive() ) {
				this.info_sent = [];
				if ( this.game_state != "freeBurried" ) {
					const guests_selected = this.free_victims.getAllItems();
					Room.removeAllRoomSelected();
					for (let i = 0; i < guests_selected.length; ++i){
						this.info_sent.push( guests_selected[i].id );
						if ( this.action == 'burried' ) 	this.info_sent.push( this.free_burials[ guests_selected[i].id] );
					}
				} else if ( this.game_state == "freeBurried" ) {
					for ( let guest_id in this.free_burials ) 	{
						this.info_sent.push( guest_id );
						this.info_sent.push( this.free_burials[guest_id] );
					}
				}
				const package_info = this.info_sent.join(" ");
				this.info_sent = package_info;
				this.ajaxcall( "/bi/bi/doneConfirm.html", { 
					id: this.info_sent,
					lock: true 
				}, this, function( result ) {  }, function( is_error) { } ); 
			}
		},

        ///////////////////////////////////////////////////
        //// Reaction to cometD notifications


        setupNotifications: function()    {
            console.log( 'notifications subscriptions setup' );
			dojo.subscribe( 'admission', this, "notif_admission" );
			dojo.subscribe( 'assignGuest', this, "notif_assignGuest" );
			dojo.subscribe( 'log_action', this, "notif_log_action" );
			dojo.subscribe( 'action', this, "notif_action" );
			dojo.subscribe( 'playerAct', this, "notif_playerAct" );
			dojo.subscribe( 'free_action', this, "notif_free_action" );
			dojo.subscribe( 'burial_good', this, "notif_burial_good" );
			dojo.subscribe( 'payments', this, "notif_payments" );
			dojo.subscribe( 'guest_location', this, "notif_guest_location" );
			dojo.subscribe( 'playerFrancAct', this, "notif_playerFrancAct" );
			dojo.subscribe( 'morning', this, "notif_morning" );
			dojo.subscribe( 'gameEnd', this, "notif_gameEnd" );
			dojo.subscribe( 'test', this, "notif_test" );
        },  
        
		notif_test: function( notif ) { // used to remove corpse from playertablecard
		},	
		notif_admission: function( notif ) {
			const guest = notif.args.traveller_info;
			this.removeTooltip('entrance_stack');
			this.removeTooltip( guest.location_arg );
			let html = this.travellers[ guest.id.toString() ].place( guest.location_arg );
			this.rooms[guest.location_arg.toString()  ].newGuest(guest.id.toString());
			this.addTooltipHtml( guest.location_arg, html, 0 );
		},

		// create new guest instance - update entrance_stack element with notif.args.guest_info
		notif_assignGuest: function( notif ) {
			this.createCardStats( notif.args.cards_in_game, notif.args.deck_size, notif.args.deck_set );
			const traveler = notif.args.guest_info;
			Guest.new_guest = traveler.guest_id;
			const new_traveler =  Guest.new_guest.toString();
			this.travellers[ new_traveler ] = new Guest( traveler );
			let html = this.travellers[ new_traveler ].place( traveler.locale_arg, traveler.locale );
			this.addTooltipHtml( traveler.locale_arg, html, 0 );
		},
		
		notif_payments: function( notif ) { // update player token
			let franc_amount = notif.args.player_francs > 40 ? 40 : notif.args.player_francs;

			this.setToken( notif.args.player_id, franc_amount, notif.args.player_checks ) ;
			document.getElementById("player_score_" + notif.args.player_id).innerHTML =  notif.args.score;
		},

		notif_guest_location: function( notif ) { // update guest location 
			const traveler = notif.args.guest;
			this.placeGuest( traveler.guest_id, traveler.locale, traveler.locale_arg,traveler.type_id, traveler );
		},	
		notif_free_action: function( notif ) { // update guest location 
			const traveler = notif.args.guest;
			const action = notif.args.action;
			this.prepareFreeActions( traveler, action);
		},		
		notif_burial_good: function( notif ) { // update guest location 
			this.free_burials[Guest.selected_for_action] = this.selected_burial;
			this.travellers[ Guest.selected_for_action ].showCorpseSelected();
			this.selecting_burial = false;
			this.clearVariblesForNextFreeAction('burried_btn')
		},
		notif_playerAct: function( notif ) { // update guest location 
			const player_id = notif.args.player_id;
			const action = notif.args.action1;
			switch(action) {
				case "gains room":
					this.rooms[notif.args.affected].changeOwner( player_id, notif.args.player_color );
					break;
				case " adds service token to room":
					this.rooms[notif.args.affected].placeServiceToken( notif.args.player_color );
					break;
				default:
					console.log("error in playerAct" ) ;
			}
			
		},
		notif_log_action: function( notif ) { // all player notification for guest actions
			this.onUnDo();
console.log("in notif_log_action ");
			Guest.num_peasants_in_bistro =  notif.args.num_in_bistro;
			this.createBistroTooltip(); //updates the bistro tooltip with current num peasants
			const traveler  = notif.args.traveller_info;
			const traveler_id = traveler.guest_id.toString();
			const player = notif.args.player_id;
			this.setCadaverInnerhtml( player , notif.args.player_kill);
			
			//vacate old location
			const old_location = notif.args.old_locale;
			const old_locale = old_location[0];
			const old_locale_arg = old_location[1];
			switch(old_locale) {
				case "room":
					this.rooms[old_locale_arg].vacateRoom();
					this.travellers[traveler_id].removeImageFromRoom( old_locale_arg );
					this.removeTooltip( old_locale_arg );
					break;
				case "discard":
					// code block
					break;
				case "killed":
					this.travellers[traveler_id].removeCorpse()
					break;
				default:
					break;
			}
			switch(notif.args.action) {
				case "spent":
				case "removed":
					this.handleSpentCards(traveler);
					break;
				case "killed":
					const corpse_id = this.travellers[traveler_id].killStash( traveler.locale, traveler.locale_arg );
					dojo.connect( $( corpse_id ), 'onclick', this, 'onCorpse' );
					break;
				case "bribed":
					this.travellers[traveler_id].move( traveler.locale, traveler.locale_arg );
					if  ( Guest.num_peasants_in_bistro < 1 ) this.travellers[traveler_id].removeImageFromRoom( 'bistro' );
					break;
				case "built":
					const annex_info = this.travellers[traveler_id].builtAnnex(traveler.locale, traveler.locale_arg);
					if ( traveler["guest_type"] == 'brewer' ) 		this.brewer_annex_player = traveler.locale_arg;
					this.addTooltipHtml( "annex_" + traveler_id, annex_info.tooltip, 0 );
					dojo.connect( $( "annex_" + traveler_id ), 'onclick', this, 'onBurialSite' );
					break;
				case "burried":
					this.travellers[traveler_id].bury(traveler.locale, traveler.locale_arg);
					break;
				default:
					break;
			}
		},		
		
		notif_action: function( notif ) { // private player notification
			this.removeTooltip( "top"  );
			Room.clear_location( "top" );

			const traveler  = notif.args.traveller_info;
			const traveler_id = traveler.guest_id.toString();

			//vacate old location
			const old_location = notif.args.old_locale;
			const old_locale = old_location[0];
			const old_locale_arg = old_location[1];
			
			switch(old_locale) {
				case "hand":
					this.playerhand.removeFromStockById( traveler_id );
					break;
				default:
					break;
			}
		
			switch(notif.args.action) {
				case "spent":
					break;
				case "bribed":
					this.playerhand.addToStockWithId( traveler.type_id, traveler.guest_id  );
					break;
				default:
					break;
			}
		},

		notif_playerFrancAct: function( notif ){
			const player_id = notif.args.player_id;
			const francs = notif.args.francs;
			const checks = notif.args.checks;
			this.setToken( player_id, francs, checks );

		},
		notif_morning: function( notif ) { // used to remove corpse from playertablecard
			Guest.num_peasants_in_bistro =  notif.args.num_in_bistro;
			this.createBistroTooltip(); //updates the bistro tooltip with current num peasants 		
			const exit_stack = notif.args.exit_stack;

			// refreshes discard display
			var discard_flex = document.querySelector("#discard_flex"); 
			discard_flex.replaceChildren(); //empties discard display

			// must be done before move so corpse are removed before locale_agre changes
			const corpses = document.querySelectorAll(".corpse");
			corpses.forEach(corpse => {   corpse.remove(); });
			for (var guest in exit_stack) {
				this.travellers[guest].move( 'discard',  'exit_stack' );
				this.travellers[guest].joinDiscard();
			}
			const players_kill = notif.args.all_players_kill;
			for( var player_id in players_kill ){
				this.setCadaverInnerhtml( player_id, players_kill[ player_id] );
			}
		},	

		notif_gameEnd: function( notif ) { // used to remove corpse from playertablecard
			this.showMessage( "End of game has been triggered - this is the last round" , "info" );
			
		},				
   });             
});
