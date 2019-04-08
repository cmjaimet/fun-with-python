#!/usr/bin/python
import lib
import requests
import datetime
import yaml

# domain name could be passed from CLI as parameter, but this is easy enough to edit for now
site_domain = 'domain.com'

def get_posts( type, status, offset, number, after, before, fields ):
  url_pattern = '{}/{}/posts?type={}&status={}&offset={}&number={}'
  if '' != fields:
    url_pattern += '&fields=' + fields
  if '' != after:
    url_pattern += '&after=' + after
  if '' != before:
    url_pattern += '&before=' + before
  #print url_pattern
  url = url_pattern.format( lib.api_base, lib.site_id, type, status, offset, number )
  result = lib.get_api_data( url, 'json' )
  if False != result:
    return result
  else:
    return {}

# custom post type used in Layouts plugin (private repo)
def get_layouts( typ ):
  # need to whitelist 'wlo_option' post type with rest_api_allowed_post_types filter
  posts = get_posts( type='wlo_option', status='publish' )
  meta_prefix = 'pmlayouts_lists_'
  meta_length = len( meta_prefix )
  for post in posts:
    print post.get('ID')
    for meta in post.get('metadata'):
      key = meta.get('key')
      if meta_prefix == key[:meta_length]:
        tax_id = key.replace( meta_prefix, '' ) # the taxonomy ID for the layouts data, meta.get('value') is the layouts data for this taxo
        print 'tax_id {}: {}'.format( tax_id, meta.get('value') )

def get_post_counts( interval, num ):
  start = datetime.datetime.now()
  before = start
  for count in range( 1, num ):
    days = count * interval
    after = start - datetime.timedelta( days=days )
    after_wp = after.isoformat()
    post_data = get_posts( type='post', status='publish', after=after_wp, before=before, fields='ID' )
    #posts = post_data.get('posts')
    found = post_data.get('found')
    print 'Published post count 7 days after {} : {}'.format( after.strftime("%d/%m/%y"), found )
    before = after_wp

def get_oversized_images():
  offset  = 0
  number = 100
  max_width = 1000
  max_height = 750
  post_data = get_posts( type='post', status='publish', offset=offset, number=number, after='', before='', fields='' )
  posts = post_data.get('posts')
  found = post_data.get('found')
  print 'Found: {}'.format( found )
  for post in posts:
    if None != post.get('post_thumbnail'):
      img_w = post.get('post_thumbnail').get('width')
      img_h = post.get('post_thumbnail').get('height')
      if max_width < img_w or max_height < img_h:
        print post.get('title')
        print post.get('URL')
        print yaml.safe_dump( post.get('post_thumbnail') )
        #print yaml.safe_dump( post )

lib.site_id = lib.get_site_id( site_domain )
lib.access_token = lib.get_access_token()

get_oversized_images()
