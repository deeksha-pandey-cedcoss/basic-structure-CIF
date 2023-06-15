<?php

namespace App\Core\Components;


use Phalcon\Translate\Adapter\NativeArray;
use Phalcon\Translate\InterpolatorFactory;
use Phalcon\Translate\TranslateFactory;

class Translation extends Base
{
    protected $locale = false;

    public function setDi(\Phalcon\Di\DiInterface $di):void
    {
        
        parent::setDi($di);
       
        if( !$this->locale ){
       
            $this->registerTranslation();
        }
        
    }

    /**
     * Registers locale to DI
     *
     * @return void.
     *
     */
    public function registerTranslation()
    {
        $headers = $this->di->getRequest()->getHeaders();
        $localeCode = $headers['Locale'] ?? ($headers['locale'] ?? 'en');
        
        if (!($translations = $this->di->getCache()->get('locale_content_'.$localeCode) )) {
            $translations = [];
            $modules = $this->di
                ->getObjectManager()
                ->get('App\Core\Components\Helper')
                ->getAllModules();
            
            foreach ($modules  as $name => $status) {
                $filePath = CODE.DS.$name.DS.'translation'. DS . $localeCode .'.php';
                if (file_exists($filePath)) {
                    $moduleTranslations = require $filePath;
                    $translations = array_merge($translations, $moduleTranslations);
                }
            }
            
            
            $this->di->getCache()->set('locale_content_'.$localeCode, $translations);
        }
        
        $interpolator = new InterpolatorFactory();
        $factory      = new TranslateFactory($interpolator);
        $locale = $factory->newInstance(
            'array',
            [
                'content' => $translations,
            ]
        );

        $this->di->set('locale', $locale);

        $this->locale = $locale;
    }


}
