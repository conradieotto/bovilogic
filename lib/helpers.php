<?php
/**
 * BoviLogic – UI Helper Functions
 */

/**
 * Returns an inline SVG cattle head icon.
 * Drop-in replacement for <i class="fa-solid fa-cow"></i>.
 * Inherits currentColor, scales with font-size.
 *
 * Design: solid-fill bull/cattle head silhouette — round head, two crescent
 * horns curving upward, two ear bumps on the sides, wider muzzle at base.
 * No face details, reads cleanly as a cattle brand/logo mark at any size.
 */
function beef_cow_icon(string $extraClass = '', string $extraStyle = ''): string
{
    $cls   = $extraClass  ? ' class="' . htmlspecialchars($extraClass)  . '"' : '';
    $style = 'display:inline-block;vertical-align:-0.125em' . ($extraStyle ? ';' . $extraStyle : '');
    return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"'
        . ' width="1em" height="1em" fill="currentColor" aria-hidden="true"'
        . $cls . ' style="' . $style . '">'
        // Main head oval
        . '<ellipse cx="12" cy="13" rx="7.5" ry="8"/>'
        // Muzzle — slightly wider than the head oval, merges at base to give cattle shape
        . '<ellipse cx="12" cy="19.5" rx="5.5" ry="3.5"/>'
        // Left ear — closed crescent bulging to the left
        . '<path d="M4.5 11C1 10 1 16 4.5 16z"/>'
        // Right ear — mirror
        . '<path d="M19.5 11C23 10 23 16 19.5 16z"/>'
        // Left horn — crescent curving up-left
        . '<path d="M7.5 8C5 6 2 3 4 1C6 0 8.5 3 8.5 7z"/>'
        // Right horn — mirror
        . '<path d="M16.5 8C19 6 22 3 20 1C18 0 15.5 3 15.5 7z"/>'
        . '</svg>';
}
