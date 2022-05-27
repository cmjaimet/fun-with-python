import re
import sys

def get_letters():
    if 1 < len(sys.argv):
        output = sys.argv[1]
    if ( 7 == len( output ) ):
        print( output[6] )
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

def get_words_by_primary_letter( words, letter ):
    output = []
    rule = r".*"+letter+".*"
    for word in words:
        if ( re.match( rule, word ) ):
            output.append( word )
    return output

def get_words_by_all_letters( words, letters ):
    output = []
    rule = r"^["+letters+"]+$"
    for word in words:
        if ( re.match( rule, word ) ):
            output.append( word )
    return output

letters = get_letters()
print(letters)
print(letters[0])
words = get_words_from_file( './data/words.txt' )
words = clean_words( words )
words = get_words_by_primary_letter( words, letters[0] )
words = get_words_by_all_letters( words, letters )
for word in words:
    print( word )
quit()

rule = "aa[a-z]{10}"

with open( './data/words.txt' ) as words:
    for word in words:
        if ( re.match( rule, word, re.IGNORECASE ) ):
            print( word.replace( "\n", '' ) )
