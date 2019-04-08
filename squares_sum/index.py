squares = []
# sumnum = input( "What is the sum num? " )
# sumnum = int( sumnum )
sumnum = 49
if 0 > sumnum:
    print( "Need an number, dummy" )
    exit

print( "Number: {}".format( sumnum ) )
for n in range( 1, int( sumnum ) ):
    sq = n ** 2
    if sq >= sumnum:
        break
    squares.append( sq )

for sq in squares:
    print( sq )
