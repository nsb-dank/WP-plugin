<html>
<head>
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
</head>
<body>
<!-- Menu Start -->
<div id="dir_tree">
<?php
function dispTree($path){
	foreach(createDir($path) as $html){
		echo $html;
	}
}

function createDir($path = '.'){
	if ($handle = opendir($path))
	{
		$html[] = '<ul>';
		$queue = array();
		while (false !== ($file = readdir($handle)))
		{
			if (is_dir($path.$file) && $file != '.' && $file !='..') {
				$html[] = '<li><span class="dir"><span class="ion-folder"> '.$file.'</span></span>';
				$html = array_merge($html, createDir($path.$file.'/'));
				$html[] = '</li>';
			} else if ($file != '.' && $file !='..') {
				$queue[] = $file;
			}
		}
		foreach ($queue as $file)
		{
			$permalink = preg_replace('!http(s)?://' . $_SERVER['SERVER_NAME'] . '/!', '/', $path);
			$html[] = '<li><a href='.$permalink.$file.'><span class="ion-document"> '.$file.'</span></a></li>';
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
</body>
</html>