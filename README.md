ImagePacking
============

ImagePacking.php must be executed locally with php.exe.
This script take some images format on input and create some DDS files on output.

:warning: This require [ImageMagick v. 6.8.6-10](http://ftp.sunet.se/pub/multimedia/graphics/ImageMagick/binaries/ "ImageMagick v.6.8.6-10 Download page") set in $PATH, since the `convert` command is used inside the script. Check [configImagick.md](https://github.com/Magador/ImagePacking/blob/master/configImagick.md) for more informations.


Syntax: `ImagePacking.php <dirpath> [filetype [sizeofimage [outputprefix [bgcolor]]]]`

`dirpath`
The directory path containing images. Warning: if this directory contains directories containing images, those will be used too.

`filetype`
The file extension of images. Default set to 'png'. Formats available http://www.imagemagick.org/script/formats.php

`sizeofimage`
The size in pixels of the generated images, count as width and height. Default at 4096.

`outputprefix`
The prefix to be set for the generated images. Default at directory name.

`bgcolor`
The background color of the images to be processed. It also is used to set the background color of generated images. Default set to 'none' (transparent).

##Usage

For example we need to create textures from the images contained in the folder [images/phone](https://github.com/Magador/ImagePacking/tree/master/images/phone), in 2048x textures using a transparent background color. We want to name the .json and images prefixes as 'mobile'; so we use:

`php ImagePacking.php images/phone png 2048 mobile`

It give us one mobile.json file and some .png and .dds files.
