import data

__race_data = {}

def list():
    global __race_data
    if {} == __race_data:
        __race_data = data.get_json_file( '../data/races.json' )
    return __race_data
