#!/usr/bin/python
import requests
import sys
import re

script_base_url  = "https://www.imsdb.com/scripts/"
script_base_path = "./data/script_raw/"

def get_script_url( name ):
    return script_base_url + name + ".html"

def get_script_path( name ):
    return script_base_path + name + ".html"

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

def get_script_name():
    if 2 != len( sys.argv ):
        print( "Use one and only one argument defining the movie name" )
        quit()
    return sys.argv[1]

def get_script():
    script_name = get_script_name()
    url = get_script_url( script_name )
    path = get_script_path( script_name )
    text = get_script_text( url )
    text = clean_script_text( text )
    save_text( path, text )



get_script()
