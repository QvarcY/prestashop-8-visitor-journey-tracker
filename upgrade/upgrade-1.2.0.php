<?php
/**
 * Upgrade script for CraftIN Visitor Journey 1.2.0.
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_1_2_0($module)
{
    $ok = true;

    // New privacy/display option: link customer ID may be enabled separately from showing personal details.
    if (Configuration::get('CIVJ_SHOW_CUSTOMER_DETAILS') === false) {
        $ok = $ok && Configuration::updateValue('CIVJ_SHOW_CUSTOMER_DETAILS', 0);
    }

    // Keep compatibility with very early/manual test builds where the id_customer column may not exist.
    $column = Db::getInstance()->executeS('SHOW COLUMNS FROM `' . _DB_PREFIX_ . 'civj_session` LIKE "id_customer"');
    if (empty($column)) {
        $ok = $ok && Db::getInstance()->execute('ALTER TABLE `' . _DB_PREFIX_ . 'civj_session` ADD `id_customer` INT UNSIGNED NULL AFTER `session_key`');
    }

    $index = Db::getInstance()->executeS('SHOW INDEX FROM `' . _DB_PREFIX_ . 'civj_session` WHERE Key_name = "id_customer"');
    if (empty($index)) {
        $ok = $ok && Db::getInstance()->execute('ALTER TABLE `' . _DB_PREFIX_ . 'civj_session` ADD KEY `id_customer` (`id_customer`)');
    }

    if (method_exists($module, 'installTab')) {
        $ok = $ok && $module->installTab();
    }

    return (bool) $ok;
}
