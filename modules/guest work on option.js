class Guest {
	
	static new_guest = null; 	// guest id currently in entrance_stack necessary for ajax
	static num_peasants_in_bistro = 0; 
	static last_test_location = null;
	static selected_for_action;
	static peasants_in_bistro = [];
	
	static createBistroHTMLToolTip()   {
		const html = `
		  <Tip class="tip">
			<article>
			  <h1>Bistro</h1><hr>
			  <ul>
				<li>Num Patrons: ${this.num_peasants_in_bistro}</li>
			  </ul> <hr>	
			  <h3> Random Peasant </h3>
			</article>
		  </Tip>
		`;
        return html;
    }

	static contactPeasant( parent_id ) { // creates guest image child on element parent_id returns html for tooltip
		const parent_node =  document.querySelector("#" + parent_id );
		if (parent_node.hasChildNodes()) {
			parent_node.removeChild(parent_node.children[0]);
		}
		const guest_image = document.createElement("p");
		const peasant_type = Math.floor(Math.random() * (2 - 1 + 1) ) +1;
		guest_image.classList.add( "card_peasant" + peasant_type, "card", "scale_2" );
		guest_image.setAttribute("id", "guestImage");
		parent_node.appendChild(guest_image);

	}
	static	createPeasantHTMLToolTip()   {

		const html = `
		  <Tip class="tip">
			<article>
			  <h1>Peasant</h1><hr>
			  <ul>
				<li>Francs in Pocket: 4</li>
			  </ul> <hr>	
			  <h3> Can Bride upto 2 peasants as an action </h3>
			</article>
		  </Tip>
		`;
        return html;
    }

    constructor( guest ) {

 		this.color = guest.color;
		this.affinity = guest.affinity;
		this.annex = guest.annex;
		this.rank = guest.ranked;
		this.pocket = guest.pocket;
		this.benefit = guest.benefit; 
        this.locale = guest.locale; 
		this.locale_arg = guest.locale_arg; // element html name 
        this.type = guest.guest_type;
		this.type_id = guest.type_id;
		this.id = guest.guest_id;
		this.locale_arg_node_id = "#" + guest.locale_arg;
		this.locale_arg_node = null;
		this.css_card = "card_" + guest.guest_type;
		this.parent_node = null;
		this.corpse = null;
		this.annex = null;
		this.corpse_image = null;
    }
	getRank() {
		return this.rank;
	}
	
	getTypeID() {
		return this.type_id;
	}
	getLocation_arg() {
		return this.locale_arg;
	}
	createGuestElement() {
		this.locale_arg_node_id = "#" + this.locale_arg;
		this.locale_arg_node = document.querySelector(this.locale_arg_node_id );	
		this.locale_arg_node.setAttribute("class","card" ); 
		this.locale_arg_node.classList.add(this.css_card, );
	}	
	createChild( child_id, main_class, other_classes=null ) { 
		let child_node = document.createElement("div");
		child_node.setAttribute("id", child_id );
		child_node.setAttribute("class", main_class );
		if ( other_classes !== null) 	child_node.classList.add(...other_classes );
		return child_node;
	}
    place( new_locale_arg, new_locale = "room" ) { 
		this.locale_arg = new_locale_arg;
		this.locale = new_locale;
		this.createGuestElement();
		return this.createHTMLToolTip();
    }
    placeNoTooltip( new_locale_arg, new_locale = "room" ) { 
		this.locale_arg = new_locale_arg;
		this.locale = new_locale;
		this.createGuestElement();
		return this.createHTMLToolTip();
    }	
	roomTest( test_location) { // just placing for room testing, instances not updated
		const test_location_id = "#" + test_location;
		const location_node = document.querySelector(test_location_id );
		location_node.classList.add("card", this.css_card );
	}	
	removeImageFromRoom( test_location )  { // removes traveller image from location
		const test_location_id = "#" + test_location;
		const location_node = document.querySelector(test_location_id );
		location_node.classList.remove( this.css_card );		
	}
	createHTMLToolTip()   {

		const html = `
		  <Tip class="tip">
			<article>
			  <h1>${this.type}</h1><hr>
			  <ul>
				<li>Affinity: ${this.affinity}</li>
				<li>Rank: ${this.rank}</li>
				<li>Francs in Pocket: ${this.pocket}</li>
				<li>Annex Location: ${this.annex}</li>
			  </ul> <hr>	
			  <h3> ${this.benefit} </h3>
			</article>
		  </Tip>
		`;
        return html;
    }
	createDeadHTMLToolTip ()   {

		const html = `
		  <Tip class="tip">
			<article>
			  <h1>Unburried Corpse</h1><hr>
			  <ul>
				<li>Rank: ${this.rank}</li>
				<li>Francs in Pocket: ${this.pocket}</li>
			  </ul> 
			</article>
		  </Tip>
		`;
        return html;
    }	
	contactGuest( vital_status = null ) { // creates guest image child on element parent_id returns html for tooltip
		const parent_node =  document.querySelector("#top" );
		if (parent_node.hasChildNodes()) {
			parent_node.removeChild(parent_node.children[0]);
		}
		const css_used =  ( vital_status == null ) ? this.corpse_image : this.css_card ;
		const child_elem = this.createChild( "guestImage", css_used, ["card", "scale_2"] ) ;
		parent_node.appendChild(child_elem);
		
		 
		return ( vital_status == null ) ? this.createDeadHTMLToolTip() : this.createHTMLToolTip();

	}
	move( new_locale, new_locale_arg )  { // updates instance info only mainly used with bribed
		this.locale_arg = new_locale_arg;
		this.locale = new_locale;
	}
	getRank() {
		let ranked = this.rank;
		if ( this.type == 'peasant1' || this.type == 'peasant2' ) 		ranked = 5;
		return ranked;
	}
	kill() {

		this.corpse_image = "rank" + this.getRank();
		this.corpse = this.createChild( "corpse_" + this.id, this.corpse_image, ["rotation_1" , "card" , "corpse" , "flexed" ] );
	}
	killStash(	locale, locale_arg ) {
		this.move(locale, locale_arg ) ;
		this.parent_node =  document.querySelector("#" + locale + "_" + locale_arg );
		this.kill();
		this.parent_node.appendChild(this.corpse);
		return this.corpse;
	}
	removeCorpse() {
		this.parent_node.removeChild(this.corpse);
	}
	
	showCorpseSelected() {
		let rank = this.getRank();
		this.corpse.setAttribute("class","selected" + rank);
		this.corpse.classList.add(...["rotation_1" , "card" , "corpse" , "flexed" ] );
	}
	showCorpseNotSelected() {
		this.corpse.setAttribute("class",this.corpse_image);
		this.corpse.classList.add(...["rotation_1" , "card" , "corpse" , "flexed" ] );
	}
	bury(locale, locale_arg) {
		this.move( locale, locale_arg );
		this.parent_node = document.querySelector("#" + locale_arg );
		let num_siblings = this.parent_node.children.length;
		let burried_class = "burried" + num_siblings;
		this.corpse_image = "rank" + this.rank;
		this.corpse = this.createChild( locale + this.id, this.corpse_image, [ "card" , burried_class ] );
		this.parent_node.appendChild(this.corpse);
	}
	
	builtAnnex(locale, locale_arg) { // This function does
	// 1 - builts annexes on annexes+ player_id aka locale_arg - annex locale is still annex 
	// 2 -creates annex container then puts image container as top_card_guest_id
		this.annex = this.createChild( "annex_" + this.id, "annexed" );
		this.move(locale, locale_arg );
		this.parent_node = document.querySelector("#annexes_"  + locale_arg );
		this.parent_node.appendChild(this.annex);
		let top_card = this.createChild( "top_card_" + this.id, this.css_card, [ "top_card" ] );
		this.annex.appendChild( top_card );
		return {
			tooltip: this.createHTMLToolTip(),
			//annex_element: this.annex,
		};
	}

	spendGuest( traveller) { // update this.locale and this.locale_arg creates images in discard and bistro
		this.locale = traveller.locale; 
		this.locale_arg = traveller.locale_arg;
		this.createGuestElement();
	}
	localeAndLocale_argConfirm( locale, locale_arg ) { // confirms locale and locale_arg
		const locale_arg_cfm = ( locale_arg == this.locale_arg ) ? true : false;
		const locale_cfm = ( locale == this.locale ) ? true : false;
		return ( locale_arg_cfm && locale_cfm ) ? true : false;
		
	}
	joinDiscard() { // card added to discard pile

		this.parent_node = document.querySelector("#discard_flex");
	
		let discard = this.createChild( "discard_" + this.id, this.css_card, [ "card", "exited" ] );
		this.parent_node.appendChild(discard );
	}
	createBenefitOption( player, action ) {
		if ( this.locale == "annex" ) { 
			if (( action == "bribed" && this.type == 'representative' ) || 
				( action == "burried" && this.type == 'abbot' )	 || ( action == "built" && this.type == 'mechanic' ) )	
				this.addBenefitOption();
		}
	}
	
	addBenefitOption() {
		this.
	}
		
}