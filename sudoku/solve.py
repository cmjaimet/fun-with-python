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
        # remove any solved values from this cell's options
        if ( is_cell_solved( board[ row ][ x ] ) ):
            # remove board[ row ][ x ] from cell
            try:
                cell.remove( board[ row ][ x ] )
            except:
                continue
    # placeholder for bigger solution
    # if this cell has n options and there are n total cells with the same options then remove those options from all other cells in the row
    # variation is that if there are n cells with a total overlap of n values then do the same
    # rarely happens in more than four cells so maybe treat each separately to start then look for simplification
    cell_size = len( cell )
    if ( 2 == cell_size ):
        col2 = -1
        for x in range( 9 ):
            if board[ row ][ x ] == cell and not x == col:
                col2 = x
                break
        if -1 < col2:
            for x in range( 9 ):
                # remove all values in cell from all cells in row except these two
                if not col == x and not col2 == x:
                    try:
                        # print( row, col, x, y, cell[0])
                        board[ row ][ x ].remove( cell[ 0 ] )
                        board[ row ][ x ].remove( cell[ 1 ] )
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
    cell_size = len( cell )
    if ( 2 == cell_size ):
        row2 = -1
        for x in range( 9 ):
            if board[ x ][ col ] == cell and not x == row:
                row2 = x
                break
        if -1 < row2:
            for x in range( 9 ):
                # remove all values in cell from all cells in row except these two
                if not row == x and not row2 == x:
                    try:
                        # print( row, col, x, y, cell[0])
                        board[ x ][ col ].remove( cell[ 0 ] )
                        board[ x ][ col ].remove( cell[ 1 ] )
                    except:
                        continue
    return cell

# remove from cell any solved values in the box
def check_cell_box( board, cell, row, col ):
    box_coord = get_box_coord( row, col )
    for r in range( box_coord[0], box_coord[0] + 3 ):
        for c in range( box_coord[1], box_coord[1] + 3 ):
            # print( box_coord[0], row_curr, r)
            if ( is_cell_solved( board[ r ][ c ] ) ):
                try:
                    cell.remove( board[ r ][ c ] )
                except:
                    continue
    cell_size = len( cell )
    if ( 2 == cell_size ):
        row2 = -1
        col2 = -1
        for r in range( box_coord[0], box_coord[0] + 3 ):
            for c in range( box_coord[1], box_coord[1] + 3 ):
                # row_curr = box_coord[0] + r
                # col_curr = box_coord[1] + c
                if board[ r ][ c ] == cell and not r == row and not c == col:
                    row2 = r
                    col2 = c
                    break
        if -1 < row2:
            for r in range( box_coord[0], box_coord[0] + 3 ):
                for c in range( box_coord[1], box_coord[1] + 3 ):
                    # row_curr = box_coord[0] + r
                    # col_curr = box_coord[1] + c
                    if ( row == r and col == c ) or ( row2 == r and col2 == c ):
                        continue
                    else:
                        try:
                            board[ r ][ c ].remove( cell[ 0 ] )
                            board[ r ][ c ].remove( cell[ 1 ] )
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
    max_iterations = 10
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
