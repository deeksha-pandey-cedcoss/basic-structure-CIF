<?php

namespace App\Connector\Models\Profile\Attribute\Type;
use Phalcon\Events\Manager as EventsManager;

class Fixed 
{
	public function changeData($key , $mappedData , $data)
	{
		$data[$key] = $mappedData['value'];
		return $data;
	}
	
}