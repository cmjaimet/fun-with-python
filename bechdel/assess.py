#!/usr/bin/python
import re
import json
import pprint
from string import punctuation

pp = pprint.PrettyPrinter(indent=4)

def get_text( path ):
    text_file = open( path, "r" )
    text = text_file.read()
    text_file.close()
    return text

def set_text( path, text ):
    text_file = open( path, "w" )
    text_file.write( text )
    text_file.close()
    return True

def save_blocks_as_text( blocks, path ):
    text = ''
    for block in blocks:
        text += '>> ' + block['title'] + "\n"
        text += block['body'] + "\n"
    set_text( path, text )

def get_script_characters( units ):
    characters = {}
    for unit in units:
        # print( unit['title'] )
        if ( unit['title'] not in characters ):
            characters[ unit['title'] ]  = {
                'words': unit['words'],
                'lines': 1
            }
        else:
            characters[ unit['title'] ]['words']  += unit['words']
            characters[ unit['title'] ]['lines']  += 1
            # characters.append( unit['title'] => unit['words'] )
    return characters

def get_script_blocks( text ):
    units = []
    blocks = text.split( '<b>' )
    for block in blocks:
        pieces = block.split( '</b>' )
        if 2 > len( pieces ):
            continue
        title = pieces[0]
        body  = pieces[1]
        if ( None == re.search( r"^\s{10,50}[A-Z]", title ) ):
            continue # no reliable formatting
        body   = strip_stage_directions( body )
        title  = clean_text( title )
        body   = clean_text( body )
        if ( "" == title.strip() ):
            continue
        if ( "" == body.strip() ):
            continue
        words  = body.count( ' ' ) + 1
        units.append( {
            "title": title,
            "body": body,
            "words": words
        } )
    return units

def strip_stage_directions( text ):
    spaces = re.search( "^\s+", text )
    if None == spaces:
        return text
    indent = spaces.span()[1] - 1
    if 0 < indent:
        text = re.sub( "\n\s{1," + str( indent ) + "}[^\s][^\n]+", "\n", text )
    return text

def clean_text( text ):
    text = re.sub( r'[\']+', '', text ) # contractions count as one word
    text = re.sub( r'\([^\)]*\)', ' ', text ) # remove stage directions
    text = re.sub( r'[' + punctuation + ']+', ' ', text ) # remove punctuation
    text = re.sub( r'\s+', ' ', text ) # merge whitespaces for counting
    text = text.strip()
    return text

def strip_punctuation( s ):
    return ''.join( c for c in s if c not in punctuation )

def get_cast_data( path ):
    # hardcode for Thor to test
    cast_json = get_text( path )
    return json.loads( cast_json )

def get_cast_from_script( characters ):
    cast = {}
    for character_name in characters.keys():
        cast[ character_name ] = { "gender": "N" }
    return cast
# print( json.dumps( get_cast_data( 'path' ) ) )
# quit()

script_name = 'Aliens'
# script_name = 'Thor-Ragnarok'
path_in  = "./data/script_raw/" + script_name + ".html"
path_out = "./data/script_clean/" + script_name + ".html"

text       = get_text( path_in )
blocks     = get_script_blocks( text )
characters = get_script_characters( blocks )

# # Get cast from script and json encode to put in file manually for now
# cast_temp = get_cast_from_script( characters )
# print( json.dumps( cast_temp ) )
# pp.pprint( cast_temp )
# quit()

save_blocks_as_text( blocks, path_out )

cast_data = get_cast_data( "./data/cast/" + script_name + ".json" )
names = characters.keys()
names.sort()

print( '' )
print( 'Cast:' )
print( 'G  Words  Lines  Character' )
print( '--------------------------' )
all_words = 0
all_lines = 0
all_cast = len( cast_data )
cast_by_gender = { 'M': 0, 'F': 0, 'N': 0 }
words_by_gender = { 'M': 0, 'F': 0, 'N': 0 }
lines_by_gender = { 'M': 0, 'F': 0, 'N': 0 }
for name in names:
    if name in cast_data:
        gender = cast_data[ name ]['gender']
        words  = characters[ name ]['words']
        lines  = characters[ name ]['lines']
        # print( gender + " - " + name + ": words " + str( words ) + ", lines " + str( lines ) )
        print( gender + str( words ).rjust( 7 ) + str( lines ).rjust( 7 ) + "  " + name )
        words_by_gender[ gender ] += words
        lines_by_gender[ gender ] += lines
        cast_by_gender[ gender ]  += 1
        all_words += words
        all_lines += lines

print( '' )
print( 'Cast by gender:' )
for gender in cast_by_gender.keys():
    people  = cast_by_gender[ gender ]
    percent = cast_by_gender[ gender ] * 100 / all_cast
    print( gender + ": " + str( people ).rjust( 7 ) + " " + str( percent ).rjust( 3 ) + "%" )

print( '' )
print( 'Words by gender:' )
for gender in words_by_gender.keys():
    words   = words_by_gender[ gender ]
    percent = words_by_gender[ gender ] * 100 / all_words
    print( gender + ": " + str( words ).rjust( 7 ) + " " + str( percent ).rjust( 3 ) + "%" )

print( '' )
print( 'Lines by gender:' )
for gender in lines_by_gender.keys():
    lines   = lines_by_gender[ gender ]
    percent = lines_by_gender[ gender ] * 100 / all_lines
    print( gender + ": " + str( lines ).rjust( 7 ) + " " + str( percent ).rjust( 3 ) + "%" )

print( '' )
print '--- DONE ---'
