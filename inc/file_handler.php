<?php

class iSell_File_Handler{
	private $post_id;
	private $file_name;
	private static $directory;
	private $extension;
	private $file;
	private $directory_path;
	function __construct($post_id=0,$file_name=NULL,$file=NULL){
		$isell_options = isell_get_options();
		$this->directory = $isell_options['file_management']['directory_name'];
		$this->post_id = $post_id;
		$this->file_name = $file_name;
		$this->file = $file;
		$this->directory_path = ABSPATH . $this->directory;
		if ($file != NULL ){
			$this->validate();
			$this->upload_file();
		}
	}
	function upload_file(){
		if ( !file_exists($this->directory_path) ){
			@mkdir($this->directory_path);
			//@chmod($this->directory_path,'0755');
		}
		if ( !file_exists($this->directory_path.DIRECTORY_SEPARATOR.$this->post_id) ){
			@mkdir($this->directory_path.DIRECTORY_SEPARATOR.$this->post_id);
			//@chmod($this->directory_path,'0755');
		}
		
		$move_path = $this->directory_path.DIRECTORY_SEPARATOR.$this->post_id.DIRECTORY_SEPARATOR.$this->file_name;
		
		$targetDir = $this->directory_path.DIRECTORY_SEPARATOR.$this->post_id;

		$cleanupTargetDir = true; // Remove old files
		$maxFileAge = 5 * 3600; // Temp file age in seconds

		// 5 minutes execution time
		@set_time_limit(5 * 60);

		// Uncomment this one to fake upload time
		// usleep(5000);

		// Get parameters
		$chunk = isset($_REQUEST["chunk"]) ? intval($_REQUEST["chunk"]) : 0;
		$chunks = isset($_REQUEST["chunks"]) ? intval($_REQUEST["chunks"]) : 0;
		$fileName = isset($_REQUEST["name"]) ? $_REQUEST["name"] : '';

		// Clean the fileName for security reasons
		$fileName = preg_replace('/[^\w\._]+/', '_', $fileName);

		// Make sure the fileName is unique but only if chunking is disabled
		if ($chunks < 2 && file_exists($targetDir . DIRECTORY_SEPARATOR . $fileName)) {
			$ext = strrpos($fileName, '.');
			$fileName_a = substr($fileName, 0, $ext);
			$fileName_b = substr($fileName, $ext);

			$count = 1;
			while (file_exists($targetDir . DIRECTORY_SEPARATOR . $fileName_a . '_' . $count . $fileName_b))
				$count++;

			$fileName = $fileName_a . '_' . $count . $fileName_b;
		}

		$filePath = $targetDir . DIRECTORY_SEPARATOR . $fileName;

		// Create target dir
		if (!file_exists($targetDir))
			@mkdir($targetDir);

		// Remove old temp files	
		if ($cleanupTargetDir && is_dir($targetDir) && ($dir = opendir($targetDir))) {
			while (($file = readdir($dir)) !== false) {
				$tmpfilePath = $targetDir . DIRECTORY_SEPARATOR . $file;

				// Remove temp file if it is older than the max age and is not the current file
				if (preg_match('/\.part$/', $file) && (filemtime($tmpfilePath) < time() - $maxFileAge) && ($tmpfilePath != "{$filePath}.part")) {
					@unlink($tmpfilePath);
				}
			}

			closedir($dir);
		} else
			die(json_encode(array(
						'status' => '3',
						'message' => 'Failed to open temp directory.'

					)));


		// Look for the content type header
		if (isset($_SERVER["HTTP_CONTENT_TYPE"]))
			$contentType = $_SERVER["HTTP_CONTENT_TYPE"];

		if (isset($_SERVER["CONTENT_TYPE"]))
			$contentType = $_SERVER["CONTENT_TYPE"];

		// Handle non multipart uploads older WebKit versions didn't support multipart in HTML5
		if (strpos($contentType, "multipart") !== false) {
			if (isset($_FILES['file']['tmp_name']) && is_uploaded_file($_FILES['file']['tmp_name'])) {
				// Open temp file
				$out = fopen("{$filePath}.part", $chunk == 0 ? "wb" : "ab");
				if ($out) {
					// Read binary input stream and append it to temp file
					$in = fopen($_FILES['file']['tmp_name'], "rb");

					if ($in) {
						while ($buff = fread($in, 4096))
							fwrite($out, $buff);
					} else
						die(json_encode(array(
							'status' => '3',
							'message' => 'Failed to open input stream.'

						)));
					fclose($in);
					fclose($out);
					@unlink($_FILES['file']['tmp_name']);
				} else
					die(json_encode(array(
						'status' => '4',
						'message' => 'Failed to open output stream.'

					)));


			} else
				die(json_encode(array(
						'status' => '4',
						'message' => 'Failed to move uploaded file.'

					)));
		} else {
			// Open temp file
			$out = fopen("{$filePath}.part", $chunk == 0 ? "wb" : "ab");
			if ($out) {
				// Read binary input stream and append it to temp file
				$in = fopen("php://input", "rb");

				if ($in) {
					while ($buff = fread($in, 4096))
						fwrite($out, $buff);
				} else
					die(json_encode(array(
						'status' => '3',
						'message' => 'Failed to open input stream.'

					)));

				fclose($in);
				fclose($out);
			} else
				die(json_encode(array(
						'status' => '3',
						'message' => 'Failed to open input stream.'

					)));
		}

		// Check if file has been uploaded
		if (!$chunks || $chunk == $chunks - 1) {
			// Strip the temp .part suffix off 
			@rename("{$filePath}.part", $filePath);
			update_post_meta($this->post_id,'product_file',$move_path);
			update_post_meta($this->post_id,'product_contains_file',true);
			update_post_meta($this->post_id,'orginal_file_name',$this->file_name);
			return true;
		}



		return false;
		
	}
	function validate(){
		$this->file_name = preg_replace('/[^\w\._]+/', '_', $this->file_name);
	}
	function delete_file($post_id){

		$delete_path = get_post_meta($post_id,'product_file',true);

		$result = @unlink($delete_path);
		delete_post_meta($this->post_id,'product_file');
		delete_post_meta($this->post_id,'product_contains_file');
		delete_post_meta($this->post_id,'orginal_file_name');
		return $result;
	}
	


}

?>