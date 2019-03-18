from re import findall, sub, split, search
import pprint

path_in  = './repo/php_in.php'
path_out = './repo/php_new.php'

# stuff
# ok 
def get_php( path ):
    text_file = open( path, "r" )
    text = text_file.read()
    text_file.close()
    return text

def set_php( path, text ):
    text_file = open( path_out, "w" )
    text_file.write( php_out )
    text_file.close()
    return True

def strip_indentation( text ):
    text = sub( r'\n\s+', '\n', text )
    return text

def strip_comments( text ):
    text = sub( r'\/\/[^\n]*\n', "\n", text) # single-line comments
    text = strip_comment_blocks( text )
    return text

def strip_comment_blocks( text ):
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

def strip_multiple_spaces( text ):
    text = sub( r'\s+', ' ', text )
    return text

def strip_blank_lines( text ):
    text = sub( r'\n+', '\n', text )
    return text

def strip_non_php( text ):
    text = sub( r'\?\>.*?\<\?php', '', text )
    return text

def strip_endif( text ):
    text = sub( r'\)\s*\:([^\:])', ') {\1', text ) # not great
    text = sub( r'(endif|endswitch|endwhile|endfor);', '}', text )
    return text

def format_lines( text ):
    text = sub( ';', ';\n', text )
    text = sub( '}', '}\n', text )
    text = sub( '{', '{\n', text )
    return text

php_in = get_php( path_in )
php_out = php_in

# strip indentation
php_out = strip_indentation( php_out )
# strip comments
php_out = strip_comments( php_out )
# strip blank lines
php_out = strip_blank_lines( php_out )
# convert multiple spaces to one
php_out = strip_multiple_spaces( php_out )
# strip out non-PHP
php_out = strip_non_php( php_out )
php_out = strip_endif( php_out )
# reformat into lines
php_out = format_lines( php_out )



set_php( path_out, php_out )
