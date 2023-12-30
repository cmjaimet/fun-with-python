import csv

def getBallots():
    ballots = {}
    with open('ballots.csv', newline='') as csvfile:
        csvreader = csv.reader(csvfile)
        for row in csvreader:
            countcol = 0
            for ranking in row:
                if ( 0 == countcol ):
                    key = ranking
                    ballots[key] = {}
                else:
                    ballots[key][candidates[countcol-1]] = int(ranking)
                countcol += 1
    return ballots

def countFirsts(ballots):
    firsts = {}
    for p in candidates:
        firsts[p] = 0
    for key in ballots:
        for cand in ballots[key]:
            if ( 1 == ballots[key][cand] ):
                firsts[cand] += 1
    return firsts

def findLoser( cands ):
    lowest = { "name": "", "votes": 100000000 }
    for p in cands:
        if ( cands[p] < lowest["votes"] ):
            lowest = { "name": p, "votes": cands[p] }
    return lowest

def dropLoser(ballots, loser):
    for key in ballots:
        for cand in ballots[key]:
            # loser["votes"]
            if ( cand == loser["name"]  ):
                ballots[key][cand] = 1000
            elif ( ballots[key][cand] > ballots[key][loser["name"]]  ):
                ballots[key][cand] -= 1
                print( ballots[key][cand] )
    return ballots


candidates = ["Joe","Sandy","Mary","Fred","Julie"]

ballotsRaw = getBallots()
# print(ballotsRaw)
# quit()

ballotsAdjusted = ballotsRaw

firsts = countFirsts(ballotsAdjusted)
# print(firsts)

loser = findLoser(firsts)
# print(loser)

ballotsAdjusted = dropLoser(ballotsAdjusted, loser)
print(ballotsAdjusted)
