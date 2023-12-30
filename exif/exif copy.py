# pip install iptcinfo
from iptcinfo3 import IPTCInfo
import pprint

pp = pprint.PrettyPrinter(indent=4)

filename = './images/img2.jpg'
info = IPTCInfo( filename )

# print(type(info))
# print(dir(info))
# ['__class__', '__del__', '__delattr__', '__dict__', '__dir__', '__doc__', '__eq__', '__format__', '__ge__', '__getattribute__', '__getitem__', '__gt__', '__hash__', '__init__', '__init_subclass__', '__le__', '__len__', '__lt__', '__module__', '__ne__', '__new__', '__reduce__', '__reduce_ex__', '__repr__', '__setattr__', '__setitem__', '__sizeof__', '__str__', '__subclasshook__', '__weakref__', '_data', '_enc', '_filename', '_filepos', '_fobj', 'blindScan', 'c_marker_err', 'collectIIMInfo', 'error', 'inp_charset', 'jpegScan', 'out_charset', 'packedIIMData', 'photoshopIIMBlock', 'save', 'save_as', 'scanToFirstIMMTag']
for (name, value) in info.data:
    print(name, value)
#
print(info['keywords'])
print(info)
print(info['caption/abstract'])
# data:	{'supplemental category': [], 'keywords': [b'scan', b'dog', b'apple', b'guy with hat'], 'contact': [], 'caption/abstract': b'this is good descr', 'writer/editor': b'Charles', 'headline': b'Awesome headline', 'special instructions': b'the instructions', 'by-line': b'Megan Goodacre', 'by-line title': b'My best pic', 'credit': b'credit by me', 'source': b'photossssshop', 'object name': b'The best pic', 'date created': b'20231226', 'time created': b'151801', 'original transmission reference': b'the job', 'copyright notice': b'Do not copy'}
quit()

# from PIL import Image
# from PIL.ExifTags import TAGS
# import pprint
# pp = pprint.PrettyPrinter(indent=4)
#
# # rename images to folder-name-keywords
#
# # with open("./images/img1.jpg", "rb") as file1:
# #     image1 = Image(file1)
#
# # with open("./images/img2.jpg", "rb") as file2:
# #     image2 = Image(file2)
# # image2 = Image.open(rb"./images/img1.jpg")
#
# # images = [ image2 ]
#
# # path to the image or video
# imagename = "images/img2.jpg"
#
# # read the image data using PIL
# image = Image.open(imagename)
#
# # extract other basic metadata
# info_dict = {
#     "Filename": image.filename,
#     "Image Size": image.size,
#     "Image Height": image.height,
#     "Image Width": image.width,
#     "Image Format": image.format,
#     "Image Mode": image.mode,
#     "Image is Animated": getattr(image, "is_animated", False),
#     "Frames in Image": getattr(image, "n_frames", 1)
# }
#
# print("Basic data")
# for label,value in info_dict.items():
#     print(f"{label:25}: {value}")
#
# print("Info data")
# pp.pprint( image.info )
# quit()
# # for label,value in image.info:
# #     print(f"{label:25}: {value}")
#
#
#
# print("Exif data")
# exifdata = image.getexif()
# # iterating over all EXIF data fields
# for tag_id in exifdata:
#     # get the tag name, instead of human unreadable tag id
#     tag = TAGS.get(tag_id, tag_id)
#     data = exifdata.get(tag_id)
#     # decode bytes
#     if isinstance(data, bytes):
#         data = data.decode()
#     print(f"{tag:25}: {data}")
# #
# # for index, image in enumerate(images):
# #     if image.has_exif:
# #         status = f"contains EXIF (version {image.exif_version}) information."
# #     else:
# #         status = "does not contain any EXIF information."
# #     print(f"Image {index} {status}")
# # # test.png => location_of_image
