import requests
import pprint
pp = pprint.PrettyPrinter( indent=4 )

# Returns integer value if set and array 1-9 if not
def get_board( path ):
    raw = open( path ).read()
    rows = raw.split("\n")
    board = []
    unknown = list( range( 1,9 ) )
    for row in rows:
        cells = row.split(',')
        nums = []
        for cell in cells:
            nums.append( unknown if cell == '' else int( cell ) )
        board.append( nums )
    return board

def show_board( board ):
    display = ''
    for row in range( 0, 8 ):
        for col in range( 0, 8 ):
            display += ( str( board[ row ][ col ] ) if is_cell_solved( board[ row ][ col ] ) else '-' ) + ' '
        display += "\n"
    print( display )

def set_cell_options( board, row, col ):
    cell = board[ row ][ col ]
    if ( False == is_cell_solved( cell ) ):
        cell = check_cell_row( board, cell, row, col )
        cell = check_cell_col( board, cell, row, col )
        cell = check_cell_box( board, cell, row, col )
    return cell

# remove from cell any solved values in the row
def check_cell_row( board, cell, row, col ):
    for x in range( 0, 8 ):
        if ( is_cell_solved( board[ row ][ x ] ) ):
            # remove board[ row ][ x ] from cell
            try:
                cell.remove( board[ row ][ x ] )
            except:
                continue
    return cell

# remove from cell any solved values in the col
def check_cell_col( board, cell, row, col ):
    for x in range( 0, 8 ):
        if ( is_cell_solved( board[ x ][ col ] ) ):
            # remove board[ row ][ x ] from cell
            try:
                cell.remove( board[ x ][ col ] )
            except:
                continue
    return cell

# remove from cell any solved values in the box
def check_cell_box( board, cell, row, col ):
    coord = get_box_coord( row, col )
    for r in range( 0, 2 ):
        for c in range( 0, 2 ):
            if ( is_cell_solved( board[ coord[0] + r ][ coord[1] + c ] ) ):
                try:
                    cell.remove( board[ coord[0] + r ][ coord[1] + c ] )
                except:
                    continue
    return cell

def get_box_coord( row, col ):
    r = int( row / 3 ) * 3
    c = int( col / 3 ) * 3
    return [ r, c ]

def is_cell_solved( cell ):
    if isinstance( cell, int ):
        return True
    else:
        return False

path = './games/001.csv'
board = get_board( path )

show_board( board )

cell_value = set_cell_options( board, 0, 0 )
print( cell_value )
cell_value = set_cell_options( board, 6, 0 )
print( cell_value )
