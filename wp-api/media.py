#!/usr/bin/python
import lib
import requests
import datetime
from datetime import date
import yaml

# domain name could be passed from CLI as parameter, but this is easy enough to edit for now
site_domain = 'domain.com'

def get_media( mime_type, after, before, fields, offset, number ):
  if '' == after:
    after = date( 2000, 1, 1 )
  if '' == before:
    before = datetime.datetime.now()
  url_pattern = '{}/{}/media?mime_type={}&after={}&before={}&fields={}&offset={}&number={}'
  url = url_pattern.format( lib.api_base, lib.site_id, mime_type, after, before, fields, offset, number )
  result = lib.get_api_data( url, 'json' )
  if False != result:
    return result
  else:
    return {}

def list_oversized_images():
  max_width = 1000
  max_height = 750
  media = get_media( mime_type='image/jpeg', after='', before='', fields='ID,caption,URL,width,height', offset=0, number=100 )
  print media.get('found')
  # Check for oversized images
  for img in media.get('media'):
    if ( max_width < img.get('width') ) or ( max_height < img.get('height') ):
      print '{} w: {}, h: {}'.format( img.get('URL'), img.get('width'), img.get('height') )

lib.site_id = lib.get_site_id( site_domain )
lib.access_token = lib.get_access_token()

list_oversized_images()
