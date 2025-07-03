<?php

declare(strict_types=1);

namespace Codewithkyrian\Transformers\Utils;

use Codewithkyrian\Transformers\Tensor\Tensor;
use Codewithkyrian\Transformers\Transformers;
use Exception;
use Imagick;
use Imagine\Image\AbstractImagine;
use Imagine\Image\Box;
use Imagine\Image\ImageInterface;
use Imagine\Image\Metadata\MetadataBag;
use Imagine\Image\Point;
use Imagine\Vips\Imagine;
use InvalidArgumentException;
use RuntimeException;

use function array_flip;
use function floor;
use function imagecolorallocate;
use function imagecolorallocatealpha;
use function imagecolorat;
use function imagesetpixel;
use function json_encode;
use function max;
use function min;
use function pack;
use function putenv;
use function sprintf;
use function str_repeat;

/**
 * Helper file for Image Processing.
 *
 * This class is only used internally,  meaning an end-user shouldn't need to access anything here.
 */
class Image
{
    public const CHR = [
        "\x00", "\x01", "\x02", "\x03", "\x04", "\x05", "\x06", "\x07", "\x08", "\x09", "\x0A", "\x0B", "\x0C", "\x0D", "\x0E", "\x0F",
        "\x10", "\x11", "\x12", "\x13", "\x14", "\x15", "\x16", "\x17", "\x18", "\x19", "\x1A", "\x1B", "\x1C", "\x1D", "\x1E", "\x1F",
        "\x20", "\x21", "\x22", "\x23", "\x24", "\x25", "\x26", "\x27", "\x28", "\x29", "\x2A", "\x2B", "\x2C", "\x2D", "\x2E", "\x2F",
        "\x30", "\x31", "\x32", "\x33", "\x34", "\x35", "\x36", "\x37", "\x38", "\x39", "\x3A", "\x3B", "\x3C", "\x3D", "\x3E", "\x3F",
        "\x40", "\x41", "\x42", "\x43", "\x44", "\x45", "\x46", "\x47", "\x48", "\x49", "\x4A", "\x4B", "\x4C", "\x4D", "\x4E", "\x4F",
        "\x50", "\x51", "\x52", "\x53", "\x54", "\x55", "\x56", "\x57", "\x58", "\x59", "\x5A", "\x5B", "\x5C", "\x5D", "\x5E", "\x5F",
        "\x60", "\x61", "\x62", "\x63", "\x64", "\x65", "\x66", "\x67", "\x68", "\x69", "\x6A", "\x6B", "\x6C", "\x6D", "\x6E", "\x6F",
        "\x70", "\x71", "\x72", "\x73", "\x74", "\x75", "\x76", "\x77", "\x78", "\x79", "\x7A", "\x7B", "\x7C", "\x7D", "\x7E", "\x7F",
        "\x80", "\x81", "\x82", "\x83", "\x84", "\x85", "\x86", "\x87", "\x88", "\x89", "\x8A", "\x8B", "\x8C", "\x8D", "\x8E", "\x8F",
        "\x90", "\x91", "\x92", "\x93", "\x94", "\x95", "\x96", "\x97", "\x98", "\x99", "\x9A", "\x9B", "\x9C", "\x9D", "\x9E", "\x9F",
        "\xA0", "\xA1", "\xA2", "\xA3", "\xA4", "\xA5", "\xA6", "\xA7", "\xA8", "\xA9", "\xAA", "\xAB", "\xAC", "\xAD", "\xAE", "\xAF",
        "\xB0", "\xB1", "\xB2", "\xB3", "\xB4", "\xB5", "\xB6", "\xB7", "\xB8", "\xB9", "\xBA", "\xBB", "\xBC", "\xBD", "\xBE", "\xBF",
        "\xC0", "\xC1", "\xC2", "\xC3", "\xC4", "\xC5", "\xC6", "\xC7", "\xC8", "\xC9", "\xCA", "\xCB", "\xCC", "\xCD", "\xCE", "\xCF",
        "\xD0", "\xD1", "\xD2", "\xD3", "\xD4", "\xD5", "\xD6", "\xD7", "\xD8", "\xD9", "\xDA", "\xDB", "\xDC", "\xDD", "\xDE", "\xDF",
        "\xE0", "\xE1", "\xE2", "\xE3", "\xE4", "\xE5", "\xE6", "\xE7", "\xE8", "\xE9", "\xEA", "\xEB", "\xEC", "\xED", "\xEE", "\xEF",
        "\xF0", "\xF1", "\xF2", "\xF3", "\xF4", "\xF5", "\xF6", "\xF7", "\xF8", "\xF9", "\xFA", "\xFB", "\xFC", "\xFD", "\xFE", "\xFF",
    ]; // Lookup for chr($x): much faster.

    public const ALPHA_LOOKUP = [
        0x00000000 => "\xff", 0x01000000 => "\xfd", 0x02000000 => "\xfb", 0x03000000 => "\xf9",
        0x04000000 => "\xf7", 0x05000000 => "\xf5", 0x06000000 => "\xf3", 0x07000000 => "\xf1",
        0x08000000 => "\xef", 0x09000000 => "\xed", 0x0A000000 => "\xeb", 0x0B000000 => "\xe9",
        0x0C000000 => "\xe7", 0x0D000000 => "\xe5", 0x0E000000 => "\xe3", 0x0F000000 => "\xe1",
        0x10000000 => "\xdf", 0x11000000 => "\xdd", 0x12000000 => "\xdb", 0x13000000 => "\xd9",
        0x14000000 => "\xd7", 0x15000000 => "\xd5", 0x16000000 => "\xd3", 0x17000000 => "\xd1",
        0x18000000 => "\xcf", 0x19000000 => "\xcd", 0x1A000000 => "\xcb", 0x1B000000 => "\xc9",
        0x1C000000 => "\xc7", 0x1D000000 => "\xc5", 0x1E000000 => "\xc3", 0x1F000000 => "\xc1",
        0x20000000 => "\xbf", 0x21000000 => "\xbd", 0x22000000 => "\xbb", 0x23000000 => "\xb9",
        0x24000000 => "\xb7", 0x25000000 => "\xb5", 0x26000000 => "\xb3", 0x27000000 => "\xb1",
        0x28000000 => "\xaf", 0x29000000 => "\xad", 0x2A000000 => "\xab", 0x2B000000 => "\xa9",
        0x2C000000 => "\xa7", 0x2D000000 => "\xa5", 0x2E000000 => "\xa3", 0x2F000000 => "\xa1",
        0x30000000 => "\x9f", 0x31000000 => "\x9d", 0x32000000 => "\x9b", 0x33000000 => "\x99",
        0x34000000 => "\x97", 0x35000000 => "\x95", 0x36000000 => "\x93", 0x37000000 => "\x91",
        0x38000000 => "\x8f", 0x39000000 => "\x8d", 0x3A000000 => "\x8b", 0x3B000000 => "\x89",
        0x3C000000 => "\x87", 0x3D000000 => "\x85", 0x3E000000 => "\x83", 0x3F000000 => "\x81",
        0x40000000 => "\x7f", 0x41000000 => "\x7d", 0x42000000 => "\x7b", 0x43000000 => "\x79",
        0x44000000 => "\x77", 0x45000000 => "\x75", 0x46000000 => "\x73", 0x47000000 => "\x71",
        0x48000000 => "\x6f", 0x49000000 => "\x6d", 0x4A000000 => "\x6b", 0x4B000000 => "\x69",
        0x4C000000 => "\x67", 0x4D000000 => "\x65", 0x4E000000 => "\x63", 0x4F000000 => "\x61",
        0x50000000 => "\x5f", 0x51000000 => "\x5d", 0x52000000 => "\x5b", 0x53000000 => "\x59",
        0x54000000 => "\x57", 0x55000000 => "\x55", 0x56000000 => "\x53", 0x57000000 => "\x51",
        0x58000000 => "\x4f", 0x59000000 => "\x4d", 0x5A000000 => "\x4b", 0x5B000000 => "\x49",
        0x5C000000 => "\x47", 0x5D000000 => "\x45", 0x5E000000 => "\x43", 0x5F000000 => "\x41",
        0x60000000 => "\x3f", 0x61000000 => "\x3d", 0x62000000 => "\x3b", 0x63000000 => "\x39",
        0x64000000 => "\x37", 0x65000000 => "\x35", 0x66000000 => "\x33", 0x67000000 => "\x31",
        0x68000000 => "\x2f", 0x69000000 => "\x2d", 0x6A000000 => "\x2b", 0x6B000000 => "\x29",
        0x6C000000 => "\x27", 0x6D000000 => "\x25", 0x6E000000 => "\x23", 0x6F000000 => "\x21",
        0x70000000 => "\x1f", 0x71000000 => "\x1d", 0x72000000 => "\x1b", 0x73000000 => "\x19",
        0x74000000 => "\x17", 0x75000000 => "\x15", 0x76000000 => "\x13", 0x77000000 => "\x11",
        0x78000000 => "\x0f", 0x79000000 => "\x0d", 0x7A000000 => "\x0b", 0x7B000000 => "\x09",
        0x7C000000 => "\x07", 0x7D000000 => "\x05", 0x7E000000 => "\x03", 0x7F000000 => "\x00",
    ]; // Lookup table for chr(255-(($x >> 23) & 0x7f)).
    public static AbstractImagine $imagine;

    public function __construct(public ImageInterface $image, public int $channels = 4)
    {
        if ($this->image instanceof \Imagine\Vips\Image) {
            $this->channels = $this->image->getVips()->bands;
        }
    }

    public static function setDriver(ImageDriver $imageDriver): void
    {
        self::$imagine = match ($imageDriver) {
            ImageDriver::IMAGICK => new \Imagine\Imagick\Imagine(),
            ImageDriver::GD => new \Imagine\Gd\Imagine(),
            ImageDriver::VIPS => new Imagine(),
        };

        if (ImageDriver::VIPS === $imageDriver) {
            $libsDir = basePath('libs');
            putenv("VIPSHOME={$libsDir}");
        }
    }

    public static function getImagine(): AbstractImagine
    {
        if (!isset(self::$imagine)) {
            self::setDriver(Transformers::getImageDriver());
        }

        return self::$imagine;
    }

    public static function read(string $input, array $options = []): static
    {
        $image = self::getImagine()->open($input, $options);

        return new self($image);
    }

    public function height(): int
    {
        return $this->image->getSize()->getHeight();
    }

    public function width(): int
    {
        return $this->image->getSize()->getWidth();
    }

    /**
     * Returns the size of the image (width, height).
     *
     * @return array{int, int}
     */
    public function size(): array
    {
        $size = $this->image->getSize();

        return [$size->getWidth(), $size->getHeight()];
    }

    public function metadata(): MetadataBag
    {
        return $this->image->metadata();
    }

    /**
     * Clone the image.
     *
     * @return Image the cloned image
     */
    public function clone(): static
    {
        return new self($this->image->copy(), $this->channels);
    }

    /**
     * Resize the image to the given dimensions.
     */
    public function resize(int $width, int $height, int|Resample $resample = 2): static
    {
        $resampleMethod = $resample instanceof Resample ? $resample : Resample::from($resample) ?? Resample::NEAREST;

        $image = $this->image->copy()->resize(new Box($width, $height), $resampleMethod->toString());

        return new self($image, $this->channels);
    }

    /**
     * Resize the image to make a thumbnail.
     */
    public function thumbnail(int $width, int $height, int|Resample $resample = 2): static
    {
        $inputHeight = $this->height();
        $inputWidth = $this->width();

        // We always resize to the smallest of either the input or output size.
        $height = min($inputHeight, $height);
        $width = min($inputWidth, $width);

        if ($height === $inputHeight && $width === $inputWidth) {
            return $this->clone();
        }

        if ($inputHeight > $inputWidth) {
            $width = (int) floor($inputWidth * $height / $inputHeight);
        } elseif ($inputWidth > $inputHeight) {
            $height = (int) floor($inputHeight * $width / $inputWidth);
        }

        return $this->resize($width, $height, $resample);
    }

    /**
     * Convert the image to grayscale format.
     *
     * @return $this
     */
    public function grayscale(bool $force = false): static
    {
        if (1 === $this->channels && !$force) {
            return $this->clone();
        }

        $image = $this->image->copy();
        $image->effects()->grayscale();

        return new self($image, 1);
    }

    /**
     * Convert the image to RGB format.
     *
     * @return $this
     */
    public function rgb(bool $force = false): static
    {
        // If the image is already in RGB format, return it as is
        if (3 === $this->channels && !$force) {
            return $this->clone();
        }

        // If it's a Vips image, we can extract the RGB channels
        if ($this->image instanceof \Imagine\Vips\Image) {
            /** @var \Imagine\Vips\Image $image */
            $image = $this->image->copy();
            $vipImage = $image->getVips()->extract_band(0, ['n' => 3]);
            $image->setVips($vipImage);

            return new self($image, 3);
        }

        return new self($this->image->copy(), 3);
    }

    /**
     * Convert the image to RGBA format.
     *
     * @return $this
     */
    public function rgba(bool $force = false): static
    {
        // If the image is already in RGBA format, return it as is
        if (4 === $this->channels && !$force) {
            return $this->clone();
        }

        // If it's a Vips image, we can handle the RGBA channels
        if ($this->image instanceof \Imagine\Vips\Image) {
            $vipImage = $this->image->copy()->getVips();

            $vipImage = $vipImage->hasAlpha() ? $vipImage->extract_band(0, ['n' => 4]) : $vipImage->bandjoin([255]);

            return new self($vipImage, 4);
        }

        return new self($this->image->copy(), 4);
    }

    public function centerCrop(int $cropWidth, int $cropHeight): static
    {
        $originalWidth = $this->image->getSize()->getWidth();
        $originalHeight = $this->image->getSize()->getHeight();

        if ($originalWidth === $cropWidth && $originalHeight === $cropHeight) {
            return $this->clone();
        }

        // Calculate the coordinates for center cropping
        $xOffset = max(0, ($originalWidth - $cropWidth) / 2);
        $yOffset = max(0, ($originalHeight - $cropHeight) / 2);

        $croppedImage = $this->image->copy()->crop(new Point($xOffset, $yOffset), new Box($cropWidth, $cropHeight));

        return new self($croppedImage, $this->channels);
    }

    public function crop(int $xMin, int $yMin, int $xMax, int $yMax): static
    {
        $originalWidth = $this->image->getSize()->getWidth();
        $originalHeight = $this->image->getSize()->getHeight();

        if (0 === $xMin && 0 === $yMin && $xMax === $originalWidth && $yMax === $originalHeight) {
            return $this->clone();
        }
        // Ensure crop bounds are within the image
        $xMin = max($xMin, 0);
        $yMin = max($yMin, 0);
        $xMax = min($xMax, $originalWidth - 1);
        $yMax = min($yMax, $originalHeight - 1);

        $cropWidth = $xMax - $xMin + 1;
        $cropHeight = $yMax - $yMin + 1;

        $croppedImage = $this->image->copy()->crop(new Point($xMin, $yMin), new Box($cropWidth, $cropHeight));

        return new self($croppedImage, $this->channels);
    }

    /**
     * Crops the margin of the image. Gray pixels are considered margin (i.e., pixels with a value below the threshold).
     *
     * @param int $grayThreshold value below which pixels are considered to be gray
     *
     * @return static the cropped image
     *
     * @throws Exception
     */
    public function cropMargin(int $grayThreshold = 200): static
    {
        $grayImage = $this->grayscale();

        // Get the min and max pixel values
        $minValue = min($grayImage->toTensor()->buffer())[0];
        $maxValue = max($grayImage->toTensor()->buffer())[0];

        $diff = $maxValue - $minValue;

        // If all pixels have the same value, no need to crop
        if (0 === $diff) {
            return $this->clone();
        }

        $threshold = $grayThreshold / 255;

        [$xMin, $yMin] = $this->size();
        $xMax = 0;
        $yMax = 0;

        [$width, $height] = $this->size();

        // Iterate over each pixel in the image
        for ($y = 0; $y < $height; ++$y) {
            for ($x = 0; $x < $width; ++$x) {
                $color = $grayImage->image->getColorAt(new Point($x, $y));
                $pixelValue = $color->getRed(); // Assuming grayscale, so red channel is sufficient

                if (($pixelValue - $minValue) / $diff < $threshold) {
                    // We have a non-gray pixel, so update the min/max values accordingly
                    $xMin = min($xMin, $x);
                    $yMin = min($yMin, $y);
                    $xMax = max($xMax, $x);
                    $yMax = max($yMax, $y);
                }
            }
        }

        // Crop the image using the calculated bounds
        return $this->crop($xMin, $yMin, $xMax, $yMax);
    }

    public function pad(int $left, int $right, int $top, int $bottom): static
    {
        if (0 === $left && 0 === $right && 0 === $top && 0 === $bottom) {
            return $this;
        }

        [$originalWidth, $originalHeight] = $this->size();

        $newWidth = $originalWidth + $left + $right;
        $newHeight = $originalHeight + $top + $bottom;

        $paddedImage = self::getImagine()->create(new Box($newWidth, $newHeight));

        $paddedImage->paste($this->image, new Point($left, $top));

        return new self($paddedImage, $this->channels);
    }

    public function invert(): static
    {
        $image = $this->image->copy();
        $image->effects()->negative();

        return new self($image, $this->channels);
    }

    /**
     * Applies a mask to the current image.
     *
     * @param Image $mask the mask to apply
     *
     * @return static a new instance of the current image with the mask applied
     *
     * @throws InvalidArgumentException if the given mask doesn't match the current image's size or if the image driver is unsupported
     * @throws RuntimeException if the apply mask operation fails
     */
    public function applyMask(Image $mask): static
    {
        $size = $this->size();
        $maskSize = $mask->size();

        if ($size != $maskSize) {
            throw new InvalidArgumentException(
                sprintf(
                    "The given mask doesn't match current image's size, current mask's dimensions are %s, while image's dimensions are %s",
                    json_encode($maskSize),
                    json_encode($size),
                ),
            );
        }

        $image = match (true) {
            $this->image instanceof \Imagine\Vips\Image => $this->image->copy()->applyMask($mask->image),

            $this->image instanceof \Imagine\Imagick\Image => (function () use ($mask) {
                //                $maskImagick = $mask->image->copy()->mask()->getImagick();
                //                $imageImagick = clone $this->image->getImagick();
                //
                //                $maskImagick->compositeImage($imageImagick, Imagick::COMPOSITE_DSTIN, 0, 0);
                //                $imageImagick->compositeImage($maskImagick, Imagick::COMPOSITE_COPYOPACITY, 0, 0);
                //
                //                $maskImagick->clear();
                //                $maskImagick->destroy();
                //
                //                return new \Imagine\Imagick\Image($imageImagick, $this->image->palette(), $this->image->metadata());
                $image = $this->image->copy();
                $maskImage = $mask->image->copy();
                $maskImage->effects()->negative();
                $image->applyMask($maskImage);

                return $image;
            })(),

            $this->image instanceof \Imagine\Gd\Image => (function () use ($mask) {
                $gdResource = $this->image->getGdResource();

                for ($x = 0, $width = $this->width(); $x < $width; ++$x) {
                    for ($y = 0, $height = $this->height(); $y < $height; ++$y) {
                        $position = new Point($x, $y);
                        $color = $this->image->getColorAt($position);
                        $maskColor = $mask->image->getColorAt($position);
                        $newAlpha = 127 - (int) floor($maskColor->getRed() / 2);

                        $newColor = imagecolorallocatealpha(
                            $this->image->getGdResource(),
                            $color->getRed(),
                            $color->getGreen(),
                            $color->getBlue(),
                            $newAlpha,
                        );

                        if (false === imagesetpixel($gdResource, $x, $y, $newColor)) {
                            throw new RuntimeException('Apply mask operation failed');
                        }
                    }
                }

                return new \Imagine\Gd\Image($gdResource, $this->image->palette(), $this->image->metadata());
            })(),
            default => throw new InvalidArgumentException('Unsupported image driver'),
        };

        return new self($image, $this->channels);
    }

    /**
     * Creates an image from a tensor.
     *
     * @param Tensor $tensor the tensor containing the image data
     * @param string $channelFormat The format of the tensor channels. Defaults to 'CHW'.
     *
     * @return static the created image
     *
     * @throws Exception if the number of channels in the tensor is unsupported
     */
    public static function fromTensor(Tensor $tensor, string $channelFormat = 'CHW'): static
    {
        $tensor = 'CHW' === $channelFormat ? $tensor->permute(1, 2, 0) : $tensor;

        [$height, $width, $channels] = $tensor->shape();

        $image = self::getImagine()->create(new Box($width, $height));

        // Make sure the tensor is the right data type
        $tensor = $tensor->to(Tensor::uint8);

        // Handle Vips images
        if ($image instanceof \Imagine\Vips\Image) {
            $vipImage = $image->getVips()::newFromMemory($tensor->buffer()->dump(), $width, $height, $channels, 'uchar');

            $image->setVips($vipImage, true);

            return new self($image, $channels);
        }

        // Handle Imagick images
        if ($image instanceof \Imagine\Imagick\Image) {
            $map = match ($channels) {
                1 => 'I',
                2 => 'RG',
                3 => 'RGB',
                4 => 'RGBA',
                default => throw new Exception("Unsupported number of channels: {$channels}"),
            };

            $bufferArray = $tensor->toBufferArray();

            $image->getImagick()->importImagePixels(0, 0, $width, $height, $map, Imagick::PIXEL_CHAR, $bufferArray);

            return new self($image, $channels);
        }

        /**
         * Handle GD images.
         */
        $gdImage = $image->getGdResource();
        $imageData = $tensor->buffer()->dump();

        $reverseCHR = array_flip(self::CHR);
        $reverseAlphaLookup = array_flip(self::ALPHA_LOOKUP);

        $j = 0;
        // Iterate through each pixel's worth of string data
        for ($y = 0; $y < $height; ++$y) {
            for ($x = 0; $x < $width; ++$x) {
                if (1 === $channels) {
                    // Grayscale: get the single channel value and set it to R, G, B equally
                    $gray = $reverseCHR[$imageData[$j++]];
                    $argb = imagecolorallocate($gdImage, $gray, $gray, $gray);
                } else {
                    $argb = 0;
                    if ($channels >= 1) {
                        $argb |= ($reverseCHR[$imageData[$j++]] << 16); // R
                    }
                    if ($channels >= 2) {
                        $argb |= ($reverseCHR[$imageData[$j++]] << 8); // G
                    }
                    if ($channels >= 3) {
                        $argb |= $reverseCHR[$imageData[$j++]]; // B
                    }
                    if (4 === $channels) {
                        // Ensure alpha value is handled
                        $alpha = $reverseAlphaLookup[$imageData[$j++]];
                        $argb |= $alpha << 24;
                    } else {
                        $argb |= 0xFF000000; // Set full opacity for RGB images
                    }
                }

                // Set the pixel at (x, y) to the ARGB value
                imagesetpixel($gdImage, $x, $y, $argb);
            }
        }

        return new self($image, $channels);
    }

    /**
     * Converts the image to a tensor.
     *
     * @param string $channelFormat The channel format of the tensor. Defaults to 'CHW'.
     *
     * @return Tensor the tensor representation of the image
     *
     * @throws Exception if the channel format is unsupported
     */
    public function toTensor(string $channelFormat = 'CHW'): Tensor
    {
        $width = $this->image->getSize()->getWidth();
        $height = $this->image->getSize()->getHeight();

        $pixelData = $this->getPixelData();

        $tensor = Tensor::fromString($pixelData, Tensor::uint8, [$height, $width, $this->channels])
            ->to(Tensor::float32);

        if ('HWC' === $channelFormat) {
            // Do nothing
        } elseif ('CHW' === $channelFormat) { // hwc -> chw
            $tensor = $tensor->permute(2, 0, 1);
        } else {
            throw new Exception("Unsupported channel format: {$channelFormat}");
        }

        return $tensor;
    }

    public function getPixelData(): string
    {
        $width = $this->width();
        $height = $this->height();

        // For Vips images, we can export the pixel data directly
        if ($this->image instanceof \Imagine\Vips\Image) {
            return $this->image->getVips()->writeToMemory();
        }

        // For Imagick images, we can export the pixel data directly
        if ($this->image instanceof \Imagine\Imagick\Image) {
            $map = match ($this->channels) {
                1 => 'I',
                2 => 'RG',
                3 => 'RGB',
                4 => 'RGBA',
                default => throw new Exception("Unsupported number of channels: {$this->channels}"),
            };

            $pixels = $this->image->getImagick()->exportImagePixels(0, 0, $width, $height, $map, Imagick::PIXEL_CHAR);

            return pack('C*', ...$pixels);
        }

        /** For GD images, we need to extract the pixel data manually.
         *
         * I didn't find an in-built method to extract pixel data from a GD image, so I'm using this ugly
         * brute-force method, suggested by @DewiMorgan on StackOverflow: https://stackoverflow.com/a/30136602/11209184.
         * It's tons faster than other methods I tried, and rivals the speed of the Imagick method, so I'll keep it for now.
         */
        $imageData = str_repeat("\x00", $width * $height * $this->channels);
        $gdImage = $this->image->getGdResource();

        // Loop over each single pixel.
        $j = 0;
        for ($y = 0; $y < $height; ++$y) {
            for ($x = 0; $x < $width; ++$x) {
                // Grab the pixel data.
                $argb = imagecolorat($gdImage, $x, $y);

                if ($this->channels >= 1) {
                    $imageData[$j++] = self::CHR[($argb >> 16) & 0xFF]; // R
                }
                if ($this->channels >= 2) {
                    $imageData[$j++] = self::CHR[($argb >> 8) & 0xFF]; // G
                }
                if ($this->channels >= 3) {
                    $imageData[$j++] = self::CHR[$argb & 0xFF]; // B
                }
                if ($this->channels >= 4) {
                    $imageData[$j++] = self::ALPHA_LOOKUP[$argb & 0x7F000000]; // A
                }
            }
        }

        return $imageData;
    }

    public function save(string $path): void
    {
        $this->image->save($path);
    }

    /**
     * Draws a rectangle on the image at the specified position with the given color and thickness.
     *
     * @param float|int $xMin the x-coordinate of the top-left corner of the rectangle
     * @param int $yMin the y-coordinate of the top-left corner of the rectangle
     * @param float|int $xMax the x-coordinate of the bottom-right corner of the rectangle
     * @param float|int $yMax the y-coordinate of the bottom-right corner of the rectangle
     * @param string $color The color of the rectangle in hexadecimal format. Default is 'FFF'.
     * @param bool $fill Whether to fill the rectangle with the color. Default is false.
     * @param float $thickness The thickness of the rectangle border. Default is 1.
     *
     * @return self a new instance of the Image class with the rectangle drawn
     */
    public function drawRectangle(float|int $xMin, float|int $yMin, float|int $xMax, float|int $yMax, string $color = 'FFF', $fill = false, float $thickness = 1): self
    {
        $image = $this->image->copy();

        $image->draw()->rectangle(
            new Point($xMin, $yMin),
            new Point($xMax, $yMax),
            $this->image->palette()->color($color),
            $fill,
            $thickness,
        );

        return new self($image, $this->channels);
    }

    /**
     * Draws text on an image at the specified position using the given font and color.
     *
     * @param string $text the text to be drawn
     * @param float|int $xPos the x-coordinate of the text position
     * @param float|int $yPos the y-coordinate of the text position
     * @param string $fontFile the path to the font file
     * @param int $fontSize The size of the font in points. Default is 16.
     * @param string $color The color of the text in hexadecimal format. Default is 'FFF'.
     *
     * @return self a new instance of Image with the drawn text
     */
    public function drawText(string $text, float|int $xPos, float|int $yPos, string $fontFile, int $fontSize = 16, string $color = 'FFF'): self
    {
        $font = self::getImagine()->font($fontFile, $fontSize, $this->image->palette()->color($color));

        $position = new Point($xPos, $yPos);

        $image = $this->image->copy();

        $image->draw()->text($text, $font, $position);

        return new self($image, $this->channels);
    }
}
