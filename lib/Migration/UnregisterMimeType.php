<?php

namespace OCA\Drawio\Migration;

use OCP\Migration\IOutput;

class UnregisterMimeType extends MimeTypeMigration
{
    public function getName()
    {
        return 'Unregister MIME type for .mpd files';
    }

    private function unregisterForExistingFiles()
    {
        $mimeTypeId = $this->mimeTypeLoader->getId('application/mpd');
        $this->mimeTypeLoader->updateFilecache('mpd', $mimeTypeId);
    }

    private function unregisterForNewFiles()
    {
        $mappingFile = \OC::$configDir . self::CUSTOM_MIMETYPEMAPPING;

        if (file_exists($mappingFile)) {
            $mapping = json_decode(file_get_contents($mappingFile), true);
            if (json_last_error() === JSON_ERROR_NONE) {
                unset($mapping['mpd']);
            } else {
                $mapping = [];
            }
            file_put_contents($mappingFile, json_encode($mapping, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
        }
    }

    public function run(IOutput $output)
    {
        $output->info('Unregistering the mimetype...');

        // Register the mime type for existing files
        $this->unregisterForExistingFiles();

        // Register the mime type for new files
        $this->unregisterForNewFiles();

        $output->info('The mimetype was successfully unregistered.');
    }
}
