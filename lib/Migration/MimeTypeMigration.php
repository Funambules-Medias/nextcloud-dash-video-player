<?php

namespace OCA\Dashvideoplayer\Migration;

use OCP\Files\IMimeTypeLoader;
use OCP\Migration\IRepairStep;

abstract class MimeTypeMigration implements IRepairStep
{
    const CUSTOM_MIMETYPEMAPPING = 'mimetypemapping.json';

    protected $mimeTypeLoader;

    public function __construct(IMimeTypeLoader $mimeTypeLoader)
    {
        $this->mimeTypeLoader = $mimeTypeLoader;
    }
}
