<?php
/**
 * BoviLogic – UI Helper Functions
 */

/**
 * Returns an inline SVG cattle head icon (front-facing).
 * Drop-in replacement for <i class="fa-solid fa-cow"></i>.
 * Inherits currentColor, scales with font-size.
 *
 * Design: front-facing cattle head — two curved horns, side ears,
 * oval muzzle with nostrils, and eyes. Unmistakably cattle at any size.
 */
function beef_cow_icon(string $extraClass = '', string $extraStyle = ''): string
{
    $cls   = $extraClass  ? ' class="' . htmlspecialchars($extraClass)  . '"' : '';
    $style = 'display:inline-block;vertical-align:-0.125em' . ($extraStyle ? ';' . $extraStyle : '');
    return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"'
        . ' width="1em" height="1em" fill="none" stroke="currentColor"'
        . ' stroke-width="2" stroke-linecap="round" stroke-linejoin="round"'
        . ' aria-hidden="true"' . $cls . ' style="' . $style . '">'
        // Face outline — taller than wide, roughly same width top to bottom (cattle characteristic)
        . '<path d="M7 8c0-2 2-3 5-3s5 1 5 3v9c0 2.5-2.2 4-5 4s-5-1.5-5-4z"/>'
        // Left ear
        . '<path d="M7 12c-1.5-.5-3 .5-3 2s1.5 2.5 3 2"/>'
        // Right ear
        . '<path d="M17 12c1.5-.5 3 .5 3 2s-1.5 2.5-3 2"/>'
        // Left horn — curves up and out
        . '<path d="M8 7C6 5 3 2 3 4"/>'
        // Right horn — mirror
        . '<path d="M16 7c2-2 5-5 5-3"/>'
        // Muzzle oval
        . '<ellipse cx="12" cy="18" rx="3.5" ry="2"/>'
        // Nostrils — solid filled dots
        . '<circle cx="10.5" cy="18" r=".9" fill="currentColor" stroke="none"/>'
        . '<circle cx="13.5" cy="18" r=".9" fill="currentColor" stroke="none"/>'
        // Eyes — solid filled dots
        . '<circle cx="9.5" cy="12.5" r="1.2" fill="currentColor" stroke="none"/>'
        . '<circle cx="14.5" cy="12.5" r="1.2" fill="currentColor" stroke="none"/>'
        . '</svg>';
}
