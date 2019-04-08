#!/usr/bin/python
import requests
import pprint
from datetime import datetime, timedelta

pp = pprint.PrettyPrinter( indent=4 )

days_review = 28
organization = "cmjaimet"

def get_from_api( url, paged=True ):
	headers = {'Authorization': 'token XXXXXXXXXXXXXXXX' }
	page = 1
	result = []
	partial_result = True
	if paged:
		while partial_result:
			# print(page)
			response = requests.get( "{}page={}".format( url, page ), headers=headers )
			partial_result = response.json()
			if partial_result:
				result += partial_result
				page += 1
	else:
		response = requests.get( url, headers=headers )
		result = response.json()
	return result

def get_teams():
	return get_from_api( "https://api.github.com/orgs/{}/teams?".format( organization ) )

def get_dev_team():
	teams = get_teams()
	for team in teams:
		if "Developers" == team["name"]:
			return team["id"]
	return ""

def get_users():
	# get users from team
	team_id = get_dev_team()
	users = get_from_api( "https://api.github.com/teams/{}/members?".format( team_id ) )
	data = []
	for user in users:
		data.append( user["login"] )
	return data

def get_repos():
	repos = get_from_api( "https://api.github.com/orgs/{}/repos?".format( organization ) )
	data = []
	for repo in repos:
		if "ARCHIVE" != repo["name"][:7] and "devops" != repo["name"][:6]:
			data.append( repo["name"] )
	return data

def get_commits( repos ):
	# all commits in a repo in a time period
	date_format = "%Y-%m-%dT%H:%M:%SZ"
	date_until = datetime.today() + timedelta( days=1 )
	date_since = datetime.today() - timedelta( days=days_review )
	until = date_until.strftime( date_format )
	since = date_since.strftime( date_format )
	sha_urls = []
	for repo in repos:
		print(repo)
		commits = get_from_api( "https://api.github.com/repos/{}/{}/commits?since={}&until={}&".format( organization, repo, since, until ) )
		for commit in commits:
			if commit["author"] and commit["author"]["login"] in users:
				sha_urls.append( commit["url"] )
	return sha_urls

def get_commit( url ):
	commit = get_from_api( url, False )
	return commit


users = get_users()
repos = get_repos()

user_commits = {}
for user in users:
	user_commits[ user ] = {
		"commits": 0,
		"additions": 0,
		"deletions": 0,
		"files": 0
		}
commits = get_commits( repos )


for url in commits:
	data = get_commit( url )
	user = data["author"]["login"]
	if "Merge pull request #" != data["commit"]["message"][:20] and "All Checks are Passed" != data["commit"]["message"][:21]:
		print(data["commit"]["message"])
		user_commits[ user ]["commits"] += 1
		user_commits[ user ]["additions"] += int( data["stats"]["additions"] )
		user_commits[ user ]["deletions"] += int( data["stats"]["deletions"] )
		user_commits[ user ]["files"] += len( data["files"] )

pp.pprint( user_commits )
