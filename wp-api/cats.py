#!/usr/bin/python
import lib
import requests
import yaml

# domain name could be passed from CLI as parameter, but this is easy enough to edit for now
site_domain = 'domain.com'

def get_cats():
  url = '{}/{}/categories'.format( lib.api_base, lib.site_id )
  result = lib.get_api_data( url, 'json' )
  if False != result:
    return result
  else:
    return {}

lib.site_id = lib.get_site_id( site_domain )
lib.access_token = lib.get_access_token()

cats = get_cats()
print yaml.safe_dump( cats )

# test
