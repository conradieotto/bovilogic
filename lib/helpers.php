<?php
/**
 * BoviLogic – UI Helper Functions
 */

/**
 * Returns the cattle icon used throughout the app.
 * All call sites use this function, so swapping the icon only requires
 * changing this one place.
 */
function beef_cow_icon(string $extraClass = '', string $extraStyle = ''): string
{
    $cls   = $extraClass  ? ' class="fa-solid fa-cow ' . htmlspecialchars($extraClass) . '"' : 'class="fa-solid fa-cow"';
    $style = $extraStyle  ? ' style="' . htmlspecialchars($extraStyle) . '"' : '';
    return '<i ' . $cls . $style . '></i>';
}
