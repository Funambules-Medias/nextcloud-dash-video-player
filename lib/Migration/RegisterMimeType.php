<?php

namespace OCA\Dashvideoplayer\Migration;

use OCP\Migration\IOutput;

class RegisterMimeType extends MimeTypeMigration
{
    public function getName()
    {
        return 'Register MIME type for .mpd files';
    }

    private function registerForExistingFiles()
    {
        $mimeTypeId = $this->mimeTypeLoader->getId('application/mpd');
        $this->mimeTypeLoader->updateFilecache('mpd', $mimeTypeId);
    }

    private function registerForNewFiles()
    {
        $mapping = array(
            'mpd' => array('application/mpd')
        );
        $mappingFile = \OC::$configDir . self::CUSTOM_MIMETYPEMAPPING;

        if (file_exists($mappingFile)) {
            $existingMapping = json_decode(file_get_contents($mappingFile), true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $mapping = array_merge($existingMapping, $mapping);
            }
        }

        file_put_contents($mappingFile, json_encode($mapping, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    }

    public function run(IOutput $output)
    {
        $output->info('Registering the mimetype...');

        // Register the mime type for existing files
        $this->registerForExistingFiles();

        // Register the mime type for new files
        $this->registerForNewFiles();

        $output->info('The mimetype was successfully registered.');
    }
}
