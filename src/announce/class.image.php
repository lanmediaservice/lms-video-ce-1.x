<?php
/**
 * CLASS clsImage [PHP4] v1.0 09.12.2004
 *
 * http://www.zutz.nl/phpclasses
 *
 * this is an image manipulation class for PHP4 with the Zend engine
 * based on the GD Library. supported imagetypes: [ jpg | gif | png ]
 *
 * LICENSE
 * Public domain
 *
 * MODERATOR
 * Ronald Zötsch - ZUTZ Automatisering
 */

include (dirname(__FILE__) . "/class.image.config.php");

class clsImage {
	/* properties */
	var $ImageStream;
	var $sFileLocation;
	var $sImageURL;

	var $filename;
	var $width;
	var $height;
	var $orientation;
	var $type;
	var $mimetype;
	var $interlace;
	var $jpegquality;

	function clsImage()
	{
		/* constructor */
		$this->aProperties = array();
		$this->jpegquality = IMAGEJPEGQUALITY;

		/* set interlace boolean */
		if (IMAGEINTERLACE != 0) {
			$this->interlace = true;
		} else {
			$this->interlace = false;
		}
	}

	function printError($sMessage)
	{
		/* echo errormessage to client and terminate script run */
		if (IMAGEDEBUG == 1) {
			echo $sMessage;
		}
	}

	function loadImage()
	{
		/* load a image from file */
		switch ($this->type) {
			case 1:
				$this->ImageStream = @imagecreatefromgif($this->sFileLocation);
				break;
			case 2:
				$this->ImageStream = @imagecreatefromjpeg($this->sFileLocation);
				break;
			case 3:
				$this->ImageStream = @imagecreatefrompng($this->sFileLocation);
				break;
			default:
				$this->printError('invalid imagetype');
		}

		if (!$this->ImageStream) {
			$this->printError('image not loaded');
		}
	}

	function saveImage()
	{
		/* store a memoryimage to file */

		if (!$this->ImageStream) {
			$this->printError('image not loaded');
		}

		switch ($this->type) {
			case 1:
				/* store a interlaced gif image */
				if ($this->interlace === true) {
					imageinterlace($this->ImageStream, 1);
				}

				imagegif($this->ImageStream, $this->sFileLocation);
				break;
			case 2:
				/* store a progressive jpeg image (with default quality value)*/
				if ($this->interlace === true) {
					imageinterlace($this->ImageStream, 1);
				}

				imagejpeg($this->ImageStream, $this->sFileLocation, $this->jpegquality);
				break;
			case 3:
				/* store a png image */
				imagepng($this->ImageStream, $this->sFileLocation);
				break;
			default:
				$this->printError('invalid imagetype');

				if (!file_exists($this->sFileLocation)) {
					$this->printError('file not stored');
				}
		}
	}

	function showImage()
	{
		/* show a memoryimage to screen */
		if (!$this->ImageStream) {
			$this->printError('image not loaded');
		}

		switch ($this->type) {
			case 1:
				imagegif($this->ImageStream);
				break;
			case 2:
				imagejpeg($this->ImageStream);
				break;
			case 3:
				imagepng($this->ImageStream);
				break;
			default:
				$this->printError('invalid imagetype');
		}
	}

	function setFilenameExtension()
	{
		/* set the imahe type and mimetype */
		$sOldFilenameExtension = substr($this->filename, strlen($this->filename) - 4, 4);
		if (($sOldFilenameExtension != '.gif') &&
				($sOldFilenameExtension != '.jpg') &&
				($sOldFilenameExtension != '.png')) {
			$this->printError('invalid filename extension');
		}

		switch ($this->type) {
			case 1:
				$this->filename = substr($this->filename, 0, strlen($this->filename) - 4) . '.gif';
				break;
			case 2:
				$this->filename = substr($this->filename, 0, strlen($this->filename) - 4) . '.jpg';
				break;
			case 3:
				$this->filename = substr($this->filename, 0, strlen($this->filename) - 4) . '.png';
				break;
			default:
				$this->printError('invalid imagetype');
		}
	}

	function setImageType($iType)
	{
		/* set the imahe type and mimetype */
		switch ($iType) {
			case 1:
				$this->type = $iType;
				$this->mimetype = 'image/gif';
				$this->setFilenameExtension();
				break;
			case 2:
				$this->type = $iType;
				$this->mimetype = 'image/jpeg';
				$this->setFilenameExtension();
				break;
			case 3:
				$this->type = $iType;
				$this->mimetype = 'image/png';
				$this->setFilenameExtension();
				break;
			default:
				$this->printError('invalid imagetype');
		}
	}

	function setLocations($sFileName)
	{
		/* set the photo url */
		$this->filename = basename($sFileName);
		$this->sFileLocation = $sFileName;
		$this->sImageURL = $sFileName;
	}

	function initializeImageProperties()
	{
		/* get imagesize from file and set imagesize array */
		list($this->width, $this->height, $iType, $this->htmlattributes) = getimagesize($this->sFileLocation);

		if (($this->width < 1) || ($this->height < 1)) {
			$this->printError('invalid imagesize');
		}

		$this->setImageOrientation();
		$this->setImageType($iType);
	}

	function setImageOrientation()
	{
		/* get image-orientation based on imagesize
	   options: [ portrait | landscape | square ] */

		if ($this->width < $this->height) {
			$this->orientation = 'portrait';
		}

		if ($this->width > $this->height) {
			$this->orientation = 'landscape';
		}

		if ($this->width == $this->height) {
			$this->orientation = 'square';
		}
	}

	function loadfile($sFileName)
	{
		/* load an image from file into memory */
		$this->setLocations($sFileName);

		if (file_exists($this->sFileLocation)) {
			$this->initializeImageProperties();
			$this->loadImage();
		} else {
			$this->printError("file $this->sFileLocation not found");
		}
	}

	function savefile($sFileName = null)
	{
		/* store memory image to file */
		if ((isset($sFileName)) && ($sFileName != '')) {
			$this->setLocations($sFileName);
		}

		$this->saveImage();
	}

	function preview()
	{
		/* print memory image to screen */
		header("Content-type: {$this->mimetype}");
		$this->showImage();
	}

	function showhtml($sAltText = null, $sClassName = null)
	{
		/* print image as htmltag */
		if (file_exists($this->sFileLocation)) {
			/* set html alt attribute */
			if ((isset($sAltText)) && ($sAltText != '')) {
				$htmlAlt = " alt=\"" . $sAltText . "\"";
			} else {
				$htmlAlt = "";
			}

			/* set html class attribute */
			if ((isset($sClassName)) && ($sClassName != '')) {
				$htmlClass = " class=\"" . $sClassName . "\"";
			} else {
				$htmlClass = " border=\"0\"";
			}

			$sHTMLOutput = '<img src="' . $this->sImageURL . '"' . $htmlClass . ' width="' . $this->width . '" height="' . $this->height . '"' . $htmlAlt . '>';
			print $sHTMLOutput;
		} else {
			$this->printError('file not found');
		}
	}

	function resize($iNewWidth, $iNewHeight)
	{
		/* resize the memoryimage do not keep ratio */
		if (!$this->ImageStream) {
			$this->printError('image not loaded');
		}

		if (function_exists("imagecopyresampled")) {
			$ResizedImageStream = imagecreatetruecolor($iNewWidth, $iNewHeight);
			imagecopyresampled($ResizedImageStream, $this->ImageStream, 0, 0, 0, 0, $iNewWidth, $iNewHeight, $this->width, $this->height);
		} else {
			$ResizedImageStream = imagecreate($iNewWidth, $iNewHeight);
			imagecopyresized($ResizedImageStream, $this->ImageStream, 0, 0, 0, 0, $iNewWidth, $iNewHeight, $this->width, $this->height);
		}

		$this->ImageStream = $ResizedImageStream;
		$this->width = $iNewWidth;
		$this->height = $iNewHeight;
		$this->setImageOrientation();
	}

	function resizetowidth($iNewWidth)
	{
		/* resize image to given width (keep ratio) */
		$iNewHeight = ($iNewWidth / $this->width) * $this->height;

		$this->resize($iNewWidth, $iNewHeight);
	}

	function resizetoheight($iNewHeight)
	{
		/* resize image to given height (keep ratio) */
		$iNewWidth = ($iNewHeight / $this->height) * $this->width;

		$this->resize($iNewWidth, $iNewHeight);
	}

	function resizetopercentage($iPercentage)
	{
		/* resize image to given percentage (keep ratio) */
		$iPercentageMultiplier = $iPercentage / 100;
		$iNewWidth = $this->width * $iPercentageMultiplier;
		$iNewHeight = $this->height * $iPercentageMultiplier;

		$this->resize($iNewWidth, $iNewHeight);
	}

	function crop($iNewWidth, $iNewHeight, $iResize = 0)
	{
		/* crop image (first resize with keep ratio) */
		if (!$this->ImageStream) {
			$this->printError('image not loaded');
		}

		/* resize imageobject in memory if resize percentage is set */
		if ($iResize > 0) {
			$this->resizetopercentage($iResize);
		}

		/* constrain width and height values */
		if (($iNewWidth > $this->width) || ($iNewWidth < 0)) {
			$this->printError('width out of range');
		}
		if (($iNewHeight > $this->height) || ($iNewHeight < 0)) {
			$this->printError('height out of range');
		}

		/* create blank image with new sizes */
		$CroppedImageStream = ImageCreateTrueColor($iNewWidth, $iNewHeight);

		/* calculate size-ratio */
		$iWidthRatio = $this->width / $iNewWidth;
		$iHeightRatio = $this->height / $iNewHeight;
		$iHalfNewHeight = $iNewHeight / 2;
		$iHalfNewWidth = $iNewWidth / 2;

		/* if the image orientation is landscape */
		if ($this->orientation == 'landscape') {
			/* calculate resize width parameters */
			$iResizeWidth = $this->width / $iHeightRatio;
			$iHalfWidth = $iResizeWidth / 2;
			$iDiffWidth = $iHalfWidth - $iHalfNewWidth;

			if (function_exists("imagecopyresampled")) {
				imagecopyresampled($CroppedImageStream, $this->ImageStream, - $iDiffWidth, 0, 0, 0, $iResizeWidth, $iNewHeight, $this->width, $this->height);
			} else {
				imagecopyresized($CroppedImageStream, $this->ImageStream, - $iDiffWidth, 0, 0, 0, $iResizeWidth, $iNewHeight, $this->width, $this->height);
			}
		}
		/* if the image orientation is portrait or square */
		elseif (($this->orientation == 'portrait') || ($this->orientation == 'square')) {
			/* calculate resize height parameters */
			$iResizeHeight = $this->height / $iWidthRatio;
			$iHalfHeight = $iResizeHeight / 2;
			$iDiffHeight = $iHalfHeight - $iHalfNewHeight;

			if (function_exists("imagecopyresampled")) {
				imagecopyresampled($CroppedImageStream, $this->ImageStream, 0, - $iDiffHeight, 0, 0, $iNewWidth, $iResizeHeight, $this->width, $this->height);
			} else {
				imagecopyresized($CroppedImageStream, $this->ImageStream, 0, - $iDiffHeight, 0, 0, $iNewWidth, $iResizeHeight, $this->width, $this->height);
			}
		} else {
			if (function_exists("imagecopyresampled")) {
				imagecopyresampled($CroppedImageStream, $this->ImageStream, 0, 0, 0, 0, $iNewWidth, $iNewHeight, $this->width, $this->height);
			} else {
				imagecopyresized($CroppedImageStream, $this->ImageStream, 0, 0, 0, 0, $iNewWidth, $iNewHeight, $this->width, $this->height);
			}
		}

		$this->ImageStream = $CroppedImageStream;
		$this->width = $iNewWidth;
		$this->height = $iNewHeight;
		$this->setImageOrientation();
	}

	function writetext($sText, $iFontSize = 10, $sTextColor = '0,0,0', $sFontFilename = 'arial', $iXPos = 5, $iYPos = 15, $iTextAngle = 0)
	{
		/* write text on image */
		if (!$this->ImageStream) {
			$this->printError('image not loaded');
		}

		if (($iXPos > $this->width) || ($iXPos < 0)) {
			$this->printError('x-pos out of range');
		}

		if (($iYPos > $this->height) || ($iYPos < 0)) {
			$this->printError('y-pos out of range');
		}

		$sFont = IMAGEFONTDIR . IMAGEDIRSEPARATOR . $sFontFilename;
		$aTextColor = explode(',', $sTextColor, 3);
		$ImageColor = imagecolorallocate($this->ImageStream, $aTextColor[0], $aTextColor[1], $aTextColor[2]);
		$iLineWidth = imagettfbbox($iFontSize, $iTextAngle, $sFont, $sText);
		imagettftext($this->ImageStream, $iFontSize, $iTextAngle, $iXPos, $iYPos, $ImageColor, $sFont, $sText);
	}

	function convert($sTargetType)
	{
		/* convert image to given type [ jpg | gif | png ] */
		if (!$this->ImageStream) {
			$this->printError('image not loaded');
		}

		switch ($sTargetType) {
			case 'gif':
				$this->setImageType(1);
				break;
			case 'jpg':
				$this->setImageType(2);
				break;
			case 'png':
				$this->setImageType(3);
				break;
			default: $this->printError('invalid imagetype');
		}
	}
}

?>
