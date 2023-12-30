import pprint


def print_head():
    print("Solve Boggle")
    print("------------")
    print("")

def print_foot():
    print("")
    print("-----------------------")
    print("")


pp = pprint.PrettyPrinter(indent=4)
letters = [
    [ 'A', 'H', 'T', 'I' ],
    [ 'S', 'E', 'T', 'H' ],
    [ 'A', 'T', 'E', 'R' ],
    [ 'I', 'E', 'B', 'N' ]
]

print_head()

pp.pprint( letters )

print( letters[2][3] )

print_foot()
