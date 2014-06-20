<?php

/**
 * Class ImagePacking
 */

class ImagePacking {
    /**
     * An array of unfitted cropped images with attributes:
     *  -sourceX:   offsetX of the cropped image
     *  -sourceY:   offsetX of the cropped image
     *  -width:     image width
     *  -height:    image height
     */
    private $unfittedImages = array();

    /**
     * An array of fitted cropped images with attributes:
     *  -sourceX:   offsetX of the cropped image
     *  -sourceY:   offsetX of the cropped image
     *  -width:     image width
     *  -height:    image height
     *  -x:         position x
     *  -y:         position y
     *  -id:        index of the related node
     */
    private $fittedImages = array();

    /**
     * An array of Node
     */
    private $nodes = array();

    /**
     * @var int Width of nodes
     */
    private $nodeWidth;

    /**
     * @var int Height of nodes
     */
    private $nodeHeight;


	/**
	 * @var string
	 */
	private $filetype;


	/**
	 * @param int $_nodeWidth
	 * @param int $_nodeHeight
	 * @param array $_unfittedImages
	 * @param string $_filetype
	 */
	public function __construct($_nodeWidth = 4096, $_nodeHeight = 4096, $_unfittedImages = array(), $_filetype = "png") {
        /**
         * Initialize instance variables
         */
        $this->unfittedImages = $_unfittedImages;
        $this->nodeWidth = $_nodeWidth;
        $this->nodeHeight = $_nodeHeight;
	    $this->filetype = $_filetype;

        /**
         * For each image, if its size is too large, throw an Exception
         */
        foreach($_unfittedImages as $key => $image) {
            if($image['width'] > $this->nodeWidth || $image['height'] > $this->nodeHeight)
                exit("Image {$key} is too large");
        }
    }

    /**
     * Pack the array of images from this class, or another array to set to the current $_unfittedImages
     * @param null $_unfittedImages
     * @return array of fitted images
     */
    public function pack($_unfittedImages = NULL) {
        /**
         * if $_unfittedImages is not NULL
         * if one of its images is too large, throw an Exception
         * else assign$_unfittedImages to instance variable $this->unfittedImages
         */
        if(!is_null($_unfittedImages)) {
            foreach ($_unfittedImages as $key => $image) {
                if ($image['width'] > $this->nodeWidth || $image['height'] > $this->nodeHeight) {
                    exit("Image {$key} is too large");
                }
            }
            $this->unfittedImages = $_unfittedImages;
        }

        //While there are unfitted images
        do {
            //Create a new node
            array_push($this->nodes, new Node($this->nodeWidth, $this->nodeHeight, 0, 0, count($this->nodes)));
            //For each image inside the array of unfitted images
            $count = count($this->unfittedImages);
            for ($i = 0; $i < $count; $i++){
                //extract the first image
                $image = array_shift($this->unfittedImages);
                //try inserting the image
                $img = $this->nodes[count($this->nodes)-1]->insert($image);
                //if the result is not null, insert the result into the array of fitted images
                if (!is_null($img))
                    array_push($this->fittedImages, $img);
                //else insert the image at the end of the array of unfitted images
                else
                    array_push($this->unfittedImages, $image);

            }
        }while(count($this->unfittedImages) > 0);

	    return $this->orderImages($this->fittedImages);
    }

	/**
	 * @param $images Array to be ordered according to their index
	 * @return array of ordered images
	 */
	private function orderImages($images) {
		print "Order images indexes ...\n";
		$orderedImages = array();
		$i = PHP_INT_MAX;
		//Get max index
		for($it = 0; $it < count($images); ++$it) {
			$i = $images[$it]["index"] <= $i ? $images[$it]["index"]: $i;
		}
		while(count($images) > 0) {
			$image = array_shift($images);
			if($image["index"] == $i) {
				array_push($orderedImages, $image);
				$i++;
			} else {
				array_push($images, $image);
			}
		}
		print "Ordered!\n";
		return $orderedImages;
	}

	/**
	 * Generate PNG images via ImageMagick library
	 * @param string $directoryName The destination directory
	 * @param $backgroundColor String Background color for the generated images
	 */
    public function generateTextures($directoryName = ".{DIRECTORY_SEPARATOR}", $backgroundColor) {
        $drawnImages = 0;
        $drawnImagicks = 0;
        print "Generating Textures ...\n";
        do {
            $imagick = new Imagick();
            $imagick->newImage($this->nodeWidth, $this->nodeWidth, $backgroundColor);
            $imagick->setFormat('png');
            $textureName = $directoryName.$drawnImagicks;

            print "Generating texture {$textureName}.png ...\n";

            foreach($this->fittedImages as $image) {
                if($image['id'] == $drawnImagicks) {
                    $imagick = $this->drawImage($imagick, $image, $backgroundColor);
                    $drawnImages++;
                }
            }
            print "Drawing texture {$textureName}.png ...\n";
            $imagick->writeImage($textureName.".png");
            print "Converting texture {$textureName}.png to {$textureName}.dds ...\n";
            $textureNamePNG = $textureName.".png";

            exec(escapeshellcmd("convert {$textureNamePNG} -format dds -define dds:compression=dxt5 -define dds:cluster-fit=true -define dds:mipmaps=0 {$textureName}.dds ")/*."& %windir%/gzip -c9 {$textureName}.dds > {$textureName}.dds.gz"*/);
            $drawnImagicks++;
            print "Texture {$textureName}.dds generated!\n";
        }while($drawnImages < count($this->fittedImages));
        print "All textures have been generated!\n";
    }

	/**
	 * @param Imagick $imagick
	 * @param $image
	 * @return Imagick $imagick
	 */
    public function drawImage(Imagick $imagick, $image) {
        $im = new Imagick(realpath($image['sourceName']));
        $im->cropimage($image['width'], $image['height'], $image['sourceX'], $image['sourceY']);
        $imagick->compositeimage($im, imagick::COMPOSITE_DEFAULT, $image['x'], $image['y']);
        return $imagick;
    }

	/**
	 * @param $directoryName
	 * @param $filetype
	 * @param $backgroundColor
	 * @return array
	 * @throws Exception
	 */
	public static function trimImages($directoryName, $filetype, $backgroundColor) {
        if(!is_dir($directoryName)) {
            throw new Exception('The first parameter of '.__METHOD__.' must be a directory');
        }

        print "Finding images ...\n";

        $files = findFile($directoryName, "/\.".$filetype."$/");
        $filesCounted = sizeof($files);

        print "Extracting coordinates from {$filesCounted} images ...\n";
        $i = 0;
	    $pad = 1;

	    do {
		    if(is_numeric(substr($files[0], -(4 + $pad), $pad)))
			    ++$pad;
		    else {
			    --$pad;
			    break;
		    }
	    }while($i < strlen($files[0]));

	    $istart = $i = intval(substr($files[0], -(4 + $pad), $pad));

        $imagesInfos = array();
        foreach ($files as $file) {
            $im = new Imagick($file);
            $im->setbackgroundcolor(new ImagickPixel($backgroundColor));
            $im->trimimage(0);
            $imagePage = $im->getimagepage();
            $infos = array( "sourceX" => $imagePage['x'],
                            "sourceY" => $imagePage['y'],
                            "index"   => $i);
            $im->setimagepage(0,0,0,0);
            $infos['width'] = $im->width;
            $infos['height'] = $im->height;
            $infos['sourceName'] = $directoryName.DIRECTORY_SEPARATOR.basename($file);
            array_push($imagesInfos, $infos);
            print "Coordinates from the image {$i} extrated! ".round((($i - $istart)/$filesCounted)*100)."%\n";
            $i++;
        }

        print "Extracted !\n";

        return $imagesInfos;
    }

};


/**
 * Class Node
 */
class Node {
    //Attributes of the node
	/**
	 * @var array
	 */
	private $root = array();

	/**
	 * @var int
	 */
	private $margin = 4;

	/**
	 * @param $_width
	 * @param $_height
	 * @param $_x
	 * @param $_y
	 * @param $_id
	 */
	public function __construct($_width, $_height, $_x, $_y, $_id){
        $this->root['x'] = $_x;
        $this->root['y'] = $_y;
        $this->root['width'] = $_width;
        $this->root['height'] = $_height;
        $this->root['left'] = null;
        $this->root['right'] = null;
        $this->root['used'] = false;
        $this->root['id'] = $_id;
    }

    /**
     * @param $image
     * @return
     */
    public function insert($image) {
        $coords = $this->searchNextCoords($this->root, $image['width']+$this->margin, $image['height']+$this->margin);
        if($coords) {
            $image['x'] = $coords['x'];
            $image['y'] = $coords['y'];
            $image['id'] = $this->root['id'];
            return $image;
        } else {
            return NULL;
        }
    }

	/**
	 * @param $node
	 * @param $width
	 * @param $height
	 * @return array|bool
	 */
	function searchNextCoords(&$node, $width, $height) {
        // if we are not at a leaf then go deeper
        if(is_array($node['left'])) {
            // check first the left branch if not found then go by the right
            $coords = $this->searchNextCoords($node['left'], $width, $height);
            return is_array($coords) ? $coords : $this->searchNextCoords($node['right'], $width, $height);
        } else {
            // if already used or it's too big then return
            if($node['used'] || $width > $node['width']+$this->margin || $height > $node['height']+$this->margin )
                return false;

            // if it fits perfectly then use this gap
            if($node['width']+$this->margin == $width && $node['height']+$this->margin == $height) {
                $node['used'] = true;
                return array(
                    'x' => $node['x'],
                    'y' => $node['y']
                );
            }

            // initialize the left and right leafs by cloning the current one
            $node['left'] = (new Node($node['width'], $node['height'], $node['x'], $node['y'], $node['id']))->root;
            $node['right'] = (new Node($node['width'], $node['height'], $node['x'], $node['y'], $node['id']))->root;

            // checks if we partition in vertical or horizontal
            if($node['width'] - $width > $node['height'] - $height) {
                $node['left']['width'] = $width - $this->margin;
                $node['right']['x'] = $node['x'] + $width;
                $node['right']['width'] = $node['width'] - $width;
            } else {
                $node['left']['height'] = $height - $this->margin;
                $node['right']['y'] = $node['y'] + $height;
                $node['right']['height'] = $node['height'] - $height;
            }

            return $this->searchNextCoords($node['left'], $width, $height);
        }
    }
};

/**
 * Function from PHP.net @link http://php.net/manual/en/ref.filesystem.php#35196
 * @param string $location
 * @param string $fileregex
 * @return array of files
 */
function findFile($location='',$fileregex='') {
    if (!$location or !is_dir($location) or !$fileregex) return false;

    $matchedfiles = array();
    $all = opendir($location);
    while ($file = readdir($all)) {
        if (is_dir($location.DIRECTORY_SEPARATOR.$file) and $file <> ".." and $file <> ".") {
            $subdir_matches = findFile($location.DIRECTORY_SEPARATOR.$file,$fileregex);
            $matchedfiles = array_merge($matchedfiles,$subdir_matches);
            unset($file);
        }
        elseif (!is_dir($location.DIRECTORY_SEPARATOR.$file)) {
            if (preg_match($fileregex,$file)) {
                array_push($matchedfiles,$location.DIRECTORY_SEPARATOR.$file);
            }
        }
    }
    closedir($all);
    unset($all);
    return $matchedfiles;
}



if($argc == 1) {
	exit(   'Syntax: ImagePacking.php <dirpath> [filetype [sizeofimage [outputprefix [bgcolor]]]]'.PHP_EOL.PHP_EOL.
			"dirpath:\tThe directory path containing images. Warning: if this directory contains directories containing images, those will be used too.".PHP_EOL.
			"filetype:\tThe file extension of images. Default set to 'png'.".PHP_EOL.
			"\t\tFormats available <http://www.imagemagick.org/script/formats.php>".PHP_EOL.
			"sizeofimage:\tThe size in pixels of the generated images, count as width and height. Default at 4096.".PHP_EOL.
			"outputprefix:\tThe prefix to be set for the generated images. Default at directory name.".PHP_EOL.
			"bgcolor:\tThe background color of the images to be processed. It also is used to set the background color of generated images.".PHP_EOL.
			"\t\tDefault set to 'none' (transparent).");
}

$dirpath = "";
$filetype = "png";
$sizeofimage = 4096;
$outputprefix = basename($dirpath);
$bgcolor = 'none';

if(isset($argv[1]) && is_dir($argv[1]))
    $dirpath = $argv[1];
else
    exit('A directory path is needed'.PHP_EOL);

if(isset($argv[2]))	$filetype = $argv[2];
if(isset($argv[3]))	$sizeofimage = $argv[3];
if(isset($argv[4])) $outputprefix = $argv[4];
if(isset($argv[5]))	$bgcolor = $argv[5];

$infos = ImagePacking::trimImages($dirpath, $filetype, $bgcolor);

$imagePacking = new ImagePacking($sizeofimage, $sizeofimage, $infos, $filetype);
$fitted = $imagePacking->pack();

file_put_contents($outputprefix.".json", json_encode($fitted, JSON_PRETTY_PRINT));

$imagePacking->generateTextures($outputprefix, $bgcolor);
