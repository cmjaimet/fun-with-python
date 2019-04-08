import data

__class_data = {}

def list():
    global __class_data
    if {} == __class_data:
        __class_data = data.get_json_file( '../data/classes.json' )
    return __class_data
