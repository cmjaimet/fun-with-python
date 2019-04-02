import re
from string import punctuation

import pprint
pp = pprint.PrettyPrinter(indent=4)

def strip_stage_directions( text ):
    spaces = re.search( "^\s+", text )
    if None == spaces:
        return text
    indent = spaces.span()[1] - 2
    if 0 < indent:
        text = re.sub( "\n\s{1," + str( indent ) + "}[^\s][^\n]+", "\n", text )
    return text

def clean_text( text ):
    text = "\n" + text + "\n"
    text = re.sub( r'[\']+', '', text ) # contractions count as one word
    text = re.sub( r'\([^\)]*\)', ' ', text ) # remove (stage directions)
    text = re.sub( r'\n[ \t]*\n', '\n', text ) # remove blank lines
    text = re.sub( r'[' + punctuation + ']+', ' ', text ) # remove punctuation
    text = strip_stage_directions( text )
    text = re.sub( r'\s+', ' ', text ) # merge whitespaces for counting
    text = text.strip()
    return text

text = '                       (louder) \n                 His name is Robert Paulson!  His name\n                 is Robert Paulson...\n\n     Jack backs away, surrounded, PUSHES his way out of the room.\n\n'

print( text)
print '----'
done = clean_text( text )
print '===='
print( done)
