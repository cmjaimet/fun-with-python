#!/usr/bin/python
import requests
import yaml

# Add your access information here
client_id = '12345'
secret    = 'insertsecrethere';
uid       = 'wpusername'
pwd_app   = 'password'

api_domain = 'https://public-api.wordpress.com'
api_base = '{}/rest/v1.1/sites'.format( api_domain )
access_token = ''
site_id = ''

def get_site_id( domain ):
  url_site = '{}/{}/'.format( api_base, domain )
  response = requests.get( url_site )
  result = response.json()
  site_id = result.get('ID')
  return site_id

def get_access_token():
  url = '{}/oauth2/token'.format( api_domain )
  heads = {}
  data = {
    'client_id': client_id,
    'client_secret': secret,
    'grant_type': 'password',
    'username': uid,
    'password': pwd_app
  }
  response = requests.post( url, data, headers=heads )
  result = response.json()
  return result.get('access_token')

def get_api_data( url, frmt ):
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

def set_api_data( url, data ):
  heads = {'Authorization': 'Bearer {}'.format( access_token ) }
  response = requests.post( url, data, headers=heads )
  result = response.json()
  return result
