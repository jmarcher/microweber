<?php
namespace Microweber\Utils\Backup\Readers;

use Microweber\Utils\Backup\BackupManager;
use Microweber\Utils\Backup\Loggers\BackupImportLogger;

class ZipReader extends DefaultReader
{
	/**
	 * Read data from file
	 * @return \JsonMachine\JsonMachine[]
	 */
	public function readData()
	{
		$filesForImporting = array();
		
		$this->_checkPathsExists();
		
		BackupImportLogger::setLogInfo('Unzipping '.basename($this->file).' in userfiles...');
		
		$backupManager = new BackupManager();
		$backupLocation = $backupManager->getBackupLocation(). 'temp_backup_zip/';

		// Remove old files
		$this->_removeFilesFromPath($backupLocation);
		
		$unzip = new \Microweber\Utils\Unzip();
		$unzip->extract($this->file, $backupLocation, true);
		
		if ($backupLocation != false and is_dir($backupLocation)) {
			BackupImportLogger::setLogInfo('Media restored!');
			$copy = $this->_cloneDirectory($backupLocation, userfiles_path());
		}
		
		$mwContentJsonFile = $backupLocation. 'mw_content.json';
		
		if (is_file($mwContentJsonFile)) {
			$filesForImporting[] = array("file"=>$mwContentJsonFile, "reader"=>"json");
		}
		
		// Find data to import
		$tables = $this->_getTableList();
		$supportedReaders =  $this->_getSupportedReaders();
		$backupFiles = scandir($backupLocation);
		foreach ($backupFiles as $filename) {
			$file = $backupLocation . $filename;
			if (!is_file($file)) {
				continue;
			}
			$fileExtension = get_file_extension($file);
			$importToTable = str_replace('.'.$fileExtension, false, $filename);

			$addToImport = false;
			
			if (strpos($importToTable, 'backup_export') !== false) {
				$addToImport = true;
			}
			
			if (in_array($fileExtension, $supportedReaders) && in_array($importToTable, $tables)) {
				$addToImport = true;
			}
			
			if ($addToImport) {
				$filesForImporting[] = array("file"=>$file, "importToTable"=> $importToTable, "reader"=>$fileExtension);
			}
			
		}

		if (empty($filesForImporting)) {
			BackupImportLogger::setLogInfo('The zip file has no files to import.');
			return;
		}

		$detectedLanguages = array();
		foreach ($filesForImporting as $file) {
		    if (strpos($file['file'], 'bg_lang') !== false) {
		        $detectedLanguages[] = 'bg';
            }
        }

        if (!empty($detectedLanguages)) {
            BackupImportLogger::setLogInfo('Its detected other languages in this import.');
            return array('must_choice_language' => true, 'detected_languages'=>$detectedLanguages);
        }

		// Decode files in zip
		$readedData = array();
		foreach ($filesForImporting as $file) {
			
			$readerClass = 'Microweber\\Utils\\Backup\\Readers\\' . ucfirst($file['reader']) . 'Reader';
			$reader = new $readerClass($file['file']);
			$data = $reader->readData();
			
			if (strpos($importToTable, 'backup_export') !== false) {
				$readedData = $data;
			} else if (strpos($importToTable, 'mw_content') !== false) {
				$readedData = $data;
			} else {
				if (!empty($data)) {
					if (isset($file['importToTable'])) {
						$readedData[$file['importToTable']] = $data;
					}
				}
			}
			
		}
		
		if (empty($readedData)) {
			BackupImportLogger::setLogInfo('The files in zip are empty. Nothing to import.');
			return;
		}
		
		return $readedData;
	}
	
	private function _getSupportedReaders() {
		
		$readers = array();
		$readersFolder = normalize_path(MW_PATH  . 'Utils/Backup/Readers');
		$readersList = scandir($readersFolder);
		foreach ($readersList as $file) {
			if (!is_file($readersFolder . $file)) {
				continue;
			}
			
			$ext = str_replace('Reader.php', false, $file);
			$ext = strtolower($ext);
			
			if ($ext == 'default' || $ext == 'zip') {
				continue;	
			}
			
			$readers[] = $ext;
			
		}
		
		return $readers;
	}
	
	private function _getTableList() {
		
		$readyTables = array();
		
		$tables = mw()->database_manager->get_tables_list();
		foreach ($tables as $table) {
			$readyTables[] = str_replace(mw()->database_manager->get_prefix(), false, $table);	
		}
		
		return $readyTables;
	}
	
	/**
	 * Remove dir recursive
	 * @param string $dir
	 */
	private function _removeFilesFromPath($dir)
	{
		if (!is_dir($dir)) {
			return;
		}
		
		$files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);

		foreach ($files as $fileinfo) {
			$todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
			@$todo($fileinfo->getRealPath());
		}

		@rmdir($dir);
	}
	
	private function _checkPathsExists() {
		
		if (userfiles_path()) {
			if (!is_dir(userfiles_path())) {
				mkdir_recursive(userfiles_path());
			}
		}
		
		if (media_base_path()) {
			if (!is_dir(media_base_path())) {
				mkdir_recursive(media_base_path());
			}
		}
	}
	
	/**
	 * Clone directory by path and destination
	 * @param stringh $source
	 * @param stringh $destination
	 * @return stringh|boolean
	 */
	private function _cloneDirectory($source, $destination)
	{
		if (is_file($source) and ! is_dir($destination)) {
			$destination = normalize_path($destination, false);
			$source = normalize_path($source, false);
			$destinationDir = dirname($destination);
			if (! is_dir($destinationDir)) {
				mkdir_recursive($destinationDir);
			}
			if (! is_writable($destination)) {
				// return;
			}

			return @copy($source, $destination);
		}

		if (! is_dir($destination)) {
			mkdir_recursive($destination);
		}

		if (is_dir($source)) {
			$dir = dir($source);
			if ($dir != false) {
				while (false !== $entry = $dir->read()) {
					if ($entry == '.' || $entry == '..') {
						continue;
					}
					if ($destination !== "$source/$entry" and $destination !== "$source" . DS . "$entry") {
						$this->_cloneDirectory("$source/$entry", "$destination/$entry");
					}
				}
			}

			$dir->close();
		}

		return true;
	}
}