<?php
/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * BI implementation : © <Your name here> <Your email address here>
 *
 * This code has been produced on the BGA studio platform for use on https://boardgamearena.com.
 * See http://en.doc.boardgamearena.com/Studio for more information.
 * -----
 * 
 * bi.action.php
 *
 * BI main action entry point
 *
 *
 * In this file, you are describing all the methods that can be called from your
 * user interface logic (javascript).
 *       
 * If you define a method "myAction" here, then you can call it from your javascript code with:
 * this.ajaxcall( "/bi/bi/myAction.html", ...)
 *
 */
  
  
  class action_bi extends APP_GameAction
  { 
    // Constructor: please do not modify
   	public function __default()
  	{
  	    if( self::isArg( 'notifwindow') )
  	    {
            $this->view = "common_notifwindow";
  	        $this->viewArgs['table'] = self::getArg( "table", AT_posint, true );
  	    }
  	    else
  	    {
            $this->view = "bi_bi";
            self::trace( "Complete reinitialization of board game" );
      }
  	} 
  	
  	// TODO: defines your action entry points there

	public function admitGuest() 	{
		self::setAjaxMode();
        $info = self::getArg("id", AT_alphanum, true);
        $this->game->admitGuest( $info );
        self::ajaxResponse();
	}
	public function playerAction() 	{
		self::setAjaxMode();
        $info = self::getArg("id", AT_alphanum_dash, true);
        $this->game->playerAction( $info );
        self::ajaxResponse();
	}
	public function getAccomplices() 	{
		self::setAjaxMode();
        $info = self::getArg("id", AT_alphanum, true);
        $this->game->getAccomplices( $info );
        self::ajaxResponse();
	}
	public function confirmSelection()
	{
		self::setAjaxMode();
        $info = self::getArg("id", AT_alphanum, true);
        $this->game->confirmSelection( $info );
        self::ajaxResponse();
	}
	public function passConfirm()	{
		self::setAjaxMode();
        $info = self::getArg("id", AT_alphanum_dash, true);
        $this->game->passConfirm( $info );
        self::ajaxResponse();
	}
	public function checkBurialSite()	{
		self::setAjaxMode();
        $info = self::getArg("id", AT_alphanum_dash, true);
        $this->game->checkBurialSite( $info );
        self::ajaxResponse();
	}
	public function doneConfirm()	{
		self::setAjaxMode();
        $info = self::getArg("id", AT_alphanum_dash, true);
        $this->game->doneConfirm( $info );
        self::ajaxResponse();
	}
    /*
    
    Example:
  	
    public function myAction()
    {
        self::setAjaxMode();     

        // Retrieve arguments
        // Note: these arguments correspond to what has been sent through the javascript "ajaxcall" method
        $arg1 = self::getArg( "myArgument1", AT_posint, true );
        $arg2 = self::getArg( "myArgument2", AT_posint, true );

        // Then, call the appropriate method in your game logic, like "playCard" or "myAction"
        $this->game->myAction( $arg1, $arg2 );

        self::ajaxResponse( );
    }
    
    */

  }
  

