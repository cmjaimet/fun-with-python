#!/usr/bin/python
import requests
import json

def get_json_file( path ):
    response = open( path ).read()
    result = json.loads( response )
    return result
