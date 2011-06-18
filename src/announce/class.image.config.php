<?php
/**************************************************************************
 * CONFIG clsImage (filename: class.image.config.php)
 *
 * This is the configuration file for the class clsImage 
 */
 
/* SCRIPT ERROR REPORTING 
   Used for script debugging only.
   Options: 
	   error_reporting [OFF|ON] [ error_reporting(0) | error_reporting(E_ALL) ]
		 IMAGEDEBUG      [OFF|ON|SOURCEDEBUG] [ 0 | 1 | 2 ]
	 */
	 
   //error_reporting(0);
   define("IMAGEDEBUG","0");	 

/* FILE BASE LOCATIONS (no trailing slash!)
   These parameters are used for loading and storing images from and 
	 to disk, the browser clients must have read/write access on
	 the psysical disk locations. 
	 
	 Do not provide execute (and list) permissions to this 
	 directory for security reasons.
	 
	 WINDOWS examples: 
	   IMAGEDIRSEPARATOR \\
	   IMAGEBASEURL      http://www.domain.com/images/temp
		 IMAGEBASEPATH     C:\\inetpub\\wwwroot\\sitename\\images\\temp
		 
	 UNIX examples:
	   IMAGEDIRSEPARATOR /
	   IMAGEBASEURL      http://www.domain.com/images/temp
		 IMAGEBASEPATH     /www/sitename/images/temp */		 
		 
	 define("IMAGEDIRSEPARATOR","/");
   define("IMAGEBASEURL","");
   define("IMAGEBASEPATH","");

/* FONT FILE LOCATION (no trailing slash!)
   This parameter provides the full filelocation of the fontfile 
	 which is used for writing text on the image. The full path with
	 filename and extension must be provided.
	 
	 WINDOWS example: C:\WINNT\fonts
	 UNIX    example: /usr/local/font */
	 
   define("IMAGEFONTDIR","");
	 
/* DEFAULT IMAGE QUALITY
   These parameters define the image quality, these parameters
	 are used in the script when saving an image to disk. 
	 These parameters set the defaultvalues, these values can be 
	 overruled by setting the object properties to a custom value.
	 
	 $objImage->jpegquality = 80 (range 1..100)
	 $objImage->interlace = true (or false)
*/
	 
  define("IMAGEINTERLACE","1");
  define("IMAGEJPEGQUALITY","80");	
?>