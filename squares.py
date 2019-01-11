# Given a number, determine what combination of squares sum to it
# e.g. 35 = 25 + 9 + 1

def print_array( ary ):
    output = ""
    for num in ary:
        output += "{}, ".format( num )
    print( output )

def sum_array( ary ):
    ary_sum = 0
    for elem in ary:
        ary_sum += elem
    return ary_sum

def get_squares( total ):
    squares = []
    for num in range( 1, total ):
        sq = num ** 2
        if total < sq:
            break
        squares.append( sq )
    squares.reverse()
    return squares

def get_match( ary ):
    for pos in range( 0, len( ary ) ):
        check_array( next_array( list( ary ), pos ) )

def next_array( ary, pos ):
    del ary[ pos ]
    return list( ary )

def check_array( ary, total ):
    print( total)
    if 0 < len( ary ):
        print_array( ary )
        total_array = sum_array( list( ary ) )
        if total == total_array:
            print( "SUCCESS: {} could be summed from these squares".format( total ) )
            print_array( ary )
            quit()
        elif total < total_array:
            get_match( ary )

def init():
    total = input( "Enter a number: " )
    total = int( total )
    if 0 < total:
        squares = get_squares( total )
        check_array( list( squares ), total )
        print( "FAILURE: {} could not be summed from squares.".format( total ) )

init()
