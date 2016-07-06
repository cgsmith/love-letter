<?php
/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * PanicMode implementation : © Chris Smith <chris@cgsmith.net>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 * 
 * panicmode.action.php
 *
 * PanicMode main action entry point
 *
 *
 * In this file, you are describing all the methods that can be called from your
 * user interface logic (javascript).
 *       
 * If you define a method "myAction" here, then you can call it from your javascript code with:
 * this.ajaxcall( "/panicmode/panicmode/myAction.html", ...)
 *
 */
  
  
  class action_panicmode extends APP_GameAction
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
            $this->view = "panicmode_panicmode";
            self::trace( "Complete reinitialization of board game" );
      }
  	}
	  public function discardCard()
	  {
		  self::setAjaxMode();
		  $card_id = self::getArg( "id", AT_posint, true );
		  $this->game->discardCard( $card_id );
		  self::ajaxResponse( );
	  }

	  public function giveCards()
	  {
		  self::setAjaxMode();
		  $cards_raw = self::getArg( "cards", AT_numberlist, true );

		  // Removing last ';' if exists
		  if( substr( $cards_raw, -1 ) == ';' )
			  $cards_raw = substr( $cards_raw, 0, -1 );
		  if( $cards_raw == '' )
			  $cards = array();
		  else
			  $cards = explode( ';', $cards_raw );

		  $this->game->giveCards( $cards );
		  self::ajaxResponse( );
	  }

  }
  

