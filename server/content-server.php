<?php
include 'Zipper.php';

//echo Zipper::beforeLast('/assets/index.php', 'php');

if(isset($_REQUEST['n'])  && isset($_REQUEST['i']) ){
    $content  =  Zipper::downloadZipItem($_REQUEST['n'],$_REQUEST['i']);
    header("Cache-Control: public");
    header("Content-Description: File Transfer");
    header("Content-Disposition: attachment; filename={$content['fileName']}");
    header("Content-Type: application/octet-stream");
    header("Content-Transfer-Encoding: binary");
    header("Content-length:".strlen($content['content']));
   echo $content['content'];
   exit;
}

if(isset($_GET['archive'])){
    $archiveContent  =  Zipper::unzip(__DIR__.'/files/'.$_GET['archive']);
    $feedback['archiveContent'] = $archiveContent;
    echo json_encode( $feedback) ;
}

if(isset($_FILES['file'])){
    echo  uploadFile('file');
}

if(isset($_GET['zipList'])){
    $files  = getZipList();
    $content =  '';
    if(count($files) >  0 )
     $content  = Zipper::unzip(__DIR__.'/files/'.$files[0]);

  echo  json_encode(['zipList'=>$files,
   'content'=>$content['content'] ?? '',
    'info'=>$content['info'] ?? '' ]) ;
}


if(isset($_GET['dispaly'])){
    $content =  Zipper::getzipEntryContent(
        $_GET['zipName'],
         (int) $_GET['dispaly'],
        $_GET['type'],
        $_GET['name'],
        $_GET['path']

    );
    echo json_encode(['content'=>$content]) ;

}

function uploadFile($name){
    $target_dir = __DIR__. "/files/";
    $target_file = $target_dir . basename($_FILES[$name]["name"]);
    $feedback  = [];

    if(!file_exists($target_file)){
        if (move_uploaded_file($_FILES[$name]["tmp_name"], $target_file)) {
            $zipname  = basename($_FILES[$name]["name"]);
            $feedback['zipName']=$zipname;

            if(isset($_POST['archive'])){
                $archiveContent  =  Zipper::unzip(__DIR__.'/files/'.$zipname);
                $feedback['archiveContent'] =  $archiveContent['content'];
                $feedback['info'] =  $archiveContent['info'];
            }
        } else {
            $feedback['error']='Sorry, there was an error while uploading your file.';
        }
    }else{
        $feedback['error']=$_FILES[$name]["name"].' already exists';
    }
    
    return json_encode( $feedback) ;

}



function getZipList(){
$dirpath = __DIR__.'/files';
$files  =  [];
$content =  scandir($dirpath);
foreach($content as $value){
  if(!in_array($value, ['.','..']))
  $files[] = $value;
}
return $files ;
       
}


?>