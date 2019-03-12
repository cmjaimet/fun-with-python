from os import walk
from re import findall, sub, split, search
import pprint

def get_php_files( files, folder ):
    phpfiles = []
    for fname in files:
        if '.php' == fname[-4:]:
            phpfiles.append( folder + '/' + fname )
    return phpfiles

def get_code_files( root_folder ):
    f = []
    # // make recursive
    for (dirpath, dirnames, filenames) in walk( root_folder ):
        f.extend( get_php_files( filenames, root_folder ) )
        for folder2 in dirnames:
            folder2 = root_folder + '/' + folder2
            for ( dirpath2, dirnames2, filenames2) in walk( folder2 ):
                f.extend( get_php_files( filenames2, folder2 ) )
                # if dirnames2:
                    # // next level of recursion - not now
                break
        break
    return f

def get_code_from_file( path ):
    text = open( path ).read()
    return text

def count_classes( text ):
    return findall( r'\n\s*class[^\{]+\{', text )

def cleanse_code( text ):
    text = sub( r'\n\s*\/\/[^\n]*\n', "\n", text) # remove comment lines
    new_text = remove_comment_blocks( text )
    new_text = sub( r'\n\s*\n', "\n", new_text) # remove blank lines
    return new_text

def remove_comment_blocks( text ):
    new_text = ''
    letters = list( text )
    count = 0
    comment_on = False
    for letter in letters:
        if '/' == letter and '*' == letters[ count + 1 ]:
            comment_on = True
        if False == comment_on:
            new_text += letter
        if comment_on and '/' == letter and '*' == letters[ count - 1 ]:
            comment_on = False
        count += 1
    return new_text

def get_function_list( text ):
    fns = split( "\n\s*[a-z]*\s*function\s+", text )
    fns.pop(0)
    new_fns = []
    for fn in fns:
        letters = list( fn )
        brace_count = 0
        started = False
        new_fn = ''
        for letter in letters:
            if ( '{' == letter ):
                brace_count += 1
                if ( False == started ):
                    started = True
            elif ( '}' == letter ):
                brace_count -= 1
            new_fn += letter
            if ( True == started and 0 == brace_count ):
                break
        new_fns.append( new_fn )
    return new_fns

def get_function_details( fns ):
    new_fns = []
    for fn in fns:
        title = get_function_title( fn )
        args = get_function_args( fn )
        lines = get_function_line_count( fn )
        blocks = get_function_blocks( fn )
        fn_deets = {
            'title': title,
            'args': args,
            'lines': lines,
            'blocks': blocks,
            'chars': len( fn )
        }
        new_fns.append( fn_deets )
    return new_fns

def get_function_title( text ):
    x = search( "[^\(]+", text )
    return text[ x.start():x.end() ]

def get_function_line_count( text ):
    lines = findall( r'\n', text )
    return len( lines ) - 1

def get_function_blocks( text ):
    blocks = findall( '{', text )
    return len( blocks )

def get_function_args( text ):
    x = search( "\([^\)]*", text ) # first instance of text in brackets
    argtxt = text[ ( x.start() + 1 ):x.end() ].strip()
    argtxt = sub( "\s+", "", argtxt )
    pairs = split( ',', argtxt )
    args = []
    for pair in pairs:
        keyval = split( '=', pair )
        key = keyval[0]
        if 2 == len( keyval ):
            val = keyval[1]
        else:
            val = None
        args.append( { key: val } )
    return args



pp = pprint.PrettyPrinter(indent=4)
folder = './php_repo'
f = get_code_files( folder )
# print( f )
# for fname in f:
code = get_code_from_file( folder + '/classes/PostmediaLayoutsAdmin.php' )
code = cleanse_code( code )
classes = count_classes( code )
fns = get_function_list( code)
# print(code)
# pp.pprint(fns)
deets = get_function_details( fns )
pp.pprint(deets)
