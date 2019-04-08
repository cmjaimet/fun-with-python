#!/usr/bin/python
import requests

def get_site_id( api_base, domain ):
  url_site = '{}/{}/'.format( api_base, domain )
  response = requests.get( url_site )
  result = response.json()
  site_id = result.get('ID')
  return site_id

def get_access_token( config ):
  url = 'https://{}/oauth2/token'.format( config.get( 'wordpress' ).get( 'api_domain' ) )
  heads = {}
  data = {
    'grant_type': 'password',
    'client_id': config.get( 'wordpress' ).get( 'client_id' ),
    'client_secret': config.get( 'wordpress' ).get( 'secret' ),
    'username': config.get( 'wordpress' ).get( 'uid' ),
    'password': config.get( 'wordpress' ).get( 'pwd_app' )
  }
  response = requests.post( url, data, headers=heads )
  result = response.json()
  return result.get('access_token')

def get_api_data( url, frmt, access_token ):
  heads = {'Authorization': 'Bearer {}'.format( access_token ) }
  response = requests.get( url, headers=heads )
  if 200 == response.status_code:
    if 'json' == frmt:
      result = response.json()
      return result
    else:
      return response
  else:
    return False

def get_posts( config, type, status, offset, number, after, before, fields, category ):
	api_base = 'https://{}/rest/v1.1/sites'.format( config.get( 'wordpress' ).get( 'api_domain' ) )
	site_id = get_site_id( api_base, config.get( 'wordpress' ).get( 'site_domain' ) )
	access_token = get_access_token( config )
	url_pattern = '{}/{}/posts?type={}&status={}&offset={}&number={}&category={}'
	if '' != fields:
		url_pattern += '&fields=' + fields
	if '' != after:
		url_pattern += '&after=' + after
	if '' != before:
		url_pattern += '&before=' + before
	url = url_pattern.format( api_base, site_id, type, status, offset, number, category )
	result = get_api_data( url, 'json', access_token )
	if False != result:
		return result
	else:
		return {}
