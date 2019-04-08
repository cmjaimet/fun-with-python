import data

__ability_data = {}

def list():
    global __ability_data
    if {} == __ability_data:
        __ability_data = data.get_json_file( '../data/abilities.json' )
    return __ability_data
