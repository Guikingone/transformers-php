<?php

declare(strict_types=1);

namespace Codewithkyrian\Transformers\Utils;

use function count;
use function file_put_contents;
use function str_repeat;
use function str_split;
use function strlen;
use function ord;
use function unpack;

final class RawAudio
{
    /**
     * @param float[] $audio
     * @param int     $samplingRate
     */
    public function __construct(
        public readonly array $audio,
        public readonly int $samplingRate,
    ) {
    }

    public function toWav(): string
    {
        $writeString = function (&$view, $offset, $string): void {
            for ($i = 0; $i < strlen($string); $i++) {
                $view[$offset + $i] = ord($string[$i]);
            }
        };

        $offset = 44;
        $buffer = str_repeat("\0", $offset + count($this->audio) * 4);
        $view = str_split($buffer, 1);

        $writeString($view, 0, "RIFF");
        $chunkSize = 36 + (count($this->audio) * 4);
        $view[4] = $chunkSize & 0xFF;
        $view[5] = ($chunkSize >> 8) & 0xFF;
        $view[6] = ($chunkSize >> 16) & 0xFF;
        $view[7] = ($chunkSize >> 24) & 0xFF;
        $writeString($view, 8, "WAVE");
        $writeString($view, 12, "fmt ");
        $view[16] = 16; $view[17] = 0; $view[18] = 0; $view[19] = 0;
        $view[20] = 3; $view[21] = 0;
        $view[22] = 1; $view[23] = 0;
        $view[24] = $this->samplingRate & 0xFF;
        $view[25] = ($this->samplingRate >> 8) & 0xFF;
        $view[26] = ($this->samplingRate >> 16) & 0xFF;
        $view[27] = ($this->samplingRate >> 24) & 0xFF;
        $byteRate = $this->samplingRate * 4;
        $view[28] = $byteRate & 0xFF;
        $view[29] = ($byteRate >> 8) & 0xFF;
        $view[30] = ($byteRate >> 16) & 0xFF;
        $view[31] = ($byteRate >> 24) & 0xFF;
        $view[32] = 4; $view[33] = 0;
        $view[34] = 32; $view[35] = 0;
        $writeString($view, 36, "data");
        $dataSize = count($this->audio) * 4;
        $view[40] = $dataSize & 0xFF;
        $view[41] = ($dataSize >> 8) & 0xFF;
        $view[42] = ($dataSize >> 16) & 0xFF;
        $view[43] = ($dataSize >> 24) & 0xFF;

        for ($i = 0; $i < count($this->audio); $i++) {
            $float = $this->audio[$i];
            $bytes = unpack('C*', pack('f', $float));
            $view[$offset++] = $bytes[1];
            $view[$offset++] = $bytes[2];
            $view[$offset++] = $bytes[3];
            $view[$offset++] = $bytes[4];
        }

        $binary = '';
        foreach ($view as $byte) {
            $binary .= chr($byte);
        }

        return $binary;
    }

    public function save(string $path): void
    {
        file_put_contents($path, $this->toWav());
    }
}
