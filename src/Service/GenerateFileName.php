<?php

namespace App\Service;

use Symfony\Component\Uid\Uuid;

class GenerateFileName 
{
    public static function getFileName($fileName): string
    {
        $metadados = [
            'hash' => Uuid::v1(),
            'extension' => $fileName->getClientOriginalExtension(),
        ];

        return $metadados['hash'] . '.' . $metadados['extension'];
    }
}