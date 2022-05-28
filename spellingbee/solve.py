import re
import sys

def get_letters():
    if 1 < len(sys.argv):
        output = sys.argv[1]
    if ( 7 == len( output ) ):
        qs = re.search( r"^([A-Z]{7})$", output )
        if qs:
            return output.lower()
    print( 'Run format: python3 solve.py IADNOPR' )
    quit()

def get_words_from_file( path ):
    text_file = open( path, "r" )
    words = text_file.read().splitlines()
    text_file.close()
    return words

def clean_words( words ):
    output = []
    rule = r"[a-z]{4,20}"
    for word in words:
        if ( re.match( rule, word ) ):
            output.append( word )
    return output

def get_words_by_all_letters( words, letters ):
    output = []
    rule = r"^["+letters+"]*"+letters[0]+"["+letters+"]*$"
    for word in words:
        if ( re.match( rule, word ) ):
            output.append( word )
    output.sort()
    return output





letters = get_letters()
words = get_words_from_file( './data/words.txt' )
words = clean_words( words )
words = get_words_by_all_letters( words, letters )

for word in words:
    print( word )
