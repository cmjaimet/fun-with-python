#!/usr/bin/python
import sys
from random import randint
import character
import abilities
import classes
import races
import character_sheet

def get_input( question, error_message, options ):
    print( "" )
    print( "Options: {}".format( ", ".join( options ) ) )
    done = False
    while False == done:
        inp = input( question )
        if inp in options:
            done = True
            return inp
        print( error_message )

def get_cli_param( options ):
    for param in sys.argv:
        for opt in options:
            if param == opt:
                return opt
    return False

def create_character():
    race_data = races.list()
    class_data = classes.list()
    character_data["class"] = set_class( class_data )
    character_data["race"] = set_race( race_data )
    character_data["subrace"] = set_subrace( race_data, character_data["race"] )
    set_abilities()

def set_class( data ):
    options = get_array_keys( data )
    result = get_cli_param( options )
    if False != result:
        return result
    return get_input( "What class would you like to play? ", "*** Oops! Pick from the list above", options )

def set_race( data ):
    options = get_array_keys( data )
    result = get_cli_param( options )
    if False != result:
        return result
    return get_input( "What race would you like to play? ", "*** Oops! Pick from the list above", options )

def set_subrace( data, race ):
    options = get_array_keys( data[ race ]["subraces"] )
    if 1 == len( options ):
        return options[0]
    result = get_cli_param( options )
    if False != result:
        return result
    return get_input( "What subrace of {} would you like to play? ".format( race ), "*** Oops! Pick from the list above", options )

def set_abilities():
    race_data = races.list()
    set_base_abilities()
    ability_bonuses = race_data[ character_data["race"] ]["subraces"][ character_data["subrace"] ]["abilities"]
    for ability in ability_bonuses:
        character_data["abilities"][ ability ] += ability_bonuses[ ability ]

def get_array_keys( data ):
    names = []
    for key in data:
        names.append( key )
    return names

def set_base_abilities():
    class_data = classes.list()
    ability_data = abilities.list()
    starting_scores = ability_data.get( "starting_scores" )
    ability_list = ability_data.get( 'types' )
    class_abilities = class_data[ character_data["class"] ]["abilities"]
    class_abilities_max = len( class_abilities )
    for x in range( 0, class_abilities_max ):
        character_data["abilities"][ class_abilities[ x ] ] = starting_scores[0] # primary ability for this class should be highest
        ability_list.remove( class_abilities[ x ] )
        del starting_scores[0]
    max = len( starting_scores ) - 1
    for ability in ability_list:
        idx = randint( 0, max )
        ability_score = starting_scores[ idx ]
        character_data["abilities"][ ability ] = ability_score
        del starting_scores[ idx ]
        max -= 1

character_data = character.get()

create_character()

character_sheet.display_character( character_data, 'cli' )
print( character_sheet.display_character( character_data, 'web' ) )
