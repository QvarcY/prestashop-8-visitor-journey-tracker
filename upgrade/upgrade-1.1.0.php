<?php
/**
 * Upgrade script for CraftIN Visitor Journey 1.1.0.
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_1_1_0($module)
{
    return $module->installTab();
}
