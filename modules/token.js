class Token {
/*	
	static removeAllToken() { // sets all squares back to .square  - necessary to keep square positioning
		const squares = document.querySelector(	".square"  );
		squares.setAttribute("class", "square"  );
	}
*/	
    constructor( player_id, colour, checks) {
 		this.color = colour;
		this.player = player_id;
		this.css_token = "token_" + this.color;
		this.id = "token_" + player_id;
		this.square_id = null;
		this.francs = 0;
		this.checks = checks;
    }
	
	place( francs ) { // 1) removes token from this.square_id 2) updates this.squre_id  3) adds token element to this.square_id 
		this.francs = francs;
		this.square_id = this.francNode( francs );
		const square_id_node = document.querySelector(	"#" + this.square_id );
		const squre_children = square_id_node.childNodes;
		const num_square_children = squre_children.length;
		const token = document.createElement('div');
		token.setAttribute("id", this.id );
		token.setAttribute("class", this.css_token );
		token.classList.add( "token_" + num_square_children );
		square_id_node.appendChild( token);
    }
	updateChecks( checks) {
		this.checks = checks;
	}
	remove() {
		if ( this.square_id !== null ) { 
			const square_id_node = document.querySelector(	"#" + this.square_id );
			const token_node = document.querySelector(	"#" + this.id );
			square_id_node.removeChild( token_node );
		}
	}
	francNode( francs ){ // returns the actual square based on franc 
		let franc_node_id = null;
		switch( parseInt(francs) ) {
			case 0:
				franc_node_id = "square_4_10";
				break;
			case 1:
				franc_node_id = "square_5_6";
				break;
			case 2:
				franc_node_id = "square_8_6";
				break;
			case 3:
				franc_node_id = "square_12_6";
				break;
			case 4:
				franc_node_id = "square_16_6";
				break;
			case 5:
				franc_node_id = "square_19_6";
				break;
			case 6:
				franc_node_id = "square_22_6";
				break;
			case 7:
				franc_node_id = "square_26_6";
				break;					
			case 8:
				franc_node_id = "square_29_6";
				break;
			case 9:
				franc_node_id = "square_32_6";
				break;
			case 10:
				franc_node_id = "square_36_6";
				break;
			case 11:
				franc_node_id = "square_39_6";
				break;
			case 12:
				franc_node_id = "square_43_6";
				break;
			case 13:
				franc_node_id = "square_46_6";
				break;
			case 14:
				franc_node_id = "square_49_9";
				break;
			case 15:
				franc_node_id = "square_46_11";
				break;
			case 16:
				franc_node_id = "square_43_11";
				break;
			case 17:
				franc_node_id = "square_39_11";
				break;					
			case 18:
				franc_node_id = "square_36_11";
				break;
			case 19:
				franc_node_id = "square_33_11";
				break;
			case 20:
				franc_node_id = "square_29_12";
				break;
			case 21:
				franc_node_id = "square_26_11";
				break;
			case 22:
				franc_node_id = "square_23_11";
				break;
			case 23:
				franc_node_id = "square_19_11";
				break;
			case 24:
				franc_node_id = "square_16_11";
				break;
			case 25:
				franc_node_id = "square_12_11";
				break;
			case 26:
				franc_node_id = "square_9_11";
				break;
			case 27:
				franc_node_id = "square_6_14";
				break;					
			case 28:
				franc_node_id = "square_9_16";
				break;
			case 29:
				franc_node_id = "square_12_16";
				break;
			case 30:
				franc_node_id = "square_16_16";
				break;
			case 31:
				franc_node_id = "square_19_16";
				break;
			case 32:
				franc_node_id = "square_22_16";
				break;
			case 33:
				franc_node_id = "square_26_16";
				break;
			case 34:
				franc_node_id = "square_29_16";
				break;
			case 35:
				franc_node_id = "square_33_16";
				break;
			case 36:
				franc_node_id = "square_36_16";
				break;
			case 37:
				franc_node_id = "square_39_16";
				break;					
			case 38:
				franc_node_id = "square_43_16";
				break;
			case 39:
				franc_node_id = "square_46_16";
				break;
			case 40:
				franc_node_id = "square_50_16";
				break;
		}
		return franc_node_id;
	}

}