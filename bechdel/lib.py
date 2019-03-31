#!/usr/bin/python
import sys

def get_script_name():
    if 2 != len( sys.argv ):
        print( "Use one and only one argument defining the movie name" )
        quit()
    return sys.argv[1]
