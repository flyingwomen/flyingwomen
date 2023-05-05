{OVERALL_GAME_HEADER}

<!--  
--------
-- BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
-- BI implementation : © <Your name here> <Your email address here>
-- 
-- This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
-- See http://en.boardgamearena.com/#!doc/Studio for more information.
-------

    bi_bi.tpl
    
    This is the HTML template of your game.
    
    Everything you are writing in this file will be displayed in the HTML page of your game user interface,
    in the "main game zone" of the screen.
    
    You can use in this template:
    _ variables, with the format {MY_VARIABLE_ELEMENT}.
    _ HTML block, with the BEGIN/END format
    
    See your "view" PHP file to check how to set variables and control blocks
    
    Please REMOVE this comment before publishing your game on BGA
-->

<div id="outer_wrapper" class="outer_wrapper">
	<div id="admin" class="admin">
		<h1 id="card_stats" class="whiteblock"></h1> 
		<div id="top"></div>
		<div id="bonus" class="hide whiteblock" > <h3> bonus victims</h3>
			<div id="bonus_victims"></div>
		</div>
		<div class="hide whiteblock" style="font-weight: 900;font-size: xx-large;"autocomplete="off" 	id="passform">

			<fieldset>
				<legend>Launder Money:</legend>
				<div>
					<label for="quantity">Checks To Add:
						<input type="number" name="quantity" id="quantity" value="0" data-allow="[0-9]">
					</label>
				</div>
				<div>
					<div class="franc"></div>
					<div style="display: inline-block">total: </div><output id="franc_amount"></output><br>
					<div class="check"></div>
					<div style="display: inline-block"> total: </div> <output for="quantity" id="checks_amount"></output>
				</div>
	
			</fieldset>
			<button id="btnsubmit">Submit</button>
		</div>
	</div>
	<div id="discard" class="hide whiteblock" style="font-weight: 900;font-size: xx-large;">Exit Stack
		<div id="discard_flex"></div>
	</div>
	<div id="main_table" class="main_table"> 
		<div id="table_top">
			<div id="room_top_card1" ><div id="top_card1" class="card"></div></div>
			<div id="room_top_card2" ><div id="top_card2" class="card"></div></div>
			<div id="room_top_card3" ><div id="top_card3" class="card"></div></div>
			<div id="room_top_card4" ><div id="top_card4" class="card"></div></div>
		</div>
		<div id="entrance_stack" class="card_back card" ></div>
		<div id="table_mid">
			<div id="franc_board">
			<div id="board">
				<!-- BEGIN square -->
					<div id="square_{X}_{Y}" class="square" style="left: {LEFT}px; top: {TOP}px;"></div>
					<!-- END square -->
				</div>
			</div>
			<div id="bistro"  class="card"></div>
		</div>
		<div id="exit_stack" class="card card32"></div>
		<div id="table_bottom">
			<div id="room_bottom_card1"> <div id="bottom_card1" class="card"></div></div>
			<div id="room_bottom_card2" ><div id="bottom_card2" class="card"></div></div>
			<div id="room_bottom_card3" ><div id="bottom_card3" class="card"></div></div>
			<div id="room_bottom_card4" ><div id="bottom_card4" class="card"></div></div>
		</div>
	</div>

	<div id="myhand_wrap" class="whiteblock">
		<div id="myhand">
		</div>
	</div>
	<div id="current_player_area_wrapper">
	    <!-- BEGIN cur_player -->
		<div class="playertable  playertable_{DIR}">
			<div class="playertablename whiteblock" style="color:#{PLAYER_COLOR}; margin-top:15px;">
				{PLAYER_NAME}
			</div>
			<div class="cur_playertablecard whiteblock" id="playertablecard_{PLAYER_ID}">
				<div id="cur_player_kills" class="killed"  style="color:#{PLAYER_COLOR};">Recent kills
					<div id="killed_{PLAYER_ID}" class="kill_flex"></div>
				</div>
				<div id="cur_player_annex" class="annex"  style="color:#{PLAYER_COLOR};">Annxes
					<div id="annexes_{PLAYER_ID}" class="annex_flex">
						<div id="annex_{PLAYER_ID}" class="annexed">
							<div id="top_card_{PLAYER_ID}" class="annex_{PLAYER_COLOR} top_card"></div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<!-- END cur_player -->
	

	</div>	
	<div id="player_area_wrapper">
	    <!-- BEGIN player -->
		<div class="playertable  playertable_{DIR}">
			<div class="playertablename whiteblock" style="color:#{PLAYER_COLOR}; margin-top:15px;">
				{PLAYER_NAME}
			</div>
			<div class="playertablecard whiteblock" id="playertablecard_{PLAYER_ID}">
				<div class="killed"  style="color:#{PLAYER_COLOR};">Recent kills
					<div id="killed_{PLAYER_ID}" class="kill_flex"></div>
				</div>
				<div class="annex"  style="color:#{PLAYER_COLOR};">Annxes
					<div id="annexes_{PLAYER_ID}" class="annex_flex">
						<div id="annex_{PLAYER_ID}" class="annexed">
							<div id="top_card_{PLAYER_ID}" class="annex_{PLAYER_COLOR} top_card"></div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<!-- END player -->
	

	</div>


</div>



<script type="text/javascript">

// Javascript HTML templates

/*
// Example:
var jstpl_some_game_item='<div class="my_game_item" id="my_game_item_${MY_ITEM_ID}"></div>';

*/
var jstpl_player_board = '\<div class="cp_board">\
	<div id="franc_img" class="franc"></div><div id="franc_p${id}" style="display:inline-block"><span style="font-weight:bold">0</span></div>\
	<div id="check_img" class="check"></div><div id="check_p${id}" style="display:inline-block"><span style="font-weight:bold">0</span></div>\
	<div id="corpse_img" class="cadaver_icon"></div><div id="cadaver_panel_p${id}" style="display:inline-block"><span style="font-weight:bold">0</span></div>\
</div>';
var jstpl_spend_card = '<div class="card_${guest_type}" id="card_${guest_id}">\
                        </div>';
</script>  

{OVERALL_GAME_FOOTER}
