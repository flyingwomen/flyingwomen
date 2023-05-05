class Room {
	static last_selected_room = null; 
	static clear_location( location_id ) {
		const location_node = document.querySelector("#" + location_id);
		if (location_node.hasChildNodes()) {
			location_node.removeChild(location_node.children[0]);
		}
		location_node.removeAttribute('class');
	}
		static removeAllRoomSelected() {
			const overlays = document.querySelectorAll('.overlay');
						overlays.forEach(overlay => {
							overlay.remove();
						});
		}

        identity; // room number  - 1,2,3,4 so have correct number of rooms
		owner_color; // neutral or owner color
		node_id; // "#" + locale + identity.toString();
		owner; // owner player ID otherwise neutral
		room_css_style; // css class used
		node;
		guest_id; // current guest in room
		

	constructor( identity, color = "white" , owner = "neutral", guest_id = "", service_token = null ) {
        this.identity = identity;
		this.owner_color = color;
		this.node_id = "#"  + identity;
		this.owner = owner;
		this.room_css_style = "room_" + color;
		this.node = document.querySelector(this.node_id);
		this.guest_id = guest_id;
		this.room_border_id = "#room_" +  identity;
		this.room_border_node = document.querySelector(this.room_border_id);
		this.service_token = service_token;
	}

    createElement() { // creates 
		this.room_border_node.setAttribute("class", this.room_css_style );
    }
	vacateRoom() {
		this.guest_id = "";
		this.node.setAttribute("class", this.room_css_style );
	}
	
	newGuest(guest_id) {
		this.guest_id = guest_id;
		
	}
	changeOwner( new_owner, owner_color ) {
		this.owner = new_owner;
		this.owner_color = owner_color;
		this.room_css_style = "room_" + this.owner_color;
		this.room_border_node.setAttribute("class", this.room_css_style );
	}
	activateSelect() {
		this.node.classList.add("select");	
	}
	deActivateSelect() {
		this.node.classList.remove("select");	
	}
	isVacant() {
		return ( this.guest_id == "" ) ? true : false;
	}
	getGuest() {
		return this.guest_id;
	}
	createChild( child_id, main_class, other_classes=null ) { 
		let child_node = document.createElement("div");
		child_node.setAttribute("id", child_id );
		child_node.setAttribute("class", main_class );
		if ( other_classes !== null) 	child_node.classList.add(...other_classes );
		return child_node;
	}
	placeServiceToken( service_token_color ) {
		this.service_token = service_token_color; // set class property service_token to token color
		const main_class =  "servicetoken_" + this.service_token;
		const child_id = this.identity + "servicetoken_";
		const service_token_elem = this.createChild( child_id, main_class,["serviceToken"] );
		const parent_node =  document.querySelector(this.node_id );	
		parent_node.appendChild(service_token_elem);
	}
	showRoomSelected() { //show gray overlay element
		const main_class =  "overlay";
		const child_id = this.identity + "overlay";
		const overlay_elem = this.createChild( child_id, main_class );
		const parent_node =  document.querySelector(this.node_id );	
		parent_node.appendChild(overlay_elem);
	}
	removeRoomSelected() { 
		const child_id = this.identity + "overlay";
		const element = document.getElementById(child_id);
		element.remove(); 
	}
}


