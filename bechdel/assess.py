#!/usr/bin/python
import re
import json
import pprint
# from string import punctuation
import lib

pp = pprint.PrettyPrinter(indent=4)

def get_cast_data( path ):
    json_data = lib.get_text( path )
    return json.loads( json_data )

def get_script_characters( path ):
    json_data = lib.get_text( path )
    return json.loads( json_data )

def get_data_by_gender( cast_data ):
    data = {
        'cast': {
            'total': 0,
            'gender': { 'M': 0, 'F': 0, 'U': 0, '': 0 }
        },
        'words': {
            'total': 0,
            'gender': { 'M': 0, 'F': 0, 'U': 0, '': 0 }
        },
        'lines': {
            'total': 0,
            'gender': { 'M': 0, 'F': 0, 'U': 0, '': 0 }
        },
    }
    data['cast']['total'] = len( cast_data )
    for key, val in cast_data.items():
        gender = cast_data[ key ]['gender']
        words  = cast_data[ key ]['words']
        lines  = cast_data[ key ]['lines']
        if '' != gender:
            data['words']['gender'][ gender ] += words
            data['lines']['gender'][ gender ] += lines
            data['cast']['gender'][ gender ]  += 1
            data['words']['total'] += words
            data['lines']['total'] += lines
    return data

def print_character_data( cast_data ):
    print( '' )
    print( 'Cast:' )
    print( '  G  Words  Lines  Character' )
    print( '--------------------------' )
    characters = cast_data.items()
    characters.sort()
    for key, val in characters:
        if '' != val['gender']:
            print( val['gender'].rjust( 2 ) + str( val['words'] ).rjust( 7 ) + str( val['lines'] ).rjust( 7 ) + "  " + key )

def print_data_by_gender( typ ):
    print( '' )
    print( typ + ' by gender:' )
    for key, val in gender_data[ typ ]['gender'].items():
        if '' != key:
            count   = val
            percent = val * 100 / gender_data[ typ ]['total'] if 0 != gender_data[ typ ]['total'] else 0
            print( key.rjust( 2 ) + ": " + str( count ).rjust( 7 ) + " " + str( percent ).rjust( 3 ) + "%" )

script_name = lib.get_script_name()
cast_data   = get_cast_data( "./data/cast/" + script_name + ".json" )

gender_data = get_data_by_gender( cast_data )
pp.pprint( gender_data )
print_character_data( cast_data )



print_data_by_gender( 'cast' )
print_data_by_gender( 'words' )
print_data_by_gender( 'lines' )

print( '' )
print '--- DONE ---'
