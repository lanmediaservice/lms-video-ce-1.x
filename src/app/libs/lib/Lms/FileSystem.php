<?php
/**
 * LMS Library
 * 
 * @version $Id: FileSystem.php 260 2009-11-29 14:11:11Z macondos $
 * @copyright 2007-2008
 * @author Ilya Spesivtsev <macondos@gmail.com>
 * @author Alex Tatulchenkov<webtota@gmail.com>
 * @package FileSystem
 */

/**
 * Static class for misc filesystem functions.
 * @package FileSystem
 */
 
class Lms_FileSystem
{
    function isDir($path)
    {
        return Lms_Ufs::is_dir($path); 
    }

    function isFile($path)
    {
        return Lms_Ufs::is_file($path); 
    }

    function getFolder($path)
    {
        return new Lms_FileSystem_Folder($path);
    }

    function getFile($path)
    {
        return new Lms_FileSystem_File($path);
    }

    function fileExists($path)
    {

    }

    function createFile($path)
    {
        $file = new Lms_FileSystem_File($path);
        $file->create();
        return $file;
    }

    function createFolder($path, $mode = 0777, $recursively = false)
    {
        $folder = new Lms_FileSystem_Folder($path);
        $folder->create($mode, $recursively);
        return $folder;
    }

    function openFile($path)
    {
    }

    function copy($sourcePath, $destinationPath)
    {
        Lms_Ufs::copy($sourcePath, $destinationPath); 
    }

    function move($sourcePath, $destinationPath)
    {
        Lms_Ufs::rename($sourcePath, $destinationPath); 
    }

    function deleteFile($path)
    {
        $file = new Lms_FileSystem_File($path);
        $file->delete();
    }

    function deleteFolder($path)
    {
        $folder = new Lms_FileSystem_Folder($path);
        $folder->delete(false);
    }

    function deleteThree($path)
    {
        $folder = new Lms_FileSystem_Folder($path);
        $folder->delete(true);
    }
    
  
}