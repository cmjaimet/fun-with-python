#!/usr/bin/python
import requests
import json
import sys
import yaml
import re

def get_post_id( post_url, api_key ):
	url = 'https://www.facebook.com/plugins/feedback.php?href={}&api_key={}'.format( post_url, api_key, )
	response = requests.get( url )
	content = response._content
	match = re.search( '"targetFBID":"([0-9]+)"', content )
	return match.group(1)

def get_comments( url_base, fb_token, fb_post_id, offset, limit ):
	url = fb_post_id + "/comments/?fields=id,permalink_url,message,created_time,parent,from{id,name}"
	return get_fb_data( url_base, fb_token, url, offset, limit )

def get_fb_data( url_base, fb_token, path, offset, limit ):
	url = '{}/{}&filter=stream&offset={}&limit={}&access_token={}'.format( url_base, path, offset, limit, fb_token )
	# print url
	response = requests.get( url )
	if 200 == response.status_code:
		result = response.json()
		return result
	else:
		print '* * * Facebook API unreachable * * '
		quit()
