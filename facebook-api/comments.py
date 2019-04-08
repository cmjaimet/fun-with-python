import yaml
import urllib
import time
import datetime
import re
import wp_posts
import fb_comments

def get_config():
	config = {}
	with open( 'config.yaml', 'r' ) as stream:
	    try:
	        config = yaml.load( stream )
	    except yaml.YAMLError as exc:
	        print( exc )
	return config

def format_output( txt ):
	if txt is None:
		txt = ''
	elif type( txt ) is int:
		txt = str( txt )
	else:
		txt = txt.encode( 'ascii', 'backslashreplace' )
		txt = txt.replace( '"', "'" )
		txt = txt.replace( "\n", '' )
		txt = re.sub( "\<[^\>]+\>", '', txt )
	txt = '\"' + txt + '\"'
	return txt

def save_posts( post_id, post_url, post, csv_file ):
	post_path = re.sub( r"[a-z]+\:\/\/[^\/]+", "", post_url )
	post_id = format_output( post_id )
	post_path = format_output( post_path )
	post_title = format_output( post.get( 'title' ) )
	post_excerpt = format_output( post.get( 'excerpt' ) )
	post_date = get_timestamp( post.get( 'date' ) )
	post_modified = get_timestamp( post.get( 'modified' ) )
	csv_file.write( "{},{},{},{},{},{}\n".format( post_id, post_path, post_title, post_excerpt, post_date, post_modified ) )

def save_comments( post_id, post_url, data, csv_file, user_file ):
	post_id = format_output( post_id )
	comment_id = format_output( data.get( 'id' ) )
	content = format_output( data.get( 'message' ) )
	created_time = get_timestamp( data.get( 'created_time' ) )
	if None != data.get( 'parent' ):
		parent_id = format_output( data.get( 'parent' ).get( 'id' ) )
	else:
		parent_id = '""'
	if None != data.get( 'from' ):
		user_id = format_output( data.get( 'from' ).get( 'id' ) )
		user_name = format_output( data.get( 'from' ).get( 'name' ) )
	else:
		user_id = '""'
		user_name = '""'
	csv_file.write( "{},{},{},{},{},{}\n".format( comment_id, post_id, user_id, content, created_time, parent_id ) )
	save_users( user_id, user_name, user_file )

def save_users( user_id, user_name, csv_file ):
	user_id = format_output( user_id )
	user_name = format_output( user_name )
	user_id = user_id.replace( "'", '' )
	user_name = user_name.replace( "'", '' )
	csv_file.write( "{},{}\n".format( user_id, user_name ) )

def get_timestamp( dt ):
	dt = re.sub( r"[\+\-][0-9]{2}[\:]*[0-9]{2}", "", dt )
 	ts = int( time.mktime( datetime.datetime.strptime( dt, "%Y-%m-%dT%H:%M:%S" ).timetuple() ) )
	return '"' + str( ts ) + '"'

def open_csv( name ):
	path = config.get( name )
	return open( path, "w+" )

config = get_config()
if ( config is None ):
	quit()

# for future paging
before_wp = ''
after_wp = ''
post_offset = 0
post_limit = config.get( 'limit' )
category = config.get( 'wordpress' ).get( 'category' )
fields = 'ID,URL,title,excerpt,date,modified,metadata'
post_list = wp_posts.get_posts( config, type='post', status='publish', offset=post_offset, number=post_limit, after=after_wp, before=before_wp, fields=fields, category=category )


csv_posts = open_csv( 'posts_csv' )
csv_posts.write( "unique_page_id,page_path,page_title,page_description,page_date_created,page_last_modified\n" )

csv_comments = open_csv( 'comments_csv' )
csv_comments.write( "unique_comment_id,unique_page_id,unique_user_id,content,comment_date_created,parent_unique_comment_id\n" )

csv_users = open_csv( 'users_csv' )
csv_users.write( "unique_user_id,name\n" )

url_base = config.get( 'facebook' ).get( 'url_base' )
fb_token = config.get( 'facebook' ).get( 'token' )
post_id_meta_key = config.get( 'wordpress' ).get( 'id' )

for post in post_list.get( 'posts' ):
	post_url = post.get( 'URL' ).replace( 'http://', 'https://' )
	post_url = post_url.replace( '/full-comment/', '/opinion/' ) # hack for NP rewrites
	post_url_encoded = urllib.quote_plus( post_url.encode('utf-8') )
	post_id = post.get( 'ID' ) # default to WP post ID
	if ( '' != post_id_meta_key ):
		if ( None != post.get( 'metadata' ) ):
			for meta in post.get( 'metadata' ):
				if ( post_id_meta_key == meta.get( 'key' ) ):
					post_id = meta.get( 'value' )
					break
	save_posts( post_id, post_url, post, csv_posts )
	fb_post_id = fb_comments.get_post_id( post_url_encoded, config.get( 'facebook' ).get( 'api_key' ) )
	comments = fb_comments.get_comments( url_base, fb_token, fb_post_id, 0, 100 )
	if False != comments:
		for comment in comments.get( 'data' ):
			save_comments( post_id, post_url, comment, csv_comments, csv_users )

csv_posts.close()
csv_comments.close()
csv_users.close()
