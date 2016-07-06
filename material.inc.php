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
 * material.inc.php
 *
 * PanicMode game material description
 *
 * Here, you can describe the material of your game with PHP variables.
 *   
 * This file is loaded in your game logic class constructor, ie these variables
 * are available everywhere in your game logic code.
 *
 */

$this->card_amounts = array(
    1 => 5,
    2 => 2,
    3 => 2,
    4 => 2,
    5 => 2,
    6 => 1,
    7 => 1,
    8 => 1,
);

$this->values_label = array(
    1 => clienttranslate('Guard'),
    2 => clienttranslate('Priest'),
    3 => clienttranslate('Baron'),
    4 => clienttranslate('Handmaid'),
    5 => clienttranslate('Prince'),
    6 => clienttranslate('King'),
    7 => clienttranslate('Countess'),
    8 => clienttranslate('Princess'),
);

