import data

def get():
    return data.get_json_file( '../data/character.json' )

def get_ability_bonus( ability, char, proficiency, to_string = False ):
    score = char["abilities"][ ability ]
    bonus = int( ( score - 10 ) / 2 ) + proficiency
    if to_string:
        return bonus_to_string( bonus )
    return bonus

def bonus_to_string( bonus ):
    return '{}{}'.format( ( '+' if ( 0 <= bonus ) else '' ), bonus )
