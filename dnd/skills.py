import data

__skill_data = {}

def list():
    global __skill_data
    if {} == __skill_data:
        __skill_data = data.get_json_file( '../data/skills.json' )
    return __skill_data
