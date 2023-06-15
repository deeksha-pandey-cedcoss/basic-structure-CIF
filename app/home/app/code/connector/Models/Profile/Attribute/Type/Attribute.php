<?php

namespace App\Connector\Models\Profile\Attribute\Type;

class Attribute
{
    public function changeData($key, $mappedData, $data)
    {
        if (is_array($mappedData['value'])) {
            $data[$key] = $data[$mappedData['value'][0]];

        } else {
            $data[$key] = $data[$mappedData['value']];

        }
        return $data;
    }

}
