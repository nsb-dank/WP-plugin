<link rel="stylesheet" type="text/css" href="//code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css">

<style type="text/css">
#dir_tree ul {
  list-style-type: none;
  padding-left: 1em;
  margin-bottom:1em;
}
#dir_tree ul:first-child {
  padding-left: 0;
}
#dir_tree a, #dir_tree li {
  text-decoration: none;
}
</style>

<!-- Menu Start -->
<div id="dir_tree">
<?php
/********************************
 *  フォルダツリー
 *********************************/
function dispTree($path,$filepath){
	foreach(createDir($path,$filepath) as $html){
		echo $html;
	}
}

function createDir($path = '.',$filepath){
	if ($handle = opendir($path))
	{
		$html[] = '<ul>';
		$queue = array();
		while (false !== ($file = readdir($handle)))
		{
			if (is_dir($path.$file) && $file != '.' && $file !='..') {
				$html[] = '<li><span class="dir"><span class="ion-folder"> '.$file.'</span></span>';
				$html = array_merge($html, createDir($path.$file.'/',$filepath.'/'.$file));
				$html[] = '</li>';
			} else if ($file != '.' && $file !='..') {
				$queue[] = $file;
			}
		}
		foreach ($queue as $file)
		{
			$permalink= plugins_url().'/wp-geolib/files'.$filepath.'/'.$file;
			$html[] = '<li>　<a href="'.$permalink.'" target="_blank"><span class="ion-document"> '.$file.'</span></a></li>';
		}
		$html[] = '</ul>';
		return $html;
	}
}
?>
</div>
<!-- End Menu -->

<script src="//ajax.googleapis.com/ajax/libs/jquery/1.3.2/jquery.min.js"></script>
<script>
$(function() {
  $("span.dir").css("cursor", "pointer").prepend("+ ").click(function() {
    $(this).next().toggle("fast");

    var v = $(this).html().substring( 0, 1 );
    if ( v == "+" )
      $(this).html( "-" + $(this).html().substring( 1 ) );
    else if ( v == "-" )
      $(this).html( "+" + $(this).html().substring( 1 ) );
  }).next().hide();

  $("#dir_tree a, #dir_tree span.dir").hover(function() {
      $(this).css("font-weight", "bold");
  }, function() {
      $(this).css("font-weight", "normal");
  });
});
</script>
<?php
/********************************
 *  ファイルアップロード
 *********************************/
//複数ファイルアップロード
function fileUpload($filename,$path){
	for ($i=0; $i<count($_FILES[$filename]['name']); $i++) {
		$file_ext = pathinfo($_FILES[$filename]["name"][$i], PATHINFO_EXTENSION);
		if (/*FileExtensionGetAllowUpload($file_ext) && */ is_uploaded_file($_FILES[$filename]["tmp_name"][$i])) {
			if(move_uploaded_file($_FILES[$filename]["tmp_name"][$i],  $path. '/'.$_FILES[$filename]["name"][$i])) {
				echo $_FILES[$filename]["name"][$i] . "をアップロードしました。<br>";
			} else {
				echo "ファイルをアップロードできません。<br>";
			}
		} else {
			echo "ファイルが選択されていません。<br>";
		}
	}
}
//単一ZIPファイルをアップロードしてそのディレクトリに解凍
	function zipFileUpload($filename,$path){
		$file_ext = pathinfo($_FILES[$filename]["name"], PATHINFO_EXTENSION);
		if (/*FileExtensionGetAllowUpload($file_ext) && */ is_uploaded_file($_FILES[$filename]["tmp_name"])) {
			removeFolder($path);
			mkdir($path,0777,TRUE);
			chmod($path, 0777);
			if(move_uploaded_file($_FILES[$filename]["tmp_name"],  $path. '/'.$_FILES[$filename]["name"])) {
				echo $_FILES[$filename]["name"] . "をアップロードしました。<br>";
				$zip_path =  $path. '/'.$_FILES[$filename]["name"];
				$unzip_dir =  $path;
				unzip($zip_path,$unzip_dir,0755);
			} else {
				echo "ファイルをアップロードできません。<br>";
			}
		} else {
			echo "ファイルが選択されていません。<br>";
		}

}

/********************************
 *  ファイルダウンロード
 *********************************/
function fileDownload($path,$file_name){
	//ダウンロードをしたいファイル名のパス
	$file_path = $path.'/'.$file_name;
	//ダウンロード時のファイル名
	$download_file_name = $file_name;
	//タイプをダウンロードと指定
	header('Content-Type: application/force-download;');
	//ファイルのサイズを取得してダウンロード時間を表示する
	header('Content-Length: '.filesize($file_path));
	//ダウンロードの指示・ダウンロード時のファイル名を指定
	header('Content-Disposition: attachment; filename="'.$file_name.'"');
	//ファイルを読み込んでダウンロード
	readfile($download_file_name);
}
/********************************
 *  Zip解凍
 *********************************/
function unzip($zip_path, $unzip_dir, $file_mod = 0755) {
	$zip = new ZipArchive();
	if ($zip->open($zip_path) !== TRUE){
		return FALSE;
	}

	$unzip_dir = (substr($unzip_dir, -1) == '/') ? $unzip_dir : $unzip_dir.'/';
	for ($i = 0; $i < $zip->numFiles; $i++){
		if(file_exists($unzip_dir.$zip->getNameIndex($i))){
			@unlink($unzip_dir.$zip->getNameIndex($i));
		}
	}
	if ($zip->extractTo($unzip_dir) !== TRUE) {
		$zip->close();
		return FALSE;
	}

	$files = [];
	for ($i = 0; $i < $zip->numFiles; $i++) {
		$files[] = $zip->getNameIndex($i);
		if(file_exists($unzip_dir.$zip->getNameIndex($i))){
			chmod($unzip_dir.$zip->getNameIndex($i), $file_mod);
		}
	}
	$zip->close();
	return $files;
}

/********************************
 *  Zip圧縮
 *********************************/
function zip($files, $zip_path, $mode = 0755) {
	if (file_exists($zip_path))
		@unlink($zip_path);

		$zip = new ZipArchive();
		if ($res = $zip->open($zip_path, ZipArchive::CREATE) !== true)
			throw new Exception("Zip create error. ZipArchive error code : ".(string)$res);

			foreach ($files as $file) {
				if ($zip->addFile($file, basename($file)) == FALSE) {
					$zip->close();
					@unlink($zip_path);
					throw new Exception("Zip create error. ZipArchive error file : ".(string)$file);
				}
			}
			$zip->close();
			chmod($zip_path, $mode);
}

/********************************
 *  ファイル数カウント
 *********************************/
function fileCount($path,$filetype){
	$filecount = 0;
	$files = glob($path . $filetype);
	if ($files){
		$filecount = count($files);
	}
	return $filecount;
}

/********************************
 *  フォルダ作成
 *********************************/
function createFolder($path){
	mkdir($path, '0777',TRUE);
}
/********************************
 *  フォルダ内すべて削除
 *********************************/
function removeFolder($path){
	$list = scandir($path); $length = count($list);
	for($i=0; $i<$length; $i++){
		if($list[$i] != '.' && $list[$i] != '..'){
			if(is_dir($path.'/'.$list[$i])){
				removeFolder($path.'/'.$list[$i]);
			}else{
				unlink($path.'/'.$list[$i]);
			}
		}
	}
	rmdir($path);
}
?>