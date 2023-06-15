<?php

namespace App\Connector\Components;

class ImportHelper extends \App\Core\Components\Base
{

    public function getBaseAttributes()
    {
        return (new \App\Connector\Models\Product())->getBaseAttributes();
    }

    public function getMappingSuggestions($sourceAttrs, $user_id = false, $source = false, $target = 'connector')
    {
        $suggestion = $this->di->getObjectManager()->get('suggestionHelper')
            ->getSuggestion($sourceAttrs, $user_id, $source, $target);
        return $suggestion;
    }
}
