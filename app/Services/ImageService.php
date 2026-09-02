<?php

declare(strict_types=1);

namespace App\Services;

final class ImageService
{
    public function heroBackground(array $file, int $tenantId): ?string
    {
        return $this->resize($file, $tenantId, 'branding', 2400, 1350, 82);
    }

    public function productThumbnail(array $file, int $tenantId): ?string
    {
        return $this->resize($file, $tenantId, 'products', 640, 420, 78);
    }

    private function resize(array $file, int $tenantId, string $folder, int $targetWidth, int $targetHeight, int $quality): ?string
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK || ($file['size'] ?? 0) > 5 * 1024 * 1024) {
            throw new \InvalidArgumentException('La imagen no se pudo subir o pesa más de 5 MB.');
        }

        $info = getimagesize($file['tmp_name']);
        if (!$info) {
            throw new \InvalidArgumentException('El archivo no es una imagen válida.');
        }

        $source = match ($info[2]) {
            IMAGETYPE_JPEG => imagecreatefromjpeg($file['tmp_name']),
            IMAGETYPE_PNG => imagecreatefrompng($file['tmp_name']),
            IMAGETYPE_WEBP => imagecreatefromwebp($file['tmp_name']),
            default => null,
        };

        if (!$source) {
            throw new \InvalidArgumentException('Solo se permiten imágenes JPG, PNG o WebP.');
        }

        $thumb = imagecreatetruecolor($targetWidth, $targetHeight);
        imagefill($thumb, 0, 0, imagecolorallocate($thumb, 255, 250, 244));

        $srcWidth = imagesx($source);
        $srcHeight = imagesy($source);
        $scale = max($targetWidth / $srcWidth, $targetHeight / $srcHeight);
        $newWidth = (int) ceil($srcWidth * $scale);
        $newHeight = (int) ceil($srcHeight * $scale);
        $dstX = (int) floor(($targetWidth - $newWidth) / 2);
        $dstY = (int) floor(($targetHeight - $newHeight) / 2);

        imagecopyresampled($thumb, $source, $dstX, $dstY, 0, 0, $newWidth, $newHeight, $srcWidth, $srcHeight);

        $dir = BASE_PATH . '/public/uploads/' . $folder;
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new \RuntimeException('No se pudo crear la carpeta de imágenes.');
        }

        $name = 'tenant-' . $tenantId . '-' . bin2hex(random_bytes(8)) . '.webp';
        $path = $dir . '/' . $name;

        if (!imagewebp($thumb, $path, $quality)) {
            throw new \RuntimeException('No se pudo generar el thumbnail.');
        }

        imagedestroy($source);
        imagedestroy($thumb);

        return '/uploads/' . $folder . '/' . $name;
    }
}
