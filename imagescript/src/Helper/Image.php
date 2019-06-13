<?php

namespace Adempier\ImageScript\Helper;

class Image
{

    public function copyInAnotherFolder($imageName)
    {
        if (file_exists(BACKUP_DIR . DIRECTORY_SEPARATOR . $imageName)) {
            $this->storeImage($imageName);
        }
        $this->backupImage($imageName);
    }

    public function storeImage($imageName)
    {
        $exImage = explode('.', $imageName);
        $sku = $exImage[0];
        $exImage = $exImage[1];
        rename(BACKUP_DIR.DIRECTORY_SEPARATOR . $imageName, BACKUP_DIR.DIRECTORY_SEPARATOR . $sku . '-' . date('d-m-Y') . '.' . $exImage);
    }

    public function backupImage($imageName)
    {
        $sourceFile = UPLOAD_DIR . DIRECTORY_SEPARATOR . $imageName;
        $targetFile = BACKUP_DIR . DIRECTORY_SEPARATOR . $imageName;
        if (copy($sourceFile, $sourceFile)) {
            unlink($sourceFile);
        }
    }

}
