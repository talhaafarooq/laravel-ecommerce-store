<?php

declare(strict_types = 1);

namespace App\Services;

use App\Support\Media;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\Exceptions\ImageException;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Interfaces\ImageManagerInterface;

/**
 * Stores uploaded images as optimised WebP files on the media disk.
 *
 * Every upload is decoded and re-encoded, which strips EXIF metadata and
 * anything that is not image data, and is saved under a random file name.
 */
final readonly class ImageService
{
    public function __construct(private ImageManagerInterface $images) {}

    /**
     * Store an image and a square-bounded thumbnail.
     *
     * @return array{path: string, thumbnail_path: string}
     */
    public function storeWithThumbnail(UploadedFile|string $source, string $directory): array
    {
        $name      = (string) Str::ulid();
        $maxSize   = (int) config('shop.images.max_dimension', 1600);
        $thumbSize = (int) config('shop.images.thumbnail_size', 480);

        $path          = "{$directory}/{$name}.webp";
        $thumbnailPath = "{$directory}/thumbnails/{$name}.webp";

        $this->write($path, $this->decode($source)->scaleDown($maxSize, $maxSize));
        $this->write($thumbnailPath, $this->decode($source)->scaleDown($thumbSize, $thumbSize));

        return ['path' => $path, 'thumbnail_path' => $thumbnailPath];
    }

    /**
     * Store a single image bounded by the given dimensions.
     */
    public function store(UploadedFile|string $source, string $directory, int $maxWidth = 1600, ?int $maxHeight = null): string
    {
        $path = "{$directory}/" . Str::ulid() . '.webp';

        $this->write($path, $this->decode($source)->scaleDown($maxWidth, $maxHeight ?? $maxWidth));

        return $path;
    }

    /**
     * Store an image cropped to cover the exact dimensions (e.g. avatars).
     */
    public function storeCovered(UploadedFile|string $source, string $directory, int $width, int $height): string
    {
        $path = "{$directory}/" . Str::ulid() . '.webp';

        $this->write($path, $this->decode($source)->cover($width, $height));

        return $path;
    }

    /**
     * Delete stored files, ignoring empty and external paths.
     */
    public function delete(?string ...$paths): void
    {
        $local = array_values(array_filter(
            $paths,
            static fn (?string $path): bool => $path !== null && $path !== '' && !Str::startsWith($path, ['http://', 'https://']),
        ));

        if ($local !== []) {
            Media::disk()->delete($local);
        }
    }

    /**
     * @param UploadedFile|string $source An uploaded file or raw binary image data.
     */
    private function decode(UploadedFile|string $source): ImageInterface
    {
        try {
            $image = $source instanceof UploadedFile
                ? $this->images->decodePath((string) $source->getRealPath())
                : $this->images->decodeBinary($source);
        } catch (ImageException) {
            throw ValidationException::withMessages([
                'images' => 'One of the images could not be processed. Please upload a valid JPG, PNG or WebP file.',
            ]);
        }

        return $image->orient();
    }

    private function write(string $path, ImageInterface $image): void
    {
        $encoded = $image->encode(new WebpEncoder(
            quality: (int) config('shop.images.quality', 82),
            strip: true,
        ));

        Media::disk()->put($path, $encoded->toString(), ['visibility' => 'public']);
    }
}
