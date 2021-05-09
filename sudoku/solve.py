import requests
import pprint
pp = pprint.PrettyPrinter( indent=4 )
path = './games/002.csv'

# Returns integer value if set and array 1-9 if not
def get_board( path ):
    raw = open( path ).read()
    rows = raw.split("\n")
    board = []
    for row in rows:
        cells = row.split(',')
        nums = []
        for cell in cells:
            nums.append( list( range( 1, 10 ) ) if cell == '' else int( cell ) )
        board.append( nums )
    return board

def show_board( board ):
    # pp.pprint( board )
    # quit()
    display = ''
    for row in range( 9 ):
        for col in range( 9 ):
            # print(row,col)
            # pp.pprint(board[ row ][ col ])
            display += ( str( board[ row ][ col ] ) if is_cell_solved( board[ row ][ col ] ) else '-' ) + ' '
        display += "\n"
    print( display )

def set_cell_options( board, row, col ):
    cell = board[ row ][ col ]
    if not is_cell_solved( cell ):
        # pp.pprint(cell)
        cell = check_cell_row( board, cell, row, col )
        cell = check_cell_col( board, cell, row, col )
        cell = check_cell_box( board, cell, row, col )
        # pp.pprint(cell)
        if ( 1 == len( cell ) ):
            # print(cell[ 0 ])
            return cell[ 0 ]
    return cell

# remove from cell any solved values in the row
def check_cell_row( board, cell, row, col ):
    # print(row,col)
    # pp.pprint(cell)
    for x in range( 9 ):
        if ( is_cell_solved( board[ row ][ x ] ) ):
            # remove board[ row ][ x ] from cell
            try:
                cell.remove( board[ row ][ x ] )
            except:
                continue
    return cell

# remove from cell any solved values in the col
def check_cell_col( board, cell, row, col ):
    for x in range( 9 ):
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
    for r in range( 3 ):
        for c in range( 3 ):
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

def is_board_solved( board ):
    for row in range( 9 ):
        for col in range( 9 ):
            if not is_cell_solved( board[ row ][ col ] ):
                return False
    return True

def solve_board( board, iteration ):
    max_iterations = 5
    for row in range( 9 ):
        for col in range( 9 ):
            # print( row, col )
            board[ row ][ col ] = set_cell_options( board, row, col )
        # pp.pprint( board )
        # quit()
    iteration += 1
    print( 'Iteration: ' + str( iteration ) )
    # keep solving if board is not solved and fewer than max_iterations have been executed
    if max_iterations > iteration and not is_board_solved( board ):
        board = solve_board( board, iteration )
    return board

board = get_board( path )
show_board( board )

board = solve_board( board, 0 )
# pp.pprint( board )
show_board( board )

# pp.pprint(set_cell_options( board, 0, 5 ))
