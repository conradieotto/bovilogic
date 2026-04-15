<?php
/**
 * BoviLogic Icon Generator
 * Generates all required PWA / favicon PNG files using GD.
 *
 * Usage: https://yourdomain.com/assets/icons/make-icons.php?token=bl-icons-2025
 *
 * Run once on the server — the generated PNG files persist permanently.
 */

// ── Auth guard ────────────────────────────────────────────────────────────────
if (($_GET['token'] ?? '') !== 'bl-icons-2025') {
    http_response_code(403);
    die('Forbidden. Supply ?token=bl-icons-2025');
}

// ── Helpers ───────────────────────────────────────────────────────────────────

/** Hex colour string → GD colour index. */
function alloc(GdImage $img, string $hex): int
{
    $hex = ltrim($hex, '#');
    return imagecolorallocate(
        $img,
        hexdec(substr($hex, 0, 2)),
        hexdec(substr($hex, 2, 2)),
        hexdec(substr($hex, 4, 2))
    );
}

/**
 * Draw a filled rounded rectangle.
 * GD has no native rounded-rect so we approximate with a filled rect +
 * four corner circles.
 */
function filledRoundedRect(GdImage $img, int $x1, int $y1, int $x2, int $y2, int $r, int $colour): void
{
    // Centre band (full width)
    imagefilledrectangle($img, $x1 + $r, $y1, $x2 - $r, $y2, $colour);
    // Left/right bands (full height, minus corners)
    imagefilledrectangle($img, $x1, $y1 + $r, $x1 + $r - 1, $y2 - $r, $colour);
    imagefilledrectangle($img, $x2 - $r + 1, $y1 + $r, $x2, $y2 - $r, $colour);
    // Four corner arcs
    imagefilledellipse($img, $x1 + $r, $y1 + $r, $r * 2, $r * 2, $colour);
    imagefilledellipse($img, $x2 - $r, $y1 + $r, $r * 2, $r * 2, $colour);
    imagefilledellipse($img, $x1 + $r, $y2 - $r, $r * 2, $r * 2, $colour);
    imagefilledellipse($img, $x2 - $r, $y2 - $r, $r * 2, $r * 2, $colour);
}

/**
 * Draw the BoviLogic icon onto $img.
 *
 * $size       – canvas dimension (square)
 * $maskable   – if true use a full circle background (maskable safe zone)
 * $bgColour   – already-allocated navy colour index
 * $white      – already-allocated white colour index
 * $yellow     – already-allocated yellow colour index
 */
function drawIcon(GdImage $img, int $size, bool $maskable, int $bgColour, int $white, int $yellow): void
{
    $cx = intval($size / 2);   // centre x
    $cy = intval($size / 2);   // centre y

    // ── Background ───────────────────────────────────────────────────────────
    if ($maskable) {
        // Full circle — fills the entire canvas for maskable icons
        imagefilledellipse($img, $cx, $cy, $size, $size, $bgColour);
    } else {
        // Rounded rectangle, corner radius ≈ 18% of size
        $r = max(2, intval($size * 0.18));
        filledRoundedRect($img, 0, 0, $size - 1, $size - 1, $r, $bgColour);
    }

    // ── Cow silhouette (white) ────────────────────────────────────────────────
    //
    // We draw a stylised cow head made from basic GD primitives:
    //   • large ellipse  → head
    //   • two ellipses   → snout / muzzle
    //   • two filled arcs → ears (one on each side, slightly above centre)
    //   • two small circles → nostrils
    //
    // All measurements are proportional to $size so it scales cleanly.

    // Safe drawing area accounts for maskable safe zone (≈10% padding each side)
    $pad       = $maskable ? intval($size * 0.12) : intval($size * 0.06);
    $drawW     = $size - 2 * $pad;   // drawable width
    $unit      = $drawW / 10.0;       // 1/10th of drawable area — base unit

    // Head ellipse — occupies roughly 60% of draw area, vertically centred
    // but shifted slightly upward to leave room for "BL" text at the bottom.
    $headRx    = intval($unit * 2.8);   // horizontal radius
    $headRy    = intval($unit * 2.4);   // vertical radius
    $headCx    = $cx;
    $headCy    = $cy - intval($unit * 0.6);

    imagefilledellipse($img, $headCx, $headCy, $headRx * 2, $headRy * 2, $white);

    // Ears — small filled ellipses sitting atop/beside the head
    $earRx   = intval($unit * 0.9);
    $earRy   = intval($unit * 1.3);
    $earOffX = intval($unit * 2.2);
    $earOffY = intval($unit * 1.5);
    // Left ear
    imagefilledellipse($img, $headCx - $earOffX, $headCy - $earOffY, $earRx * 2, $earRy * 2, $white);
    // Right ear
    imagefilledellipse($img, $headCx + $earOffX, $headCy - $earOffY, $earRx * 2, $earRy * 2, $white);

    // Snout / muzzle — wide shallow ellipse at the bottom of the head
    $snoutRx = intval($unit * 1.8);
    $snoutRy = intval($unit * 1.0);
    $snoutCy = $headCy + intval($unit * 1.5);
    imagefilledellipse($img, $headCx, $snoutCy, $snoutRx * 2, $snoutRy * 2, $white);

    // Nostrils — two small navy circles on the snout (only visible at ≥ 32 px)
    if ($size >= 32) {
        $nostrilR  = max(1, intval($unit * 0.35));
        $nostrilOff = intval($unit * 0.65);
        $bgNav = $bgColour; // reuse the navy we already have
        imagefilledellipse($img, $headCx - $nostrilOff, $snoutCy + intval($unit * 0.1),
                           $nostrilR * 2, $nostrilR * 2, $bgNav);
        imagefilledellipse($img, $headCx + $nostrilOff, $snoutCy + intval($unit * 0.1),
                           $nostrilR * 2, $nostrilR * 2, $bgNav);
    }

    // Inner ear accent — small navy ellipse inside each ear (≥ 32 px)
    if ($size >= 32) {
        $innerEarRx = max(1, intval($earRx * 0.55));
        $innerEarRy = max(1, intval($earRy * 0.55));
        imagefilledellipse($img, $headCx - $earOffX, $headCy - $earOffY,
                           $innerEarRx * 2, $innerEarRy * 2, $bgColour);
        imagefilledellipse($img, $headCx + $earOffX, $headCy - $earOffY,
                           $innerEarRx * 2, $innerEarRy * 2, $bgColour);
    }

    // ── "BL" text in yellow ───────────────────────────────────────────────────
    // Only render text at sizes where it will actually be legible.
    if ($size >= 32) {
        $fontSize = max(1, intval($unit * 1.6));

        // Position: centred horizontally, near bottom of drawable area
        $textY = $cy + intval($unit * 3.2);

        // Use GD built-in fonts — font 5 is the largest (13 px tall).
        // We scale by drawing onto an intermediate canvas then resampling
        // for large icons so "BL" looks crisp at any size.
        if ($size <= 64) {
            // At small sizes use font 2 or 3 directly
            $font   = ($size <= 32) ? 2 : 3;
            $charW  = imagefontwidth($font);
            $charH  = imagefontheight($font);
            $text   = 'BL';
            $textX  = $headCx - intval(strlen($text) * $charW / 2);
            imagestring($img, $font, $textX, $textY - intval($charH / 2), $text, $yellow);
        } else {
            // For larger icons: render on an oversized temp canvas, then copy-resample
            $tmpScale  = 8;   // 8× the needed font size
            $tmpFont   = 5;   // largest built-in font
            $tmpCharW  = imagefontwidth($tmpFont);
            $tmpCharH  = imagefontheight($tmpFont);
            $text      = 'BL';
            $tmpW      = strlen($text) * $tmpCharW * $tmpScale;
            $tmpH      = $tmpCharH * $tmpScale;
            $tmp       = imagecreatetruecolor($tmpW, $tmpH);
            $tmpBg     = imagecolorallocatealpha($tmp, 0, 0, 0, 127); // transparent
            imagefill($tmp, 0, 0, $tmpBg);
            imagesavealpha($tmp, true);
            $tmpYellow = imagecolorallocate($tmp, 0xFF, 0xDE, 0x00);
            imagestring($tmp, $tmpFont, 0, 0, $text, $tmpYellow);

            // Destination rect on main image
            $destW = intval($fontSize * 2.4);
            $destH = intval($fontSize * 1.4);
            $destX = $headCx - intval($destW / 2);
            $destY = $textY  - intval($destH / 2);

            imagecopyresampled($img, $tmp, $destX, $destY, 0, 0,
                               $destW, $destH, $tmpW, $tmpH);
            imagedestroy($tmp);
        }
    }
}

// ── Icon specifications ───────────────────────────────────────────────────────

$icons = [
    ['file' => 'icon-16.png',          'size' => 16,  'maskable' => false],
    ['file' => 'icon-32.png',          'size' => 32,  'maskable' => false],
    ['file' => 'apple-touch-icon.png', 'size' => 180, 'maskable' => false],
    ['file' => 'icon-192.png',         'size' => 192, 'maskable' => false],
    ['file' => 'icon-512.png',         'size' => 512, 'maskable' => false],
    ['file' => 'icon-maskable-512.png','size' => 512, 'maskable' => true],
];

$dir = __DIR__ . '/';

// ── Generate ──────────────────────────────────────────────────────────────────

header('Content-Type: text/plain; charset=utf-8');
echo "BoviLogic Icon Generator\n";
echo str_repeat('=', 40) . "\n\n";

foreach ($icons as $spec) {
    $size     = $spec['size'];
    $maskable = $spec['maskable'];
    $path     = $dir . $spec['file'];

    // Create true-colour canvas with transparency support
    $img = imagecreatetruecolor($size, $size);
    imagealphablending($img, true);
    imagesavealpha($img, true);

    // Transparent base (so rounded corners show the page background)
    $transparent = imagecolorallocatealpha($img, 0, 0, 0, 127);
    imagefill($img, 0, 0, $transparent);

    // Pre-allocate brand colours
    $navy   = alloc($img, '#1e2130');
    $white  = alloc($img, '#ffffff');
    $yellow = alloc($img, '#FFDE00');

    drawIcon($img, $size, $maskable, $navy, $white, $yellow);

    if (imagepng($img, $path, 9)) {
        echo "[OK]  {$spec['file']} ({$size}x{$size}" . ($maskable ? ', maskable' : '') . ")\n";
    } else {
        echo "[ERR] {$spec['file']} — could not write to {$path}\n";
    }

    imagedestroy($img);
}

echo "\nDone. Refresh the app to see the new icons.\n";
