#!/usr/bin/python
import re
import json
import pprint
# from string import punctuation
import lib

pp = pprint.PrettyPrinter(indent=4)

def get_text( path ):
    text_file = open( path, "r" )
    text = text_file.read()
    text_file.close()
    return text

def get_cast_data( path ):
    json_data = get_text( path )
    return json.loads( json_data )

def get_script_characters( path ):
    json_data = get_text( path )
    return json.loads( json_data )

def get_data_by_gender( cast_data ):
    data = {
        'cast': {
            'total': 0,
            'gender': { 'M': 0, 'F': 0, 'N': 0, '': 0 }
        },
        'words': {
            'total': 0,
            'gender': { 'M': 0, 'F': 0, 'N': 0, '': 0 }
        },
        'lines': {
            'total': 0,
            'gender': { 'M': 0, 'F': 0, 'N': 0, '': 0 }
        },
    }
    data['cast']['total'] = len( cast_data )
    for key, val in cast_data.items():
        gender = cast_data[ key ]['gender']
        words  = cast_data[ key ]['words']
        lines  = cast_data[ key ]['lines']
        data['words']['gender'][ gender ] += words
        data['lines']['gender'][ gender ] += lines
        data['cast']['gender'][ gender ]  += 1
        data['words']['total'] += words
        data['lines']['total'] += lines
    return data

def print_character_data( characters ):
    names = characters.keys()
    names.sort()

def print_data_by_gender( typ ):
    print( '' )
    print( typ + ' by gender:' )
    for key, val in gender_data[ typ ]['gender'].items():
        count   = val
        percent = val * 100 / gender_data[ typ ]['total']
        print( key + ": " + str( count ).rjust( 7 ) + " " + str( percent ).rjust( 3 ) + "%" )

script_name = lib.get_script_name()
cast_data   = get_cast_data( "./data/cast/" + script_name + ".json" )

gender_data = get_data_by_gender( cast_data )
pp.pprint( gender_data )
print_character_data( cast_data )

names = cast_data.keys()
names.sort()

print( '' )
print( 'Cast:' )
print( 'G  Words  Lines  Character' )
print( '--------------------------' )
for name in names:
    if name in cast_data:
        gender = cast_data[ name ]['gender']
        words  = cast_data[ name ]['words']
        lines  = cast_data[ name ]['lines']
        # print( gender + " - " + name + ": words " + str( words ) + ", lines " + str( lines ) )
        print( gender + str( words ).rjust( 7 ) + str( lines ).rjust( 7 ) + "  " + name )

print_data_by_gender( 'cast' )
print_data_by_gender( 'words' )
print_data_by_gender( 'lines' )

print( '' )
print '--- DONE ---'
