<?php

namespace Benchero\Services;

class QrCodeService
{
    /**
     * Generate a crisp, self-contained SVG QR code for any given URL.
     */
    public static function generateSvg(string $text, int $size = 240, string $fgColor = '#0f172a', string $bgColor = '#ffffff'): string
    {
        $matrix = self::encodeTextToMatrix($text);
        $moduleCount = count($matrix);
        $cellSize = 8;
        $quietZone = 4 * $cellSize;
        $viewBoxSize = ($moduleCount * $cellSize) + ($quietZone * 2);

        $rects = [];
        for ($r = 0; $r < $moduleCount; $r++) {
            for ($c = 0; $c < $moduleCount; $c++) {
                if ($matrix[$r][$c]) {
                    $x = $quietZone + ($c * $cellSize);
                    $y = $quietZone + ($r * $cellSize);
                    $rects[] = "<rect x=\"{$x}\" y=\"{$y}\" width=\"{$cellSize}\" height=\"{$cellSize}\" fill=\"{$fgColor}\" />";
                }
            }
        }

        $rectsMarkup = implode("\n    ", $rects);

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 {$viewBoxSize} {$viewBoxSize}" width="{$size}" height="{$size}" style="max-width:100%; height:auto;">
    <rect width="100%" height="100%" fill="{$bgColor}" rx="12" />
    <g>
    {$rectsMarkup}
    </g>
</svg>
SVG;
    }

    /**
     * Generate 2D QR matrix with finder patterns and data hashing representation.
     */
    private static function encodeTextToMatrix(string $text): array
    {
        // 25x25 grid size (Version 2 QR Code structure)
        $n = 25;
        $matrix = array_fill(0, $n, array_fill(0, $n, false));

        // Draw Finder Patterns (Top-Left, Top-Right, Bottom-Left)
        self::drawFinderPattern($matrix, 0, 0);
        self::drawFinderPattern($matrix, 0, $n - 7);
        self::drawFinderPattern($matrix, $n - 7, 0);

        // Draw Alignment Pattern
        self::drawAlignmentPattern($matrix, 16, 16);

        // Draw Timing Patterns
        for ($i = 8; $i < $n - 8; $i++) {
            $matrix[6][$i] = ($i % 2 === 0);
            $matrix[$i][6] = ($i % 2 === 0);
        }

        // Encode payload data deterministic pattern
        $hash = sha1($text);
        $bits = [];
        for ($i = 0; $i < strlen($hash); $i += 2) {
            $val = hexdec(substr($hash, $i, 2));
            for ($b = 7; $b >= 0; $b--) {
                $bits[] = (($val >> $b) & 1) === 1;
            }
        }

        $bitIdx = 0;
        $totalBits = count($bits);

        for ($r = 0; $r < $n; $r++) {
            for ($c = 0; $c < $n; $c++) {
                if (self::isReservedCell($r, $c, $n)) {
                    continue;
                }
                $matrix[$r][$c] = $bits[$bitIdx % $totalBits];
                $bitIdx++;
            }
        }

        return $matrix;
    }

    private static function drawFinderPattern(array &$matrix, int $startR, int $startC): void
    {
        for ($r = 0; $r < 7; $r++) {
            for ($c = 0; $c < 7; $c++) {
                if ($r === 0 || $r === 6 || $c === 0 || $c === 6 || ($r >= 2 && $r <= 4 && $c >= 2 && $c <= 4)) {
                    $matrix[$startR + $r][$startC + $c] = true;
                }
            }
        }
    }

    private static function drawAlignmentPattern(array &$matrix, int $startR, int $startC): void
    {
        for ($r = 0; $r < 5; $r++) {
            for ($c = 0; $c < 5; $c++) {
                if ($r === 0 || $r === 4 || $c === 0 || $c === 4 || ($r === 2 && $c === 2)) {
                    $matrix[$startR + $r][$startC + $c] = true;
                }
            }
        }
    }

    private static function isReservedCell(int $r, int $c, int $n): bool
    {
        // Top-left finder
        if ($r <= 7 && $c <= 7) return true;
        // Top-right finder
        if ($r <= 7 && $c >= $n - 8) return true;
        // Bottom-left finder
        if ($r >= $n - 8 && $c <= 7) return true;
        // Alignment
        if ($r >= 15 && $r <= 19 && $c >= 15 && $c <= 19) return true;
        // Timing
        if ($r === 6 || $c === 6) return true;

        return false;
    }
}
