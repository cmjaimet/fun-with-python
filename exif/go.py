import os
from os import listdir
from os.path import isfile, join
import shutil
from iptcinfo3 import IPTCInfo
import logging

iptcinfo_logger = logging.getLogger('iptcinfo')
iptcinfo_logger.setLevel(logging.ERROR)

def makeNewFileName( filepath, count ):
    extensions = ['.jpg']
    newfilepath = ""
    ext = getFileExtension( filepath )
    if ext in extensions:
        dict = getIPTCdata( filepath )
        # print(dict)
        newfilepath += dict['title']
        for kw in dict['keywords']:
            newfilepath += '-' + kw
        if "" != newfilepath:
            newfilepath += '-' + str(count)
            newfilepath += str(ext)
    return newfilepath

def getIPTCdata( filepath ):
    iptc = IPTCInfo( filepath )
    dict = { "title": "", "keywords": [] }
    try:
        dict["title"] = iptc['object name'].decode()
    except:
        print("")
    for kw in iptc['keywords']:
        try:
            dict["keywords"].append( kw.decode() )
        except:
            print("")
    return dict

def getFileExtension( filepath ):
    nam, ext = os.path.splitext( filepath )
    return ext

def getFileNames( mypath ):
    onlyfiles = [f for f in listdir(mypath) if isfile(join(mypath, f))]
    return onlyfiles

filecount = 1
srcfolder = './src/'
dstfolder = './dest/'
filenames = getFileNames( srcfolder )
for srcfilename in filenames:
    srcpath = srcfolder + srcfilename
    # print(srcpath)
    dstfilename = makeNewFileName( srcpath, filecount )
    if "" != dstfilename:
        dstpath = dstfolder + dstfilename
        shutil.copyfile(srcpath, dstpath)
        print("File created: ", dstfilename)
        filecount += 1
    else:
        print("FAILED: ", srcfilename)


quit()


# IPTC data:
# {'supplemental category': [], 'keywords': [b'scan', b'dog', b'apple', b'guy with hat'], 'contact': [], 'caption/abstract': b'this is good descr', 'writer/editor': b'Charles', 'headline': b'Awesome headline', 'special instructions': b'the instructions', 'by-line': b'Megan Goodacre', 'by-line title': b'My best pic', 'credit': b'credit by me', 'source': b'photossssshop', 'object name': b'The best pic', 'date created': b'20231226', 'time created': b'151801', '': b'the job', '': b'Do not copy'}
# Exif data:
#
