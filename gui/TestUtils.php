<?php
/**
* Static utilities for running unitTests within portal
* $Id: TestUtils.php 366 2013-11-30 13:39:53Z admin $
*/
class TestUtils{

	const
		// define logging levels
		LEVEL_INFO = 'info',
		LEVEL_WARNING = 'warning',
		LEVEL_ERROR = 'error';

	/**
	* vrati link pre stiahnutie snapshot file
	*/
	/*
	public static function getRequestUri(){
		return Yii::app()->createUrl('admin/test');
	}
	*/

	/**
	* Prekonvertuje text na cielovy character set
	* @param string $text
	* @param string $from Kodovanie, v ktorom bezi testovana webstranka, napr. iso-8859-2
	* @param string $to Kodovanie, v ktorom vracia odpovede selenium server (utf-8)
	*/
	public static function convert($text, $from = CHARSET_SERVER, $to = CHARSET_WEB){
		if(strcasecmp($from, $to)!==0 ){
			$text = iconv($from, $to."//TRANSLIT", $text);
		}
		return $text;
	}

	/**
	* Delete all files from directory starting with 'test_' in filename.
	* @param int $ttl Time to live - seconds, default 1 hour. Files older than $ttl seconds will be deleted. If 0, means all.
	* @param string $dir Path to directory to be cleaned. If empty DIR_TEMP will be used.
	* @param string $prefix Deleted only files with this prefix
	* @returns TRUE if iteration executed succesfully
	*/
	public static function flushTempDir($ttl = 3600, $dir = '', $prefix = 'test_'){
		clearstatcache();
		$dir = (trim($dir) != '') ? trim($dir) : DIR_TEMP;
		$dir = realpath($dir) . DIRECTORY_SEPARATOR;
		$ok = 0;
		if(is_dir($dir)){
			$di = new DirectoryIterator($dir);
			foreach($di as $fileObj){
				$fname = $fileObj->getFilename();
				if($fileObj->isFile() && substr($fname, 0, 5)==$prefix){
					if($fileObj->getMTime() + $ttl < time()){
						if(!unlink($fileObj->getPathname())){
							exit(__CLASS__ . ': Failed deleting ['.$fileObj->getPathname().']. Check writing permissions.');
						}else{
							++$ok;
						}
					}
				}
			}
			return $ok;
		}
		return false;
	}

	/**
	* Store fetched $html into file in temporary directory.
	* @param string $string The string to be saved
	* @param string $label URL link label
	* @param bool $returnLink If true, link is only returned, otherwise directly sent to output buffer
	* @param bool $enabled If false, disable this function
	* @param string $filename Optional filename into which $string will be saved
	* @return string URL link
	*/
	public static function snapshot($string, $label='snapshot', $returnLink = false, $enabled=true, $filename = ''){
		if(!$enabled){
			return false;
		}
		$trace = debug_backtrace();
		if($label == 'snapshot' && isset($trace[1])){
			$function = isset($trace[1]['function']) ? $trace[1]['function'] : '';
			$class = isset($trace[1]['class']) ? $trace[1]['class'] : '';
			$line = isset($trace[1]['line']) ? $trace[1]['line'] : '';
			$label .= " ({$class}::{$function}::{$line})";
		}
		if($filename == ''){
			list($msecs, $secs) = explode(' ', microtime());
			$filename = 'test_'.str_replace('.','_',$_SERVER['SERVER_NAME']).'_'.$secs.'_'.(intval($msecs*1000,3)).'.html';
		}
		$savePath = DIR_TEMP . $filename;
		if(!file_put_contents($savePath, $string)){
			exit('Failed saving file into ['.$savePath.']');
		}
		// return URL link for download snapshot
		$url = self::getSnapshotUrl($savePath, $label);
		if($returnLink){
			return $url;
		}
		echo $url;
		return true;
	}

	/**
	* Return URL for downloading the file
	* @param string $filepath Abs. path to file being downloaded
	* @return string
	*/
	public static function getUrlDownload($filepath){
		return Yii::app()->createUrl('admin/test', array('snapshot' => base64_encode($filepath)));
	}

	/**
	* Return URL download link for supplied file.
	* @param string $file Absolute path to a file in temporary directory or filename in temporary directory.
	* @param string $label URL label, if empty created automatically.
	*/
	public static function getSnapshotUrl($file, $label=''){
		$path = is_file($file) ? $file : DIR_TEMP . $file;
		if(!is_file($path)){
			return '<div class="red">Cannot create snapshot url - file ['.$file.'] not found in ['.$path.']</div>';
		}
		$basename = basename($file);
		if($label==''){
			$label = $basename;
		}
//		$url = self::getRequestUri().'?snapshot='.base64_encode($basename);
		$url = self::getUrlDownload($file);
		return '<div class="test-snapshot-link">
					&raquo; <a href="'.$url.'" target="_blank" class="snapshot">'.$label.'</a> &nbsp;
					[<a href="'.$url.'&download=1" target="_blank">Download</a>]
				</div>';
	}

	/**
	* Zobrazi ulozeny snapshot z unit testu
	* @param string $token URL token obsahujuci zakodovany nazov suboru, ktory musi existovat v TEMP directory.
	*/
	public static function downloadSnapshot($token){
		$path = base64_decode($token);
		if(!$path){
			exit('Invalid URL token ['.$token.']');
		}
		//$path = DIR_TEMP . $filename;
		if(!is_file($path)){
			exit('File not found in ['.$path.']');
		}
		// streamdown file
		$ext = pathinfo($path, PATHINFO_EXTENSION);
		if(!isset($_GET['download']) && in_array(strtolower($ext), array('pdf', 'txt', 'html', 'htm', 'csv', 'xml', 'xsd'))){
			// allow only text format for viewing online
			if(strcasecmp($ext, 'pdf')==0){
				header('Cache-Control: public, must-revalidate, max-age=0'); // HTTP/1.1
				header('Pragma: public');
				header('Expires: Sat, 26 Jul 1997 05:00:00 GMT'); // Date in the past
				header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');

				header('Content-Type: application/pdf');
				header('Content-Disposition: attachment; filename="'.basename($path).'";');
				header('Content-Transfer-Encoding: binary');
				header('Content-Length: '.filesize($path));

				header('Content-Type: application/force-download');
				header('Content-Type: application/octet-stream', false);
				header('Content-Type: application/download', false);

			}elseif(in_array(strtolower($ext), array('xml','xsd'))){
				header('Content-Type: text/xml');
			}
			readfile($path);
		}else{
			self::downloadFile($path);
		}
		exit();
	}

	/**
	* Download any file
	* http://php.net/manual/en/function.header.php
	* @param string $fullPath Path to file
	*/
	public static function downloadFile($fullPath, $exit = true){
		// Required for some browsers
		if(ini_get('zlib.output_compression')){
			//ini_set('zlib.output_compression', 'Off');
		}
		// File Exists?
		if( file_exists($fullPath) ){

			// Parse Info / Get Extension
			$fsize = filesize($fullPath);
			$path_parts = pathinfo($fullPath);
			$ext = strtolower($path_parts["extension"]);

			// Determine Content Type
			// http://technet.microsoft.com/en-us/library/ee309278%28office.12%29.aspx
			switch ($ext) {
				case "pdf":
					$ctype="application/pdf";
					break;
				case "exe":
					$ctype="application/octet-stream";
					break;
				case "zip":
					$ctype="application/zip";
					break;
				case "doc":
					$ctype="application/msword";
					break;
				case "xls":
					$ctype="application/vnd.ms-excel";
					break;
				case "xlsx":
					$ctype="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet";
					break;
				case "gif":
					$ctype="image/gif";
					break;
				case "png":
					$ctype="image/png";
					break;
				case "jpeg":
				case "jpg":
					$ctype="image/jpg";
					break;
				default:
					$ctype="application/force-download";
			}

			header("Pragma: public"); // required
			header('Expires: Sat, 26 Jul 2000 05:00:00 GMT'); // Date in the past
			header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
			header("Cache-Control: must-revalidate, post-check=0, pre-check=0, max-age=0");
			header("Cache-Control: private",false); // required for certain browsers
			header("Content-Type: $ctype");
			header("Content-Disposition: attachment; filename=\"".basename($fullPath)."\";" );
			header("Content-Transfer-Encoding: binary");
			header("Content-Length: ".$fsize);
			ob_get_clean();
			//flush();
			readfile( $fullPath );

		} else {
			exit('File not found in "'.$fullPath.'".');
		}
		if($exit){
			exit();
		}
	}

	/**
	* Return current microtime
	*/
	public static function utime(){
		//return array_sum(explode(' ', microtime()));	// old compatability PHP4
		return microtime(true);
	}

	/**
	* Return current memory usage. Must be compiled with --enable-memory.
	* Not enabled by default prior to PHP 5.1
	*/
	public static function getMemoryUsage(){
		if(function_exists('memory_get_usage')){
			return round(memory_get_usage()/1024/1024,2).' MB';
		}
		return 'n/a';
	}

	/**
	* Return the time measured since test execution start
	*/
	public static function execTime(){
		return number_format(microtime(true)-TIMESTART, 3).' sec';
	}

	/**
	* Load HTML template and optionally replace metawords within it.
	* @param string $path Absolute path to sought template file
	* @param array $replace Array of words to be replace, e.g. array('{SERVERURL}' => '1.20.103.1', '{TODAY}' => date('d.m.Y'))
	* @return string
	*/
	public static function getTemplate($path, $replace = array()){
		$html = '<!-- FILE NOT FOUND IN ['.$path.'] -->';
		if(file_exists($path)){
			$html = file_get_contents($path);
			if(is_array($replace) && count($replace)){
				$html = strtr($html, $replace);
			}
		}
		return $html;
	}

	/**
	* Return list of files.
	* @param string $dir Directory to search
	* @param string $base Current recursive subdirectory under $dir. Defaults to "".
	* @param array $fileTypes Types of sought files, default array()
	* @param string $noPattern REGEX which files are rejected
	* @param string $yesPattern REGEX which files are accepted
	* @param int $level Search depth level, default -1.
	 * Level -1 means searching for all directories and files under the directory;
	 * Level 0 means searching for only the files DIRECTLY under the directory;
	 * level N means searching for those directories that are within N levels.
	* @param bool $addDirs If true directory paths will be also returned, default true.
	* @return array of files
	*/
	public static function findFilesRecursive($dir, $options = array()){

		$base='';
		$fileTypes=array();
		$noPattern='';
		$yesPattern='';
		$level = -1;
		$addDirs = true;
		extract($options);

		$list=array();
		$handle=opendir($dir);
		while(($file=readdir($handle))!==false){
			if($file==='.' || $file==='..'){
				continue;
			}
			$path=realpath($dir).DS.$file;
			$isFile=is_file($path);
			if(self::validatePath($base,$file,$isFile,$fileTypes,$noPattern,$yesPattern)){
				if($isFile || $addDirs){
					$list[]=$path;
				}
				if(!$isFile && $level){
					$list=array_merge($list,self::findFilesRecursive($path,array(
						'base'=>$base.'/'.$file,
						'fileTypes'=>$fileTypes,
						'noPattern'=>$noPattern,
						'yesPattern'=>$yesPattern,
						'level'=>$level-1
					)));
				}
			}
		}
		closedir($handle);
		return $list;
	}

	/**
	* Write log, add time and auto-detect severity, if empty
	* @param string $message log message
	* @param string $file Abs. path to write to.
	* @param string $severity info|error|warning or leave empty for auto detection.
	*/
	public static function log($message, $file, $severity = ''){
		if(!$severity){
			$severity = preg_match('/(error|fail|caution)/i', $message) ? self::LEVEL_ERROR : self::LEVEL_INFO;
		}
		$message = "\n[".date('d.m.Y H:i:s').'] ['.$severity.'] '.$message;
		$path = DIR_LOG . $file;
		if(!error_log($message, 3, $path)){
			exit('Failed writing unit test log into ['.$file.']. Message: '.htmlspecialchars($message));
		}
	}

	/**
	* Return true if file should be included in returned list
	* @param mixed $base
	* @param mixed $file
	* @param mixed $isFile
	* @param mixed $fileTypes
	* @param mixed $noPattern
	* @param mixed $yesPattern
	* @return bool
	*/
	protected static function validatePath($base,$file,$isFile,$fileTypes,$noPattern,$yesPattern){
		if(!$isFile){
			return true;
		}
		// exclude file/directory
		if(!empty($noPattern)){
			return preg_match($noPattern,$file);
		}
		if(!empty($yesPattern)){
			return preg_match($yesPattern,$file);
		}
		// we have directory or accept all files
		if(empty($fileTypes)){
			return true;
		}
		// check fileTypes
		if(($pos=strrpos($file,'.'))!==false){
			$type=substr($file,$pos+1);
			return in_array($type,$fileTypes);
		}
		return false;
	}

	/**
	* Return static HTML header
	*/
	public static function getOutputHeader(){
		// optional header - adjust if needed
		return;
		/*
		return <<< HEADER
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "DTD/xhtml1-transitional.dtd">
<html>
<head>
	<link rel="stylesheet" type="text/css" href="/public/styles/portalbase.css" />
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-2" />
</head>
<body>
HEADER;
*/
	}

	/**
	* Return static HTML footer
	*/
	public static function getOutputFooter(){
		// optional footer - adjust if needed
		return;
		/*
		return <<< FOOTER
</body>
</html>
FOOTER;
*/
	}

	/**
	* Return page title
	*/
	public static function getOutputTitle(){
		// optional title - adjust if needed
		return '';
		//return '<h1 align="center">UNIT TESTS @ '.$_SERVER['SERVER_NAME'].' (MY PORTAL)</h1>';
	}

}

