<?php
if(!isset($act)){
    $act=isset($_POST['act'])?filter_input(INPUT_POST,'act',FILTER_SANITIZE_STRING):filter_input(INPUT_GET,'act',FILTER_SANITIZE_STRING);
}
if($act=='logout'){
    $_SESSION=array();
    $_SESSION['loggedin']=false;
    $_SESSION['rank']=0;
}elseif($act=='login'||(isset($_SESSION['loggedin'])&&$_SESSION['loggedin']==true)){
    $username=isset($_POST['username'])?filter_input(INPUT_POST,'username',FILTER_SANITIZE_STRING):$_SESSION['username'];
    $password=isset($_POST['password'])?filter_input(INPUT_POST,'password',FILTER_SANITIZE_STRING):$_SESSION['password'];
    $q=$db->prepare("SELECT * FROM login WHERE username=:username AND activate='' AND active='1' LIMIT 1");
    $q->execute(array(':username'=>$username));
    $user=$q->fetch(PDO::FETCH_ASSOC);
    if($user['id']!=0){
        if(password_verify($password,$user['password'])){
            $_SESSION['username']=$user['username'];
            $_SESSION['password']=$password;
            $_SESSION['uid']=$user['id'];
            $_SESSION['rank']=$user['rank'];
            $_SESSION['loggedin']=true;
        }else{
            $_SESSION=array();
            $_SESSION['loggedin']=false;
            $_SESSION['rank']=0;
        }
    }else{
        $_SESSION=array();
        $_SESSION['loggedin']=false;
        $_SESSION['rank']=0;
    }
}else{
    $_SESSION=array();
    $_SESSION['loggedin']=false;
    $_SESSION['rank']=0;
}
if(isset($_SESSION['loggedin'])&&$_SESSION['loggedin']==true){
    $q=$db->prepare("UPDATE login SET lti=:lti WHERE id=:id");
    $q->execute(array(':lti'=>time(),':id'=>$_SESSION['uid']));
}
