#!/usr/bin/python
import sys
import re

def get_script_name( script_name = None ):
    if None != script_name:
        if 'http' == script_name[:4]:
            url_parts = script_name.split( '/' )
            name = url_parts.pop()
            name = re.sub( '.html', '', name )
            return name

    else:
        if 2 != len( sys.argv ):
            print( "Use one and only one argument defining the movie name" )
            quit()
        return sys.argv[1]

def get_text( path ):
    text_file = open( path, "r" )
    text = text_file.read()
    text_file.close()
    return text
