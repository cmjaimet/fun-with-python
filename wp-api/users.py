#!/usr/bin/python
# -*- coding: utf-8 -*-
import lib
import requests
import sys
import yaml

# Functions

def get_user( user_name ):
  url = '{}/{}/users/login:{}'.format( lib.api_base, lib.site_id, user_name )
  result = lib.get_api_data( url, 'json' )
  return result

def set_user_role( data, role ):
  data['roles'] = [ role ]
  user_id = data.get('ID')
  #url = '{}/{}/users/{}'.format( lib.api_base, lib.site_id, user_id )
  #result = lib.set_api_data( url, data )
  return result

# get the list of users to change roles on
def get_users():
  users = []
  with open( 'configs/users.txt', "r" ) as fout:
    for i, line in enumerate( fout ):
      user_name = line.strip()
      users.append( user_name )
  return users

def list_users():
  offset = 0
  partial = True
  result = []
  number = 20
  while partial:
    url = '{}/{}/users?offset={}&number={}'.format( lib.api_base, lib.site_id, offset, number )
    partial_data = lib.get_api_data( url, 'json' )
    if partial_data:
      # print offset
      partial = partial_data.get('users')
      if partial:
        result += partial
        offset += number
  return result

def get_sites():
  sites = []
  with open( 'configs/sites.txt', "r" ) as fout:
    for i, line in enumerate( fout ):
      sites.append( line.strip() )
  return sites

def change_users_roles( new_role ):
  confirm = input( 'Are you sure you want to change user roles? (yes/no)' )
  print('Nope ')
  if 'yes' == confirm:
    print('but you did say ', confirm)
  # Shut this off in code when not in use
  quit()
  users = get_users()
  for site_domain in sites:
    print 'Site: {}:'.format( site_domain )
    print '---------'
    lib.site_id = lib.get_site_id( site_domain )
    if None == lib.site_id:
      print 'Cannot access site'
      continue
    lib.access_token = lib.get_access_token()
    for user_name in users:
      user_data = get_user( user_name )
      if False != user_data:
        user_out = set_user_role( user_data, new_role )
        print 'SUCCESS: {} changed to {}'.format( user_name, new_role )
      else:
        print 'FAILED: {} not found'.format( user_name )
    print '---------'

def get_users_from_sites( role ):
  for site_domain in sites:
    print 'Site: {}'.format( site_domain )
    lib.site_id = lib.get_site_id( site_domain )
    if None == lib.site_id:
      print 'Cannot access site'
      continue
    lib.access_token = lib.get_access_token()
    users = list_users()
    with open( 'output/users/{}.txt'.format( site_domain ), "w" ) as fout:
      out = ''
      for user in users:
        if 0 < len( user.get('roles') ):
          urole = user.get('roles')[0].encode('utf-8')
        else:
          urole = ''
        if ( '' == role or role == urole ):
          line = '{},{},{},{}'.format( user.get('name').encode('utf-8'), user.get('login').encode('utf-8'), user.get('email').encode('utf-8'), urole )
          #line = '{}|{}'.format( user.get('name').encode('utf-8'), site_domain )
          out += line + "\n"
          #print line
      fout.write( out )
      fout.close()


# Code
for x in range( 0, 2 ):
  print ''

mode = ''
arguments = sys.argv[1:]
if 0 == len( arguments ):
  quit( 'Please select a mode to run (list, role)' )

mode = arguments[0]
role = ''
if ( 2 == len( arguments ) ):
  role = arguments[1]

sites = get_sites()

if 'list' == mode:
  # quit( 'list' )
  get_users_from_sites( role )
elif 'role' == mode:
  quit( 'role' )
  change_users_roles( 'author' )
else:
  quit( 'Please select a mode to run (list|role)' )

print ''
