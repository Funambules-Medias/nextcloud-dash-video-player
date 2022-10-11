<?php

namespace OCA\Dashvideoplayer\Migration;


class CheckMimeType
{
    const CUSTOM_MIMETYPEMAPPING = 'mimetypemapping.json';

    private function check()
    {
        $mappingFile = \OC::$configDir . self::CUSTOM_MIMETYPEMAPPING;

        if (file_exists($mappingFile)) {
            $mapping = json_decode(file_get_contents($mappingFile), true);
            if (json_last_error() === JSON_ERROR_NONE) {
                if (isset($mapping['mpd']) || isset($mapping['m3u8'])) return true;
            }
        }

        return false;
    }

    public function run()
    {
        return $this->check();
    }
}
