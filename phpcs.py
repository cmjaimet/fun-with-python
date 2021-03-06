from os import walk
from re import findall, sub, split, search
import pprint


def get_code_files( folder ):
    allfiles = []
    for ( dirpath, dirnames, filenames ) in walk( folder ):
        for fname in filenames:
            if '.php' == fname[-4:]:
                allfiles.append( dirpath + '/' + fname )
    return allfiles

def get_code_from_file( path ):
    text = open( path ).read()
    return text

def get_classes( text ):
    return findall( r'\n\s*class[^\{]+\{', text )

def count_classes( text ):
	classes = get_classes( text )
	count = len( classes )
	return count

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
        if ( '' != key ):
            if 2 == len( keyval ):
                val = keyval[1]
            else:
                val = None
            args.append( { key: val } )
    return args

def display_functions( fns, thresholds, flagged_only ):
    count = 0
    output = ''
    output += 'title' + ( ' ' * 30 ) + "lines  blocks  flagged\n"
    output += ( '-' * 57 ) + "\n"
    for fn in fns:
        flag = is_function_flagged( fn, thresholds )
        if ( flagged_only and flag or not flagged_only ):
            output += fn['title']
            output += ( ' ' * ( 40 - len( fn['title'] ) - len( str( fn['lines'] ) ) ) )
            output += str( fn['lines'] )
            output += ( ' ' * ( 8 - len( str( fn['blocks'] ) ) ) )
            output += str( fn['blocks'] )
            output += '       '
            output += ( '**' if flag else '' )
            output += "\n"
            count += 1
    return output if ( 0 < count ) else ' -- No code flags'

def is_function_flagged( fn, thresholds ):
    if ( fn['lines'] > thresholds['lines'] or fn['blocks'] > thresholds['blocks'] ):
        return True
    else:
        return False

def get_file_details( text, thresholds ):
	class_count = count_classes( text )
	static_method_calls = len( findall( '::', text ) )
	train_wrecks = get_train_wrecks( text, thresholds['train_length'] )
	return {
		'classes': class_count,
		'train_wrecks': train_wrecks,
		'static_method_calls': static_method_calls
	}

def get_train_wrecks( text, links ):
	# text = sub( r'\([^\)]+\)', '()', text )
	# fails on text in ()
	text = sub( r'\t+', ' ', text )
	print(text)
	trains = findall( r'(([0-9a-zA-Z$_\(\)\[\]]+\-\>){' + str( links ) + ',9}[0-9a-zA-Z$_\(\)\[\]]+)', text )
	# count = len( trains )
	pp.pprint(trains)
	output = []
	for train in trains:
		output.append(train[0])
	return output

pp = pprint.PrettyPrinter(indent=4)

folder = './repo'
thresholds = {
    'lines': 70,
    'blocks': 5,
	'train_length': 2
}

repo_files = get_code_files( folder )
repo_files = [ './repo/functions/utility.php' ]

file_list = []
for fname in repo_files:
	# print( '' )
	# print( fname )
	code = get_code_from_file( fname )
	code = cleanse_code( code )
	file_general = get_file_details( code, thresholds )
	fns = get_function_list( code)
	function_details = get_function_details( fns )
	# print( display_functions( function_details, thresholds, True ) )
	file_list.append( {
		'name': fname,
		'functions': function_details,
		'general': file_general
	} )

# pp.pprint( file_list ) - this is old
# more
#

for f in file_list:
	print( f['name'] )
	pp.pprint( f['general'] )
