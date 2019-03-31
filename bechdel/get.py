#!/usr/bin/python
import requests
import re
import json
from string import punctuation
import lib

script_base_url  = "https://www.imsdb.com/scripts/"

def get_script_url( name ):
    return script_base_url + name + ".html"

def get_script_path( folder, filename, ext ):
    return "./data/" + folder + "/" + filename + "." + ext

def get_script_text( url ):
    text = ''
    r = requests.get( url )
    if 200 == r.status_code:
        text = r.text
    return text

def save_text( path, text ):
    text_file = open( path, "w" )
    text_file.write( text )
    text_file.close()
    return True

def clean_script_text( text ):
    header = text.split( '<td class="scrtext">' )
    if 2 > len( header ):
        return text
    body = header[1].split( '</pre>' )
    if 2 > len( body ):
        return header[1]
    text = body[0]
    text = re.sub( '<pre>', '', text )
    return text

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

def format_blocks_as_text( blocks ):
    text = ''
    for block in blocks:
        text += '>> ' + block['title'] + "\n"
        text += block['body'] + "\n"
    return text

def get_script_characters( units ):
    characters = {}
    for unit in units:
        if ( unit['title'] not in characters ):
            characters[ unit['title'] ]  = {
                'words': unit['words'],
                'lines': 1
            }
        else:
            characters[ unit['title'] ]['words']  += unit['words']
            characters[ unit['title'] ]['lines']  += 1
    return characters

def get_cast_from_script( characters ):
    cast = {}
    for key, value in characters.items():
        cast[ key ] = {
            'gender': '',
            'words': value['words'],
            'lines': value['lines'],
        }
    return cast

def get_script():
    script_name = lib.get_script_name()
    url = get_script_url( script_name )
    text = get_script_text( url )
    text = clean_script_text( text )
    save_text( get_script_path( 'script_raw', script_name, 'html' ), text )
    blocks     = get_script_blocks( text )
    characters = get_script_characters( blocks )
    text_formatted = format_blocks_as_text( blocks )
    save_text( get_script_path( 'script_clean', script_name, 'html' ), text_formatted )
    blocks_json = json.dumps( blocks )
    save_text( get_script_path( 'blocks', script_name, 'json' ), blocks_json )
    cast = get_cast_from_script( characters )
    cast_json = json.dumps( cast )
    save_text( get_script_path( 'cast', script_name, 'json' ), cast_json )



get_script()
