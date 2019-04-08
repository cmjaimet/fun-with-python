import character_sheet_cli
import character_sheet_web
import character
import skills

def display_character( char, mode ):
    abilities = get_abilities( char )
    skills = get_skills( char )
    if "web" == mode:
        return character_sheet_web.display_character( char, abilities, skills ) # irl probably returning for an http request
    character_sheet_cli.display_character( char, abilities, skills )
    # could invert dependencies and call this module from the print module

def get_abilities( char ):
    data = {
        "fields": [ 'Ability', 'Score', 'Bonus' ],
        "values": []
    }
    for ability in char["abilities"]:
        data["values"].append(
            [
                ability,
                char["abilities"][ ability ],
                character.get_ability_bonus( ability, char, 0, True )
            ]
        )
    return data

def get_skills( char ):
    skill_data = skills.list()
    data = {
        "fields": [ 'Skill', 'Bonus' ],
        "values": []
    }
    for skill, ability in skill_data.items():
        data["values"].append(
            [
                skill,
                character.get_ability_bonus( ability, char, 0, True )
            ]
        )
    return data
