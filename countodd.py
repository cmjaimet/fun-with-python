## Count the odd numbers in an array of integers without conditionals
## Inspired by https://edgecoders.com/coding-tip-try-to-code-without-if-statements-d06799eed231
data = [ 1, 4, 5, 9, 0, -1, 5 ]

count = 0;
list = ''
for num in data:
	count += num % 2
print 'There are ' + str( count ) + ' odd numbers in the array.'
