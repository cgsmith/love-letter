<?php
 /**
  *------
  * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
  * PanicMode implementation : © <Your name here> <Your email address here>
  * 
  * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
  * See http://en.boardgamearena.com/#!doc/Studio for more information.
  * -----
  * 
  * panicmode.game.php
  *
  * This is the main file for your game logic.
  *
  * In this PHP file, you are going to defines the rules of the game.
  *
  */


require_once( APP_GAMEMODULE_PATH.'module/table/table.game.php' );


class PanicMode extends Table
{
	function PanicMode( )
	{
        	
 
        // Your global variables labels:
        //  Here, you can assign labels to global variables you are using for this game.
        //  You can use any number of global variables with IDs between 10 and 99.
        //  If your game has options (variants), you also have to associate here a label to
        //  the corresponding ID in gameoptions.inc.php.
        // Note: afterwards, you can get/set the global variables with getGameStateValue/setGameStateInitialValue/setGameStateValue
        parent::__construct();
        self::initGameStateLabels( array(
            "currentHandType" => 10,
            "trickColor" => 11,
            "alreadyPlayedHearts" => 12,
            "gameLength" => 100 )
        );

        $this->cards = self::getNew( "module.common.deck" );
        $this->cards->init( "card" );
	}
	
    protected function getGameName( )
    {
		// Used for translations and stuff. Please do not modify.
        return "panicmode";
    }	

    /*
        setupNewGame:
        
        This method is called only once, when a new game is launched.
        In this method, you must setup the game according to the game rules, so that
        the game is ready to be played.
    */
    protected function setupNewGame( $players, $options = array() )
    {    
        // Set the colors of the players with HTML color code
        // The default below is red/green/blue/orange/brown
        // The number of colors defined here must correspond to the maximum number of players allowed for the gams
        $default_colors = array( "ff0000", "008000", "0000ff", "ffa500", "773300" );
 
        // Create players
        // Note: if you added some extra field on "player" table in the database (dbmodel.sql), you can initialize it there.
        $sql = "INSERT INTO player (player_id, player_color, player_canal, player_name, player_avatar) VALUES ";
        $values = array();
        foreach( $players as $player_id => $player )
        {
            $color = array_shift( $default_colors );
            $values[] = "('".$player_id."','$color','".$player['player_canal']."','".addslashes( $player['player_name'] )."','".addslashes( $player['player_avatar'] )."')";
        }
        $sql .= implode( $values, ',' );
        self::DbQuery( $sql );
        self::reattributeColorsBasedOnPreferences( $players, array(  "ff0000", "008000", "0000ff", "ffa500", "773300" ) );
        self::reloadPlayersBasicInfos();
        
        /************ Start the game initialization *****/

        // Init global values with their initial values
        //self::setGameStateInitialValue( 'my_first_global_variable', 0 );
        
        // Note: hand types: 0 = give 3 cards to player on the left
        //                   1 = give 3 cards to player on the right
        //                   2 = give 3 cards to player on tthe front
        //                   3 = keep cards
        self::setGameStateInitialValue( 'currentHandType', 0 );

        // Set current trick color to zero (0= no trick color)
        self::setGameStateInitialValue( 'trickColor', 0 );

        // Mark if we already played some heart during this hand
        self::setGameStateInitialValue( 'alreadyPlayedHearts', 0 );

        // Init game statistics
        // (note: statistics are defined in your stats.inc.php file)


        // Create cards
        $cards = array();
        foreach( $this->card_amounts as $card_id => $qty )
        {
                $cards[] = array( 'type' => $card_id, 'type_arg' => $card_id, 'nbr' => $qty);
        }
        $this->cards->createCards( $cards, 'deck' );

        /************ End of the game initialization *****/
        // Active first player
        self::activeNextPlayer();
    }

    /*
        getAllDatas: 
        
        Gather all informations about current game situation (visible by the current player).
        
        The method is called each time the game interface is displayed to a player, ie:
        _ when the game starts
        _ when a player refreshes the game page (F5)
    */
    protected function getAllDatas()
    {
        $result = array( 'players' => array() );
    
        $current_player_id = self::getCurrentPlayerId();    // !! We must only return informations visible by this player !!
    
        // Get information about players
        // Note: you can retrieve some extra field you added for "player" table in "dbmodel.sql" if you need it.
        $sql = "SELECT player_id id, player_score score FROM player ";
        $result['players'] = self::getCollectionFromDb( $sql );
  
        // TODO: Gather all information about current game situation (visible by player $current_player_id).
  
        return $result;
    }

    /*
        getGameProgression:
        
        Compute and return the current game progression.
        The number returned must be an integer beween 0 (=the game just started) and
        100 (= the game is finished or almost finished).
    
        This method is called each time we are in a game state with the "updateGameProgression" property set to true 
        (see states.inc.php)
    */
    function getGameProgression()
    {
        // TODO: compute and return the game progression

        return 0;
    }


//////////////////////////////////////////////////////////////////////////////
//////////// Utility functions
////////////    

    /*
        In this space, you can put any utility methods useful for your game logic
    */



//////////////////////////////////////////////////////////////////////////////
//////////// Player actions
//////////// 

    /*
        Each time a player is doing some game action, one of the methods below is called.
        (note: each method below must match an input method in panicmode.action.php)
    */
    function discardCard( $card_id )
    {
        self::checkAction( "discardCard" );

        $player_id = self::getActivePlayerId();

        // Get all cards in player hand
        // (note: we must get ALL cards in player's hand in order to check if the card played is correct)

        $playerhands = $this->cards->getCardsInLocation( 'hand', $player_id );

        $bFirstCard = ( count( $playerhands ) == 2 );

        //$currentTrickColor = self::getGameStateValue( 'trickColor' ) ;

        // Check that the card is in this hand
        $bIsInHand = false;
        $currentCard = null;
        $bAtLeastOneCardOfCurrentTrickColor = false;
        $bPrincessInHand = false;
        $bAtLeastOneCardNotHeart = false;
        foreach( $playerhands as $card )
        {
            if( $card['id'] == $card_id )
            {
                $bIsInHand = true;
                $currentCard = $card;
            }

            if( $card['type'] == 8 )
            {
                $bPrincessInHand = true; // this card cannot be played
            }
        }
        if( !$bIsInHand )
            throw new feException( "This card is not in your hand" );

        // Checks are done! now we can play our card
        $this->cards->moveCard( $card_id, 'cardsontable', $player_id );

        // Set the trick color if it hasn't been set yet
        if( $currentTrickColor == 0 )
            self::setGameStateValue( 'trickColor', $currentCard['type'] );

        if( $currentCard['type'] == 2 )
            self::setGameStateValue( 'alreadyPlayedHearts', 1 );

        // And notify
        self::notifyAllPlayers( 'discardCard', clienttranslate('${player_name} discards ${value_displayed}'), array(
            'i18n' => array( 'color_displayed', 'value_displayed' ),
            'card_id' => $card_id,
            'player_id' => $player_id,
            'player_name' => self::getActivePlayerName(),
            'value' => $currentCard['type_arg'],
            'value_displayed' => $this->values_label[ $currentCard['type_arg'] ],
        ) );

        // Next player
        $this->gamestate->nextState( 'discardCard' );
    }

    // Give some cards (before the hands begin)
    /*function giveCards( $card_ids )
    {
        self::checkAction( "giveCards" );

        // !! Here we have to get CURRENT player (= player who send the request) and not
        //    active player, cause we are in a multiple active player state and the "active player"
        //    correspond to nothing.
        $player_id = self::getCurrentPlayerId();

        if( count( $card_ids ) != 3 )
            throw new feException( self::_("You must give exactly 3 cards") );

        // Check if these cards are in player hands
        $cards = $this->cards->getCards( $card_ids );

        if( count( $cards ) != 3 )
            throw new feException( self::_("Some of these cards don't exist") );

        foreach( $cards as $card )
        {
            if( $card['location'] != 'hand' || $card['location_arg'] != $player_id )
                throw new feException( self::_("Some of these cards are not in your hand") );
        }

        // To which player should I give these cards ?
        $player_to_give_cards = null;
        $player_to_direction = self::getPlayersToDirection();   // Note: current player is on the south
        $handType = self::getGameStateValue( "currentHandType" );
        if( $handType == 0 )
            $direction = 'W';
        else if( $handType == 1 )
            $direction = 'N';
        else if( $handType == 2 )
            $direction = 'E';
        foreach( $player_to_direction as $opponent_id => $opponent_direction )
        {
            if( $opponent_direction == $direction )
                $player_to_give_cards = $opponent_id;
        }
        if( $player_to_give_cards === null )
            throw new feException( self::_("Error while determining to who give the cards") );

        // Allright, these cards can be given to this player
        // (note: we place the cards in some temporary location in order he can't see them before the hand starts)
        $this->cards->moveCards( $card_ids, "temporary", $player_to_give_cards );

        // Notify the player so we can make these cards disapear
        self::notifyPlayer( $player_id, "giveCards", "", array(
            "cards" => $card_ids
        ) );

        // Make this player unactive now
        // (and tell the machine state to use transtion "giveCards" if all players are now unactive
        $this->gamestate->setPlayerNonMultiactive( $player_id, "giveCards" );
    }*/


    
//////////////////////////////////////////////////////////////////////////////
//////////// Game state arguments
////////////

    /*
        Here, you can create methods defined as "game state arguments" (see "args" property in states.inc.php).
        These methods function is to return some additional information that is specific to the current
        game state.
    */

    function argGiveCards()
    {
        $handType = self::getGameStateValue( "currentHandType" );
        $direction = "";
        if( $handType == 0 )
            $direction = clienttranslate( "the player on the left" );
        else if( $handType == 1 )
            $direction = clienttranslate( "the player accros the table" );
        else if( $handType == 2 )
            $direction = clienttranslate( "the player on the right" );

        return array(
            "i18n" => array( 'direction'),
            "direction" => $direction
        );
    }

//////////////////////////////////////////////////////////////////////////////
//////////// Game state actions
////////////

    /*
        Here, you can create methods defined as "game state actions" (see "action" property in states.inc.php).
        The action method of state X is called everytime the current game state is set to X.
    */

    function stDrawCard()
    {
        // Draw a new card from the deck
        $card = $this->cards->pickCard( 'deck', self::getActivePlayerId());
        self::notifyPlayer( self::getActivePlayerId(), 'drawCard', '', array(
            'card' => $card
        ) );

        // check if the player has 7 and 6 or 5 in hand
        $playershand = $this->cards->getPlayerHand(self::getActivePlayerId());
        $hasSeven = false;
        $hasFiveOrSix = false;
        foreach ($playershand as $card) {
            if ($card['type'] == 7) {
                $card_id = $card['id'];
                $hasSeven = true;
            }elseif ($card['type'] == 6 || $card['type'] == 5) {
                $hasFiveOrSix = true;
            }
        }

        // does the player have 5/6 & 7? discard 7 if so and notify players
        if ($hasFiveOrSix && $hasSeven) {

            // Checks are done! now we must discard the 7
            $this->cards->moveCard( $card_id, 'cardsontable', self::getActivePlayerId() );

            // And notify
            self::notifyAllPlayers( 'discardCard', clienttranslate('${player_name} discards ${value_displayed}'), array(
                'i18n' => array( 'value_displayed' ),
                'card_id' => $card_id,
                'player_id' => self::getActivePlayerId() ,
                'player_name' => self::getActivePlayerName(),
                'value' => 7,
                'value_displayed' => $this->values_label[ 7 ],
            ) );
            $this->gamestate->nextState( 'discardCard' );
        }

    }

    function stDiscardCard()
    {
        // 1 - Name another card & choose a player. If he has that card he is out
        // 2 - Look at another player's hand
        // 3 - Choose a player, secretly compare hands. Lowest value out of the round
        // 4 - Ignore all other cards until your next round
        // 5 - Choose any player, that player discards hand and draws a new card
        // 6 - Trade hands with a player of your choice
        // 7 - If you have this card and 6 or 5, discard this card
        // 8 - If you discard this, you are out of the round

        $handType = self::getGameStateValue( "currentHandType" );

        // If we are in hand type "3" = "keep cards", skip this step
        if( $handType == 3 )
        {
            $this->gamestate->nextState( "skip" );
        }
        else
        {
            // Active all players (everyone has to choose 3 cards to give)
            $this->gamestate->setAllPlayersMultiactive();
        }
    }
    
    function stNewHand()
    {
        self::incStat( 1, "handNbr" );

        // Take back all cards (from any location => null) to deck
        $this->cards->moveAllCardsInLocation( null, "deck" );
        $this->cards->shuffle( 'deck' );

        // move the top card to the discard pile
        $this->cards->moveCard($this->cards->getCardOnTop( "deck" ), "discard");

        // Deal 13 cards to each players
        // Create deck, shuffle it and give 13 initial cards
        $players = self::loadPlayersBasicInfos();
        foreach( $players as $player_id => $player )
        {
            $cards = $this->cards->pickCards( 1, 'deck', $player_id );

            // Notify player about his cards
            self::notifyPlayer( $player_id, 'newHand', '', array(
                'cards' => $cards
            ) );
        }

        self::setGameStateValue( 'alreadyPlayedHearts', 0 );

        $this->gamestate->nextState( "" );
    }
    
    function stNextPlayer()
    {
        // Active next player OR end the trick and go to the next trick OR end the hand


            // Standard case (not the end of the trick)
            // => just active the next player

            $player_id = self::activeNextPlayer();
            self::giveExtraTime( $player_id );

            $this->gamestate->nextState( 'nextPlayer' );

    }

//////////////////////////////////////////////////////////////////////////////
//////////// Zombie
////////////

    /*
        zombieTurn:
        
        This method is called each time it is the turn of a player who has quit the game (= "zombie" player).
        You can do whatever you want in order to make sure the turn of this player ends appropriately
        (ex: pass).
    */

    function zombieTurn( $state, $active_player )
    {
    	$statename = $state['name'];
    	
        if ($state['type'] == "activeplayer") {
            switch ($statename) {
                default:
                    $this->gamestate->nextState( "zombiePass" );
                	break;
            }

            return;
        }

        if ($state['type'] == "multipleactiveplayer") {
            // Make sure player is in a non blocking status for role turn
            $sql = "
                UPDATE  player
                SET     player_is_multiactive = 0
                WHERE   player_id = $active_player
            ";
            self::DbQuery( $sql );

            $this->gamestate->updateMultiactiveOrNextState( '' );
            return;
        }

        throw new feException( "Zombie mode not supported at this game state: ".$statename );
    }
    
///////////////////////////////////////////////////////////////////////////////////:
////////// DB upgrade
//////////

    /*
        upgradeTableDb:
        
        You don't have to care about this until your game has been published on BGA.
        Once your game is on BGA, this method is called everytime the system detects a game running with your old
        Database scheme.
        In this case, if you change your Database scheme, you just have to apply the needed changes in order to
        update the game database and allow the game to continue to run with your new version.
    
    */
    
    function upgradeTableDb( $from_version )
    {
        // $from_version is the current version of this game database, in numerical form.
        // For example, if the game was running with a release of your game named "140430-1345",
        // $from_version is equal to 1404301345
        
        // Example:
//        if( $from_version <= 1404301345 )
//        {
//            $sql = "ALTER TABLE xxxxxxx ....";
//            self::DbQuery( $sql );
//        }
//        if( $from_version <= 1405061421 )
//        {
//            $sql = "CREATE TABLE xxxxxxx ....";
//            self::DbQuery( $sql );
//        }
//        // Please add your future database scheme changes here
//
//


    }    
}
