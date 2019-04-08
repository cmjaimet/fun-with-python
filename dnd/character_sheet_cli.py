from prettytable import PrettyTable

def display_character( char, abilities, skills ):
    print( "" )
    print( "Class: {}".format( char["class"] ) )
    print( "Race: {}, {}".format( char["race"], char["subrace"] ) )
    make_table( abilities, True )
    make_table( skills, True )

def make_table( data, display ):
    table = PrettyTable()
    table.field_names = data["fields"]
    table.align[ data["fields"][0] ] = "l"
    for value in data["values"]:
        table.add_row( value )
    if display:
        print( table )
        return
    return table
