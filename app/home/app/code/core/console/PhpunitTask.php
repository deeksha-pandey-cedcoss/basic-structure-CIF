<?php

use Phalcon\Cli\Task;


class PhpunitTask extends Task
{
    public function generateconfigAction()
    {
        $dom = new \DOMDocument();

        $dom->encoding = 'utf-8';

        $dom->xmlVersion = '1.0';

        $dom->formatOutput = true;
        $phpunit = $dom->createElement('phpunit');
        $this->addAttribute($phpunit,'bootstrap',BP.'/app/phpunit.php')
            ->addAttribute($phpunit,'backupGlobals',"false")
            ->addAttribute($phpunit,'backupStaticAttributes',"false")
            ->addAttribute($phpunit,'verbose',"true")
            ->addAttribute($phpunit,'colors',"true")
            ->addAttribute($phpunit,'convertErrorsToExceptions',"true")
            ->addAttribute($phpunit,'convertNoticesToExceptions',"true")
            ->addAttribute($phpunit,'convertWarningsToExceptions',"true")
            ->addAttribute($phpunit,'processIsolation',"false")
            ->addAttribute($phpunit,'stopOnFailure',"false")
            /*->addAttribute($phpunit,'syntaxCheck',"true")*/;
        $xml_file_name = BP.'/app/phpunit.xml';

        $this->prepareTestSuite($dom,$phpunit);
        $this->prepareFilter($dom,$phpunit);

        $dom->appendChild($phpunit);
        $dom->save($xml_file_name);

    }

    public function prepareTestSuite($dom,&$phpunit){
        $modules = $this->di->getObjectManager()->get('App\Core\Components\Helper')->getAllModules();
        $testsuite = $dom->createElement('testsuite');
        $this->addAttribute($testsuite,'name','Phalcon - Testsuite');

        foreach($modules as $module => $active){
            $directory = $dom->createElement('directory',BP.'/app/code/'.$module);
            $testsuite->appendChild($directory);
        }
        $phpunit->appendChild($testsuite);
    }


    public function prepareFilter($dom,&$phpunit){
        $filter = $dom->createElement('filter');
        $whitelist = $dom->createElement('whitelist');

        $modules = $this->di->getObjectManager()->get('App\Core\Components\Helper')->getAllModules();

        //$this->addAttribute($testsuite,'name','Phalcon - Testsuite');

        foreach($modules as $module => $active){
            $directory = $dom->createElement('directory',BP.'/app/code/'.$module.'/Test');
            $whitelist->appendChild($directory);
        }
        $filter->appendChild($whitelist);

        $phpunit->appendChild($filter);
    }


    public function addAttribute(&$node,$attribute,$value){
        $attribute = new DOMAttr($attribute, $value);
        $node->setAttributeNode($attribute);
        return $this;
    }
}