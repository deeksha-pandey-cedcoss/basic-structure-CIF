<?php

use Phalcon\Cli\Task;

class MainTask extends Task
{
    public function deleteLogsAction(){
        $ar=[];
        $path = BP.DS.'var'.DS.'log'.DS.'shopify';

        if ($handle = opendir($path)) {
            while (false !== ($file = readdir($handle))) {
                if ('.' !== $file && '..' !== $file) {
                    $ar[]=$file;
                }
            }
            closedir($handle);
        }
//        print_r($ar);

        foreach ($ar as $key=>$value){
            if(is_dir($path."/".$value."/inventory"))
                $arr=$this->deleteDir($path,$value,'inventory');
            if(is_dir($path."/".$value."/product"))
                $arr=$this->deleteDir($path,$value,'product');
            if(is_dir($path."/".$value."/locations"))
                $arr=$this->deleteDir($path,$value,'locations');
            echo "\n";
            print_r($value);
            print_r($arr);
        }
    }

    public function deleteDir($path,$value,$subdir){
        $arr=[];
        $paths=$path."/".$value."/".$subdir;
        $count=0;
        if ($handle = opendir($paths)) {
            while (false !== ($file = readdir($handle))) {

                if ('.' !== $file && '..' !== $file && is_dir($paths."/".$file)===true) {

                    $date = new \DateTime($file);
                    $currdate = new \DateTime(date("Y-m-d"));
                    if ($date->diff($currdate)->days <= 10) {
                        $count=1;
                        $arr[] = $file . " days diff=" . $date->diff($currdate)->days;
                    } else {
                        $f = glob($paths . "/" . $file . '/*');
                        foreach ($f as $fil) {
                            if (is_file($fil))
                                unlink($fil);
                        }
                        rmdir($paths . "/" . $file);
                    }

                }

            }
            if($count!=1 && is_file($paths."/inventory.log")==true){
                unlink($paths."/inventory.log");
            }
            closedir($handle);
        }
        return $arr;
    }

    public function mainAction()
    {
        echo 'Here we can upgarde the system, can see the status of all modules.
			  As well as can enable and disable the modules.

			    php app/cli setup status

			    php app/cli setup upgrade

			    php app/cli setup enable module_name

			    php app/cli setup disable module_name
			' . PHP_EOL;
    }

}