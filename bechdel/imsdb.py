#!/usr/bin/python
import requests
import re
# import json
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

def clean_script( text ):
    ary1 = text.split( '<td class="scrtext">' )
    ary2 = ary1[1].split( '</pre>' )
    text = ary2[0]
    text = re.sub( '<pre>', '', text )
    # blocks = re.search( r'<b>[^\<]+</b>[^\<]+', text )
    blocks = text.split( '<b>' )
    units = []
    for block in blocks:
        pieces = block.split( '</b>' )
        title  = pieces[0]
        if ( "" == title.strip() ):
            continue
        if ( None == re.search( r"^\s{10,50}[A-Z]", title ) ):
            continue # no reliable formatting
        # print( title )
        # quit()
        title  = clean_text( title )
        body   = clean_text( pieces[1] ) if 2 <= len( pieces ) else ''
        # only retain content from lines starting with "\n\s{10}[^\s]"
        body   = clean_text( body )
        words  = body.count( ' ' ) + 1
        units.append( {
            "title": title,
            "body": body,
            "words": words
        } )
    # pp.pprint( units )
    characters = []
    for unit in units:
        print( unit['title'] )
        if ( unit['title'] not in characters ):
            characters.append( unit['title'] )
    pp.pprint( characters )
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

url      = "https://www.imsdb.com/scripts/Thor-Ragnarok.html"
path_in  = "in.html"
path_out = "out.html"

text     = get_text( path_in )
text     = clean_script( text )
set_text( path_out, text )

# print yaml.safe_dump( repo_list, default_flow_style=False )
# print text
print '--- DONE ---'
