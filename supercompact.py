# Python is super compact if you don't mind compromising readability a bit
for x in range( 1, 51 ):
	print str( x ) + ': ' + ( 'fizz' if (0 == x % 3 ) else '' ) + ( 'buzz' if (0 == x % 5 ) else '' )
