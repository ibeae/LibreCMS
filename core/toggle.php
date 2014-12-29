<?php
session_start();
include'db.php';
$id=filter_input(INPUT_GET,'id',FILTER_SANITIZE_NUMBER_INT);
$bit=filter_input(INPUT_GET,'b',FILTER_SANITIZE_NUMBER_INT);
$tbl=filter_input(INPUT_GET,'t',FILTER_SANITIZE_STRING);
$col=filter_input(INPUT_GET,'c',FILTER_SANITIZE_STRING);
$ti=time();
if($tbl!='NaN'&&$col!='NaN'){
	$q=$db->prepare("SELECT $col as c FROM $tbl WHERE id=:id");
	$q->execute(array(':id'=>$id));
	$r=$q->fetch(PDO::FETCH_ASSOC);
	if($r[c]{$bit}==1){
		$r[c]{$bit}=0;
		$w=0;
	}else{
		$r[c]{$bit}=1;
		$w=1;
	}
	$q=$db->prepare("UPDATE $tbl SET $col=:c WHERE id=:id");
	$q->execute(array(':c'=>$r[c],':id'=>$id));
}?>
<script>/*<![CDATA[*/
	window.top.window.$('#<?php echo$tbl.$col.$bit;?>').remove();
/*]]>*/</script>