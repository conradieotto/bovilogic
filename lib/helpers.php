<?php
/**
 * BoviLogic – UI Helper Functions
 */

/**
 * Returns an inline SVG beef cattle icon.
 * Drop-in replacement for <i class="fa-solid fa-cow"></i>.
 * Inherits currentColor, scales with font-size.
 *
 * Design: blocky square head, large hindquarters, shoulder withers hump,
 * short thick legs, no udder — characteristic of Angus/Hereford beef breeds.
 */
function beef_cow_icon(string $extraClass = '', string $extraStyle = ''): string
{
    $cls   = $extraClass  ? ' class="' . htmlspecialchars($extraClass)  . '"' : '';
    $style = 'display:inline-block;vertical-align:-0.15em' . ($extraStyle ? ';' . $extraStyle : '');
    return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 80"'
        . ' width="1.25em" height="1em" fill="currentColor" aria-hidden="true"'
        . $cls . ' style="' . $style . '">'
        // Hindquarters — large and rounded (defining beef-breed trait)
        . '<ellipse cx="74" cy="38" rx="20" ry="22"/>'
        // Main body — wide and low
        . '<ellipse cx="48" cy="42" rx="28" ry="18"/>'
        // Shoulder/withers hump
        . '<ellipse cx="30" cy="27" rx="12" ry="9"/>'
        // Neck — short and thick
        . '<rect x="14" y="30" width="18" height="20" rx="5"/>'
        // Head — blocky/square (beef breed, not the narrow dairy head)
        . '<rect x="2" y="27" width="17" height="18" rx="4"/>'
        // Muzzle — wide and square
        . '<rect x="0" y="33" width="9" height="11" rx="2"/>'
        // Ear
        . '<path d="M14 27 L10 19 L19 23 Z"/>'
        // Front legs — short and stocky
        . '<rect x="27" y="57" width="8" height="17" rx="2.5"/>'
        . '<rect x="38" y="58" width="8" height="16" rx="2.5"/>'
        // Back legs — short and stocky
        . '<rect x="62" y="57" width="8" height="17" rx="2.5"/>'
        . '<rect x="73" y="57" width="8" height="17" rx="2.5"/>'
        // Tail — short, curves up, tufted end
        . '<path d="M92 28 C98 22 99 13 94 9" stroke="currentColor" stroke-width="2.5" fill="none" stroke-linecap="round"/>'
        . '<ellipse cx="93" cy="8" rx="3.5" ry="4.5"/>'
        . '</svg>';
}
