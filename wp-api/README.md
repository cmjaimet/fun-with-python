# Wordpress API Tools

## Some Python scripts to help assess and manage users, .posts, media, and terms

* Note: You can do a LOT of damage to your Wordpress site with these tools. DO NOT use them unless you understand clearly how they work, and you have run some careful tests in advance. They are my scripts which I am simply putting in the public space without warranty, and so forth. Caveat downloader.*

Provided under GNU license. Do what you like with these files.

All these scripts run from the command line and require some setup. Notes will be added below shortly.

posts.py
usage:
python posts.py
ATM returns a list of posts with width>1000 or height>750 (get_oversized_images())
