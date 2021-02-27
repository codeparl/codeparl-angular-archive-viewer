<?php



class Zipper {
    

    /**
   * @param String $ext the file extention
   * to determine its language
   * @return String|null the language or null if not found
   */
    public  static function getExtension($ext='html', $reverse=false){
    $langs  =  file_get_contents(__DIR__.'/languages.json');
    $langs  =  json_decode($langs, true);
    if(!$reverse):
    foreach ($langs as $lang => $exts) :

        if(self::contains($exts, '|')){
            $extesions  = explode('|',$exts);
            foreach ($extesions as  $x)
               if($x  === $ext )
                 return  $lang;
         }elseif($exts === $ext)
         return $lang;

    endforeach;
else:
 if(isset($langs[$ext]))
  return  explode('|',$langs[$ext])[0];
endif;

return null;
}


public static function downloadZipItem($zipName, $index){
    $path =  __DIR__.'/files/'.  $zipName;
    $content =  null;
    $filename = '';
    $zip = new ZipArchive();
    if ($zip->open($path) == TRUE) {
        $content  =  $zip->getFromIndex($index);
        $filename = $zip->statIndex($index)['name'];
        $zip->close();
       }
       return  ['content'=>$content, 'fileName'=>$filename];
}


public static function unzip($path){
    if($path ===  null ) return 'File not found.';
    $info = [];
    $zip = new ZipArchive();
    $zipName  = pathinfo($path,PATHINFO_FILENAME)  ;
    $entryList  = '<ul class="list-group content-list "  data-entry="'.$zipName.'">';
    if ($zip->open($path) == TRUE) {
        for ($i = 0; $i < $zip->numFiles; $i++)
            $entryList .= self::list($zip, $i,$info);
        $zip->close();
       }
    return  ['content'=>$entryList.'</ul>', 'info'=>$info]  ;
    }


    private  static function list($zip, $index, &$info){
        $list = '';
    
        //set the prerequisit inputs
        $stat =  $zip->statIndex($index); //an array of file statistics or details
        $filename =  $stat['name']; // entry name
        $size =  $stat['size']; //entry size
      
      
        $isFile  =  strstr($filename, '/') === false;
        $anyFile  = (preg_match('/(\..+)$/',$filename) && $size > 0) || $size > 0;
        $type  = $anyFile ? 'file' : 'folder' ;
        $icon = ($type === 'file'  ? ' fa-file text-info' : ' fa-folder text-warning' );
        $plus  =  ( $type === 'folder' ? '<i class="fa fa-plus  mr-1" ></i>'  : '' );
        $x =  substr($filename,0,strpos($filename, '/'));
        

        if(($size === 0 && $x.'/' === $filename ) || $isFile ){

            $info[$index] = 
            ['type'=>$type,'path'=>$filename, 'size'=>$size, 'open'=>'false'];

          

            $x = str_replace('/','',$filename);
            $data  = 'data-index="'.$index.'" data-open="false" data-type="'.$type.'" data-path="'.$filename.'"';
            $list .='<li  class="list-group-item py-1 index-'.$index.'" '.$data.' id="'.$index.'">';
            $list .=  $plus.'<i class="fa '.$icon.' mr-1 "></i>';
            $list .='<a  class="btn btn-link shadow-none ">'.$x.'</a></li>';
         }
    
    
    return $list;
    }


    public static function getzipEntryContent($zipName, $index, $type, $fName, $fPath){
        $fPath =  self::beforeLast($fPath, '/');
        $info = [];
        $path =  __DIR__.'/files/'.  $zipName;
        $entryList  ='<ul class="list-group content-list "  data-entry="'.$zipName.'">';
        $zip = new ZipArchive();
        $content  =  '';
        $fileType = '';
        $validEncoding = false;
        if ($zip->open($path) == TRUE) {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $stat =  $zip->statIndex($i);
                $thisName =  $stat['name'];
                if($i == $index){
                    $index =  $i;
                    if($type === 'file'){
                        $content =  $zip->getFromIndex($index);
                        
                        $fileType =  pathinfo($fName,PATHINFO_EXTENSION);
                        $validEncoding =   (mb_check_encoding($content  ,'utf-8')  ? true : false );
                        
                        $info[$index]['fileType'] = self::getExtension($fileType); 
                        $info[$index]['name'] =  $thisName;
                        $info[$index]['type']=$type; 
                        $info[$index]['size']=$stat['size']; 
                      
                        if(preg_match('/(jpg|jpeg|png|gif)/', $fileType)){
                            $content =  "data:image/$fileType;base64,".base64_encode($content);
                            $info[$index]['fileType'] = $fileType; 
                        }else if($validEncoding === false ){
                            $content = null;
                        }
                          break;
                    }
                }
    
                if($type === 'folder'){
                    $index =  $i;
                    $dir  = substr($thisName, 0, strlen($fPath));
                    if($dir ===  $fPath && $thisName !== $fPath.'/'){
                         $size =  $stat['size'];
                         $isFile  =  self::after($thisName, $dir.'/');
                         $isFile =  self::contains($isFile, '/') === false && $size > 0;
                         $anyFile  = (preg_match('/(\..+)$/',$thisName) && $size > 0) || $size > 0 ;
                         $thisType  = $anyFile ? 'file' : 'folder' ;
                         $icon = ($anyFile  ? 'fa-file text-info' : 'fa-folder text-warning' );
                         $plus  =  $thisType === 'folder'  ? ' <i class="fa fa-plus mr-1" ></i> '  : '' ;
                         $thisFolder = self::thisFolder($thisName,$fPath, $size,$dir); 
                                           
                        if( $thisFolder  || $isFile ){

                        
                           $thisName =  self::after($thisName, $dir.'/');
                           $x= str_replace('/','',$thisName );
                            $thisPath  = $dir.'/'.$thisName;
                            $info[$i] = ['type'=>$thisType,'size'=>$size,'path'=>$thisPath, 'open'=>'false'];  
                            
                            $data  = 'data-index="'.$i.'" data-open="false" data-type="'.$thisType.'" data-path="'.$thisPath.'"';
                            $entryList  .='<li class="list-group-item py-1 index-'.$index.'" '.$data.'>';
                            $entryList  .=  $plus.'<i class="fa '.$icon.' mr-1 "></i>' ;
                            $entryList  .='<a  class="btn btn-link shadow-none ">'.$x .'</a></li>';
                         }
                    }
                }
            }

            $zip->close();
      }//open zip
    
      if($type == 'folder')
         $content = $entryList.'</ul>' ;
    
      if(preg_match('/(\..+?)$/',$fPath) == false){
        $fPath= $fPath.'/'.$fName;
        if(self::startsWith($fPath,'/'))
        $info[$index]['name']  = self::after($fPath,'/');
        else 
        $info[$index]['name']  = $fPath;
      }   
    
    $info[$index]['isText'] =$validEncoding; 
   

    return  ['content'=>$content, 'info'=>$info ];
      }
    
      private static function thisFolder ($thisName,$fPath, $size){
        $thisName =    self::after($thisName, $fPath.'/');
      $t =   self::substrCount($thisName, '/', 1);
       return  $size === 0 &&  $t === 1;
      }

      public static function beforeLast($haystack, $needle){
        return substr($haystack, 0,strrpos($haystack, $needle));
      }

      public static function after($string, $substring){

        $pos = strpos($string, $substring);
         if ($pos === false) {
             return $string;
         } else {
             return substr($string, $pos + strlen($substring));
         }

      
      }

      public static function substrCount($haystack, $needle){
         return  substr_count($haystack,$needle) ;
      }

      public static function contains($haystack, $needle){
        return  strstr($haystack,$needle) !== false;
     }

     public static function startsWith($haystack, $needle){
        return  strpos($haystack,$needle) === 0;
     }
}