# Python is so compact!
for x in range( 1, 51 ):
	line = str( x ) + ': '
	if 0 == x % 3:
		line += 'fizz'
	if 0 == x % 5:
		line += 'buzz'
	print line

# done
