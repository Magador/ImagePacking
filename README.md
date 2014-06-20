ImagePacking
============

ImagePacking.php must be executed locally with php.exe.

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

