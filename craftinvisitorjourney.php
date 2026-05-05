<?php
/**
 * CraftIN Visitor Journey
 * Lightweight visitor journey analytics for PrestaShop 8.x
 *
 * @author QvarcY / CraftIN
 * @license AFL-3.0
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class Craftinvisitorjourney extends Module
{
    const VERSION = '1.2.0';
    const COOKIE_VISITOR = 'civj_vid';
    const COOKIE_SESSION = 'civj_sid';
    const COOKIE_LAST_TS = 'civj_last_ts';

    public function __construct()
    {
        $this->name = 'craftinvisitorjourney';
        $this->tab = 'analytics_stats';
        $this->version = self::VERSION;
        $this->author = 'QvarcY / CraftIN';
        $this->need_instance = 0;
        $this->bootstrap = true;
        $this->ps_versions_compliancy = [
            'min' => '8.0.0',
            'max' => _PS_VERSION_,
        ];

        parent::__construct();

        $this->displayName = $this->l('CraftIN Visitor Journey');
        $this->description = $this->l('Shows where visitors come from and how they move through your PrestaShop store.');
        $this->confirmUninstall = $this->l('Uninstall CraftIN Visitor Journey? Stored analytics data will be removed.');
    }

    public function install()
    {
        return parent::install()
            && $this->installSql()
            && $this->installConfig()
            && $this->installTab()
            && $this->registerHook('displayHeader')
            && $this->registerHook('actionFrontControllerSetMedia');
    }

    public function uninstall()
    {
        return $this->uninstallTab()
            && $this->uninstallSql()
            && $this->uninstallConfig()
            && parent::uninstall();
    }

    public function installTab()
    {
        if (!class_exists('Tab')) {
            return true;
        }

        $className = 'AdminCraftinVisitorJourney';
        $idTab = (int) Tab::getIdFromClassName($className);
        $tab = $idTab > 0 ? new Tab($idTab) : new Tab();

        $tab->active = 1;
        $tab->class_name = $className;
        $tab->module = $this->name;
        $tab->icon = 'timeline';
        $tab->id_parent = $this->getPreferredParentTabId();

        foreach (Language::getLanguages(false) as $lang) {
            $tab->name[(int) $lang['id_lang']] = 'Apmeklētāju ceļi';
        }

        return $idTab > 0 ? (bool) $tab->update() : (bool) $tab->add();
    }

    public function uninstallTab()
    {
        if (!class_exists('Tab')) {
            return true;
        }

        $idTab = (int) Tab::getIdFromClassName('AdminCraftinVisitorJourney');
        if ($idTab <= 0) {
            return true;
        }

        $tab = new Tab($idTab);
        return (bool) $tab->delete();
    }

    private function getPreferredParentTabId()
    {
        if (!class_exists('Tab')) {
            return 0;
        }

        $parents = [
            'AdminParentStats',
            'AdminStats',
            'SELL',
            'AdminDashboard',
        ];

        foreach ($parents as $className) {
            $id = (int) Tab::getIdFromClassName($className);
            if ($id > 0) {
                return $id;
            }
        }

        return 0;
    }

    private function installConfig()
    {
        return Configuration::updateValue('CIVJ_ENABLED', 1)
            && Configuration::updateValue('CIVJ_REQUIRE_CONSENT', 0)
            && Configuration::updateValue('CIVJ_CONSENT_COOKIES', 'cicc_analytics,craftin_cookie_analytics,cookie_consent_analytics,analytics_consent,cc_analytics,CIVJ_ANALYTICS_OK')
            && Configuration::updateValue('CIVJ_RETENTION_DAYS', 90)
            && Configuration::updateValue('CIVJ_LINK_CUSTOMER', 0)
            && Configuration::updateValue('CIVJ_SHOW_CUSTOMER_DETAILS', 0)
            && Configuration::updateValue('CIVJ_RESPECT_DNT', 1)
            && Configuration::updateValue('CIVJ_IGNORE_BOTS', 1);
    }

    private function uninstallConfig()
    {
        $keys = [
            'CIVJ_ENABLED',
            'CIVJ_REQUIRE_CONSENT',
            'CIVJ_CONSENT_COOKIES',
            'CIVJ_RETENTION_DAYS',
            'CIVJ_LINK_CUSTOMER',
            'CIVJ_SHOW_CUSTOMER_DETAILS',
            'CIVJ_RESPECT_DNT',
            'CIVJ_IGNORE_BOTS',
        ];

        foreach ($keys as $key) {
            Configuration::deleteByName($key);
        }

        return true;
    }

    private function installSql()
    {
        $sql = [];

        $sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'civj_session` (
            `id_civj_session` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `visitor_key` VARCHAR(64) NOT NULL,
            `session_key` VARCHAR(64) NOT NULL,
            `id_customer` INT UNSIGNED NULL,
            `first_url` TEXT NULL,
            `first_referrer` TEXT NULL,
            `source` VARCHAR(128) NULL,
            `medium` VARCHAR(128) NULL,
            `campaign` VARCHAR(255) NULL,
            `device` VARCHAR(32) NULL,
            `user_agent_hash` VARCHAR(64) NULL,
            `ip_hash` VARCHAR(64) NULL,
            `date_add` DATETIME NOT NULL,
            `date_upd` DATETIME NOT NULL,
            PRIMARY KEY (`id_civj_session`),
            KEY `visitor_key` (`visitor_key`),
            UNIQUE KEY `session_key` (`session_key`),
            KEY `id_customer` (`id_customer`),
            KEY `source` (`source`),
            KEY `date_add` (`date_add`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';

        $sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'civj_pageview` (
            `id_civj_pageview` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `id_civj_session` INT UNSIGNED NOT NULL,
            `url` TEXT NOT NULL,
            `page_title` VARCHAR(255) NULL,
            `controller` VARCHAR(128) NULL,
            `id_product` INT UNSIGNED NULL,
            `id_category` INT UNSIGNED NULL,
            `referrer` TEXT NULL,
            `time_on_previous` INT UNSIGNED NULL,
            `screen` VARCHAR(32) NULL,
            `date_add` DATETIME NOT NULL,
            PRIMARY KEY (`id_civj_pageview`),
            KEY `id_civj_session` (`id_civj_session`),
            KEY `controller` (`controller`),
            KEY `id_product` (`id_product`),
            KEY `id_category` (`id_category`),
            KEY `date_add` (`date_add`),
            CONSTRAINT `' . _DB_PREFIX_ . 'civj_pageview_session_fk` FOREIGN KEY (`id_civj_session`) REFERENCES `' . _DB_PREFIX_ . 'civj_session` (`id_civj_session`) ON DELETE CASCADE
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';

        foreach ($sql as $query) {
            if (!Db::getInstance()->execute($query)) {
                return false;
            }
        }

        return true;
    }

    private function uninstallSql()
    {
        $sql = [];
        $sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'civj_pageview`';
        $sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'civj_session`';

        foreach ($sql as $query) {
            if (!Db::getInstance()->execute($query)) {
                return false;
            }
        }

        return true;
    }

    public function hookActionFrontControllerSetMedia($params)
    {
        if (!(bool) Configuration::get('CIVJ_ENABLED')) {
            return;
        }

        if ($this->isBotRequest() && (bool) Configuration::get('CIVJ_IGNORE_BOTS')) {
            return;
        }

        $this->context->controller->registerJavascript(
            'module-' . $this->name . '-tracker',
            'modules/' . $this->name . '/views/js/tracker.js',
            [
                'position' => 'bottom',
                'priority' => 200,
                'version' => $this->version,
            ]
        );
    }

    public function hookDisplayHeader($params)
    {
        if (!(bool) Configuration::get('CIVJ_ENABLED')) {
            return '';
        }

        if ($this->isBotRequest() && (bool) Configuration::get('CIVJ_IGNORE_BOTS')) {
            return '';
        }

        $controller = isset($this->context->controller->php_self) ? (string) $this->context->controller->php_self : '';
        $idProduct = (int) Tools::getValue('id_product');
        $idCategory = (int) Tools::getValue('id_category');

        $config = [
            'collectUrl' => $this->context->link->getModuleLink($this->name, 'collect', [], true),
            'requireConsent' => (bool) Configuration::get('CIVJ_REQUIRE_CONSENT'),
            'consentCookies' => $this->getConsentCookieNames(),
            'respectDnt' => (bool) Configuration::get('CIVJ_RESPECT_DNT'),
            'sessionMinutes' => 30,
            'context' => [
                'controller' => $controller,
                'id_product' => $idProduct > 0 ? $idProduct : null,
                'id_category' => $idCategory > 0 ? $idCategory : null,
            ],
        ];

        return '<script>window.civjConfig = ' . json_encode($config) . ';</script>' . "\n";
    }

    public function getContent()
    {
        $output = '';

        if (class_exists('Tab') && (int) Tab::getIdFromClassName('AdminCraftinVisitorJourney') <= 0) {
            $this->installTab();
        }

        if (Tools::isSubmit('submitCivjSettings')) {
            Configuration::updateValue('CIVJ_ENABLED', (int) Tools::getValue('CIVJ_ENABLED'));
            Configuration::updateValue('CIVJ_REQUIRE_CONSENT', (int) Tools::getValue('CIVJ_REQUIRE_CONSENT'));
            Configuration::updateValue('CIVJ_CONSENT_COOKIES', trim((string) Tools::getValue('CIVJ_CONSENT_COOKIES')));
            Configuration::updateValue('CIVJ_RETENTION_DAYS', max(1, (int) Tools::getValue('CIVJ_RETENTION_DAYS')));
            Configuration::updateValue('CIVJ_LINK_CUSTOMER', (int) Tools::getValue('CIVJ_LINK_CUSTOMER'));
            Configuration::updateValue('CIVJ_SHOW_CUSTOMER_DETAILS', (int) Tools::getValue('CIVJ_SHOW_CUSTOMER_DETAILS'));
            Configuration::updateValue('CIVJ_RESPECT_DNT', (int) Tools::getValue('CIVJ_RESPECT_DNT'));
            Configuration::updateValue('CIVJ_IGNORE_BOTS', (int) Tools::getValue('CIVJ_IGNORE_BOTS'));
            $output .= $this->displayConfirmation($this->l('Settings updated.'));
        }

        if (Tools::isSubmit('submitCivjPurge')) {
            $this->purgeOldData((int) Configuration::get('CIVJ_RETENTION_DAYS'), true);
            $output .= $this->displayConfirmation($this->l('Old analytics data was cleaned.'));
        }

        if (Tools::isSubmit('submitCivjClearAll')) {
            Db::getInstance()->execute('DELETE FROM `' . _DB_PREFIX_ . 'civj_pageview`');
            Db::getInstance()->execute('DELETE FROM `' . _DB_PREFIX_ . 'civj_session`');
            Db::getInstance()->execute('ALTER TABLE `' . _DB_PREFIX_ . 'civj_pageview` AUTO_INCREMENT = 1');
            Db::getInstance()->execute('ALTER TABLE `' . _DB_PREFIX_ . 'civj_session` AUTO_INCREMENT = 1');
            $output .= $this->displayConfirmation($this->l('All analytics data was deleted.'));
        }

        $output .= $this->renderIntroPanel();
        $output .= $this->renderSettingsForm();

        $idSession = (int) Tools::getValue('civj_view_session');
        if ($idSession > 0) {
            $output .= $this->renderSessionDetail($idSession);
        }

        $output .= $this->renderDashboard();

        return $output;
    }

    private function renderIntroPanel()
    {
        $statsUrl = $this->getAdminStatsUrl();

        return '<div class="panel">
            <h3><i class="icon-bar-chart"></i> ' . $this->escapeHtml($this->l('CraftIN Visitor Journey')) . '</h3>
            <p>' . $this->escapeHtml($this->l('This module records anonymous visitor sessions, traffic sources and page paths inside your store.')) . '</p>
            <p><a href="' . $this->escapeHtml($statsUrl) . '" class="btn btn-primary"><i class="icon-line-chart"></i> ' . $this->escapeHtml($this->l('Open visitor statistics')) . '</a></p>
            <div class="alert alert-warning">
                <strong>' . $this->escapeHtml($this->l('GDPR note:')) . '</strong> ' . $this->escapeHtml($this->l('For live usage, connect this module with your cookie consent solution and clearly mention analytics tracking in your privacy/cookie policy. IP addresses and user agents are stored only as hashes.')) . '
            </div>
        </div>';
    }

    private function renderSettingsForm()
    {
        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->name;
        $helper->module = $this;
        $helper->default_form_language = (int) Configuration::get('PS_LANG_DEFAULT');
        $helper->allow_employee_form_lang = (int) Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG');
        $helper->identifier = $this->name;
        $helper->submit_action = 'submitCivjSettings';
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $fieldsForm = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Settings'),
                    'icon' => 'icon-cogs',
                ],
                'input' => [
                    [
                        'type' => 'switch',
                        'label' => $this->l('Enable tracking'),
                        'name' => 'CIVJ_ENABLED',
                        'is_bool' => true,
                        'values' => $this->switchValues(),
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Require analytics consent cookie'),
                        'name' => 'CIVJ_REQUIRE_CONSENT',
                        'is_bool' => true,
                        'desc' => $this->l('Recommended for public live shops. Tracking starts only if one of the configured cookies contains an accepted/granted value.'),
                        'values' => $this->switchValues(),
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Consent cookie names'),
                        'name' => 'CIVJ_CONSENT_COOKIES',
                        'desc' => $this->l('Comma-separated cookie names. Example: cicc_analytics, cookie_consent_analytics'),
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Data retention days'),
                        'name' => 'CIVJ_RETENTION_DAYS',
                        'suffix' => $this->l('days'),
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Link logged-in customer ID'),
                        'name' => 'CIVJ_LINK_CUSTOMER',
                        'is_bool' => true,
                        'desc' => $this->l('Disabled by default. Enable only if you really need to connect sessions with logged-in customers.'),
                        'values' => $this->switchValues(),
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Show customer name and email in statistics'),
                        'name' => 'CIVJ_SHOW_CUSTOMER_DETAILS',
                        'is_bool' => true,
                        'desc' => $this->l('If enabled, Back Office statistics will show customer name, email and a link to the customer profile for linked logged-in sessions.'),
                        'values' => $this->switchValues(),
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Respect Do Not Track'),
                        'name' => 'CIVJ_RESPECT_DNT',
                        'is_bool' => true,
                        'values' => $this->switchValues(),
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Ignore common bots'),
                        'name' => 'CIVJ_IGNORE_BOTS',
                        'is_bool' => true,
                        'values' => $this->switchValues(),
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Save'),
                    'class' => 'btn btn-default pull-right',
                ],
            ],
        ];

        $helper->fields_value = [
            'CIVJ_ENABLED' => (int) Configuration::get('CIVJ_ENABLED'),
            'CIVJ_REQUIRE_CONSENT' => (int) Configuration::get('CIVJ_REQUIRE_CONSENT'),
            'CIVJ_CONSENT_COOKIES' => (string) Configuration::get('CIVJ_CONSENT_COOKIES'),
            'CIVJ_RETENTION_DAYS' => (int) Configuration::get('CIVJ_RETENTION_DAYS'),
            'CIVJ_LINK_CUSTOMER' => (int) Configuration::get('CIVJ_LINK_CUSTOMER'),
            'CIVJ_SHOW_CUSTOMER_DETAILS' => (int) Configuration::get('CIVJ_SHOW_CUSTOMER_DETAILS'),
            'CIVJ_RESPECT_DNT' => (int) Configuration::get('CIVJ_RESPECT_DNT'),
            'CIVJ_IGNORE_BOTS' => (int) Configuration::get('CIVJ_IGNORE_BOTS'),
        ];

        $cleanup = '<div class="panel">
            <h3><i class="icon-trash"></i> ' . $this->escapeHtml($this->l('Data cleanup')) . '</h3>
            <form method="post" style="display:inline-block;margin-right:10px;">
                <button type="submit" name="submitCivjPurge" class="btn btn-default" onclick="return confirm(\'' . $this->escapeJs($this->l('Clean old analytics data?')) . '\');">
                    <i class="icon-trash"></i> ' . $this->escapeHtml($this->l('Clean old data')) . '
                </button>
            </form>
            <form method="post" style="display:inline-block;">
                <button type="submit" name="submitCivjClearAll" class="btn btn-danger" onclick="return confirm(\'' . $this->escapeJs($this->l('Delete ALL visitor journey data?')) . '\');">
                    <i class="icon-remove"></i> ' . $this->escapeHtml($this->l('Delete all data')) . '
                </button>
            </form>
        </div>';

        return $helper->generateForm([$fieldsForm]) . $cleanup;
    }

    public function renderDashboard($baseUrl = null)
    {
        if ($baseUrl === null) {
            $baseUrl = AdminController::$currentIndex . '&configure=' . $this->name . '&token=' . Tools::getAdminTokenLite('AdminModules');
        }

        $sessions7 = (int) Db::getInstance()->getValue('SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'civj_session` WHERE `date_add` >= DATE_SUB(NOW(), INTERVAL 7 DAY)');
        $sessions30 = (int) Db::getInstance()->getValue('SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'civj_session` WHERE `date_add` >= DATE_SUB(NOW(), INTERVAL 30 DAY)');
        $pageviews30 = (int) Db::getInstance()->getValue('SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'civj_pageview` WHERE `date_add` >= DATE_SUB(NOW(), INTERVAL 30 DAY)');
        $customerSessions30 = (int) Db::getInstance()->getValue('SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'civj_session` WHERE `id_customer` IS NOT NULL AND `id_customer` > 0 AND `date_add` >= DATE_SUB(NOW(), INTERVAL 30 DAY)');
        $avgPages = (float) Db::getInstance()->getValue('SELECT AVG(pv_count) FROM (SELECT COUNT(*) AS pv_count FROM `' . _DB_PREFIX_ . 'civj_pageview` GROUP BY `id_civj_session`) x');

        $topSources = Db::getInstance()->executeS('SELECT COALESCE(NULLIF(`source`, ""), "direct") AS source, COUNT(*) AS total
            FROM `' . _DB_PREFIX_ . 'civj_session`
            WHERE `date_add` >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY source
            ORDER BY total DESC
            LIMIT 10');

        $recentSessions = Db::getInstance()->executeS('SELECT s.*, c.firstname, c.lastname, c.email, COALESCE(p.pageviews, 0) AS pageviews, p.last_pageview
            FROM `' . _DB_PREFIX_ . 'civj_session` s
            LEFT JOIN `' . _DB_PREFIX_ . 'customer` c ON c.id_customer = s.id_customer
            LEFT JOIN (
                SELECT `id_civj_session`, COUNT(*) AS pageviews, MAX(`date_add`) AS last_pageview
                FROM `' . _DB_PREFIX_ . 'civj_pageview`
                GROUP BY `id_civj_session`
            ) p ON p.id_civj_session = s.id_civj_session
            ORDER BY s.date_add DESC
            LIMIT 30');

        $html = '<div class="panel civj-dashboard">
            <h3><i class="icon-dashboard"></i> ' . $this->escapeHtml($this->l('Visitor journey overview')) . '</h3>
            <div class="row">
                ' . $this->metricBox($this->l('Sessions / 7 days'), $sessions7) . '
                ' . $this->metricBox($this->l('Sessions / 30 days'), $sessions30) . '
                ' . $this->metricBox($this->l('Pageviews / 30 days'), $pageviews30) . '
                ' . $this->metricBox($this->l('Logged-in sessions / 30 days'), $customerSessions30) . '
                ' . $this->metricBox($this->l('Avg. pages / session'), number_format($avgPages, 2)) . '
            </div>
            <hr>
            <div class="row">
                <div class="col-lg-4">
                    <h4>' . $this->escapeHtml($this->l('Top sources / 30 days')) . '</h4>
                    <table class="table">
                        <thead><tr><th>' . $this->escapeHtml($this->l('Source')) . '</th><th class="text-right">' . $this->escapeHtml($this->l('Sessions')) . '</th></tr></thead>
                        <tbody>';

        if ($topSources) {
            foreach ($topSources as $row) {
                $html .= '<tr><td>' . $this->escapeHtml($row['source']) . '</td><td class="text-right">' . (int) $row['total'] . '</td></tr>';
            }
        } else {
            $html .= '<tr><td colspan="2" class="text-muted">' . $this->escapeHtml($this->l('No data yet.')) . '</td></tr>';
        }

        $html .= '</tbody></table>
                </div>
                <div class="col-lg-8">
                    <h4>' . $this->escapeHtml($this->l('Recent sessions')) . '</h4>
                    <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>' . $this->escapeHtml($this->l('Date')) . '</th>
                                <th>' . $this->escapeHtml($this->l('Source')) . '</th>
                                <th>' . $this->escapeHtml($this->l('Customer')) . '</th>
                                <th>' . $this->escapeHtml($this->l('Landing page')) . '</th>
                                <th class="text-center">' . $this->escapeHtml($this->l('Pages')) . '</th>
                                <th>' . $this->escapeHtml($this->l('Device')) . '</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>';

        if ($recentSessions) {
            foreach ($recentSessions as $session) {
                $viewUrl = $this->appendUrlParams($baseUrl, ['civj_view_session' => (int) $session['id_civj_session']]);
                $html .= '<tr>
                    <td>' . $this->escapeHtml($session['date_add']) . '</td>
                    <td><strong>' . $this->escapeHtml($session['source'] ?: 'direct') . '</strong><br><small>' . $this->escapeHtml($session['medium'] ?: '') . '</small></td>
                    <td>' . $this->renderCustomerCell($session) . '</td>
                    <td style="max-width:260px;word-break:break-word;">' . $this->shortUrl($session['first_url']) . '</td>
                    <td class="text-center">' . (int) $session['pageviews'] . '</td>
                    <td>' . $this->escapeHtml($session['device'] ?: '-') . '</td>
                    <td class="text-right"><a class="btn btn-default btn-xs" href="' . $this->escapeHtml($viewUrl) . '"><i class="icon-search"></i> ' . $this->escapeHtml($this->l('View path')) . '</a></td>
                </tr>';
            }
        } else {
            $html .= '<tr><td colspan="7" class="text-muted">' . $this->escapeHtml($this->l('No visitor sessions recorded yet.')) . '</td></tr>';
        }

        $html .= '</tbody></table></div>
                </div>
            </div>
        </div>';

        return $html;
    }

    public function renderSessionDetail($idSession, $backUrl = null)
    {
        $session = Db::getInstance()->getRow('SELECT s.*, c.firstname, c.lastname, c.email FROM `' . _DB_PREFIX_ . 'civj_session` s LEFT JOIN `' . _DB_PREFIX_ . 'customer` c ON c.id_customer = s.id_customer WHERE s.`id_civj_session` = ' . (int) $idSession);
        if (!$session) {
            return $this->displayError($this->l('Session not found.'));
        }

        $pageviews = Db::getInstance()->executeS('SELECT * FROM `' . _DB_PREFIX_ . 'civj_pageview` WHERE `id_civj_session` = ' . (int) $idSession . ' ORDER BY `date_add` ASC');

        $backButton = '';
        if ($backUrl) {
            $backButton = '<p><a class="btn btn-default" href="' . $this->escapeHtml($backUrl) . '"><i class="icon-arrow-left"></i> ' . $this->escapeHtml($this->l('Back to statistics')) . '</a></p>';
        }

        $html = '<div class="panel">
            <h3><i class="icon-road"></i> ' . $this->escapeHtml($this->l('Visitor path')) . ' #' . (int) $idSession . '</h3>' . $backButton . '
            <div class="row">
                <div class="col-lg-12"><strong>' . $this->escapeHtml($this->l('Customer')) . ':</strong><br>' . $this->renderCustomerPanel($session) . '</div>
            </div>
            <hr>
            <div class="row">
                <div class="col-lg-3"><strong>' . $this->escapeHtml($this->l('Source')) . ':</strong><br>' . $this->escapeHtml($session['source'] ?: 'direct') . '</div>
                <div class="col-lg-3"><strong>' . $this->escapeHtml($this->l('Medium')) . ':</strong><br>' . $this->escapeHtml($session['medium'] ?: '-') . '</div>
                <div class="col-lg-3"><strong>' . $this->escapeHtml($this->l('Campaign')) . ':</strong><br>' . $this->escapeHtml($session['campaign'] ?: '-') . '</div>
                <div class="col-lg-3"><strong>' . $this->escapeHtml($this->l('Device')) . ':</strong><br>' . $this->escapeHtml($session['device'] ?: '-') . '</div>
            </div>
            <hr>
            <p><strong>' . $this->escapeHtml($this->l('First referrer')) . ':</strong> ' . $this->shortUrl($session['first_referrer']) . '</p>
            <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>' . $this->escapeHtml($this->l('Time')) . '</th>
                        <th>' . $this->escapeHtml($this->l('Page')) . '</th>
                        <th>' . $this->escapeHtml($this->l('Controller')) . '</th>
                        <th>' . $this->escapeHtml($this->l('Time on previous')) . '</th>
                    </tr>
                </thead>
                <tbody>';

        if ($pageviews) {
            $i = 1;
            foreach ($pageviews as $pv) {
                $meta = [];
                if ((int) $pv['id_product'] > 0) {
                    $meta[] = 'Product #' . (int) $pv['id_product'];
                }
                if ((int) $pv['id_category'] > 0) {
                    $meta[] = 'Category #' . (int) $pv['id_category'];
                }
                $timePrev = $pv['time_on_previous'] !== null ? ((int) $pv['time_on_previous'] . 's') : '-';
                $html .= '<tr>
                    <td>' . $i++ . '</td>
                    <td>' . $this->escapeHtml($pv['date_add']) . '</td>
                    <td style="max-width:480px;word-break:break-word;"><strong>' . $this->escapeHtml($pv['page_title'] ?: '-') . '</strong><br>' . $this->shortUrl($pv['url']) . '</td>
                    <td>' . $this->escapeHtml($pv['controller'] ?: '-') . '<br><small>' . $this->escapeHtml(implode(', ', $meta)) . '</small></td>
                    <td>' . $this->escapeHtml($timePrev) . '</td>
                </tr>';
            }
        } else {
            $html .= '<tr><td colspan="5" class="text-muted">' . $this->escapeHtml($this->l('No pageviews for this session.')) . '</td></tr>';
        }

        $html .= '</tbody></table></div></div>';

        return $html;
    }

    public function renderAdminStatisticsPage($baseUrl = null)
    {
        if ($baseUrl === null) {
            $baseUrl = $this->getAdminStatsUrl();
        }

        $output = '<div class="panel">
            <h3><i class="icon-line-chart"></i> ' . $this->escapeHtml($this->l('Apmeklētāju ceļi')) . '</h3>
            <p>' . $this->escapeHtml($this->l('Here you can see traffic sources, recent visitor sessions and page paths inside the store.')) . '</p>
            <p>
                <a class="btn btn-default" href="' . $this->escapeHtml($this->getModuleConfigurationUrl()) . '"><i class="icon-cogs"></i> ' . $this->escapeHtml($this->l('Module settings')) . '</a>
            </p>
        </div>';

        $idSession = (int) Tools::getValue('civj_view_session');
        if ($idSession > 0) {
            $output .= $this->renderSessionDetail($idSession, $baseUrl);
        }

        $output .= $this->renderDashboard($baseUrl);

        return $output;
    }

    private function renderCustomerCell(array $session)
    {
        if (empty($session['id_customer'])) {
            return '<span class="text-muted">' . $this->escapeHtml($this->l('Guest')) . '</span>';
        }

        if (!(bool) Configuration::get('CIVJ_SHOW_CUSTOMER_DETAILS')) {
            return '<span class="label label-info">' . $this->escapeHtml($this->l('Customer')) . ' #' . (int) $session['id_customer'] . '</span>';
        }

        $name = trim((string) (isset($session['firstname']) ? $session['firstname'] : '') . ' ' . (string) (isset($session['lastname']) ? $session['lastname'] : ''));
        $email = (string) (isset($session['email']) ? $session['email'] : '');
        $label = $name !== '' ? $name : ('Customer #' . (int) $session['id_customer']);
        $url = $this->getCustomerAdminUrl((int) $session['id_customer']);

        $html = '<a href="' . $this->escapeHtml($url) . '"><strong>' . $this->escapeHtml($label) . '</strong></a>';
        if ($email !== '') {
            $html .= '<br><small>' . $this->escapeHtml($email) . '</small>';
        }

        return $html;
    }

    private function renderCustomerPanel(array $session)
    {
        if (empty($session['id_customer'])) {
            return '<span class="text-muted">' . $this->escapeHtml($this->l('Guest / not logged in during this session')) . '</span>';
        }

        if (!(bool) Configuration::get('CIVJ_SHOW_CUSTOMER_DETAILS')) {
            return '<span class="label label-info">' . $this->escapeHtml($this->l('Linked customer')) . ' #' . (int) $session['id_customer'] . '</span> '
                . '<span class="text-muted">' . $this->escapeHtml($this->l('Enable customer details in module settings to show name and email.')) . '</span>';
        }

        $name = trim((string) (isset($session['firstname']) ? $session['firstname'] : '') . ' ' . (string) (isset($session['lastname']) ? $session['lastname'] : ''));
        $email = (string) (isset($session['email']) ? $session['email'] : '');
        $label = $name !== '' ? $name : ('Customer #' . (int) $session['id_customer']);
        $url = $this->getCustomerAdminUrl((int) $session['id_customer']);

        $html = '<a class="btn btn-default btn-xs" href="' . $this->escapeHtml($url) . '"><i class="icon-user"></i> ' . $this->escapeHtml($this->l('Open customer profile')) . '</a> ';
        $html .= '<strong>' . $this->escapeHtml($label) . '</strong>';
        if ($email !== '') {
            $html .= ' <span class="text-muted">' . $this->escapeHtml($email) . '</span>';
        }
        $html .= ' <span class="label label-info">ID ' . (int) $session['id_customer'] . '</span>';

        return $html;
    }

    private function getCustomerAdminUrl($idCustomer)
    {
        return $this->context->link->getAdminLink('AdminCustomers', true, [], [
            'id_customer' => (int) $idCustomer,
            'viewcustomer' => 1,
        ]);
    }

    public function getAdminStatsUrl(array $params = [])
    {
        $url = $this->context->link->getAdminLink('AdminCraftinVisitorJourney', true);
        return $this->appendUrlParams($url, $params);
    }

    public function getModuleConfigurationUrl()
    {
        return $this->context->link->getAdminLink('AdminModules', true, [], [
            'configure' => $this->name,
            'tab_module' => $this->tab,
            'module_name' => $this->name,
        ]);
    }

    private function appendUrlParams($url, array $params)
    {
        if (!$params) {
            return $url;
        }

        $separator = (strpos($url, '?') === false) ? '?' : '&';
        return $url . $separator . http_build_query($params, '', '&');
    }

    private function metricBox($label, $value)
    {
        return '<div class="col-lg-3 col-md-6">
            <div class="well" style="min-height:92px;">
                <div class="text-muted">' . $this->escapeHtml($label) . '</div>
                <div style="font-size:28px;font-weight:700;line-height:1.4;">' . $this->escapeHtml((string) $value) . '</div>
            </div>
        </div>';
    }

    private function switchValues()
    {
        return [
            [
                'id' => 'active_on',
                'value' => 1,
                'label' => $this->l('Enabled'),
            ],
            [
                'id' => 'active_off',
                'value' => 0,
                'label' => $this->l('Disabled'),
            ],
        ];
    }

    public function recordPageView(array $data)
    {
        if (!(bool) Configuration::get('CIVJ_ENABLED')) {
            return false;
        }

        if ((bool) Configuration::get('CIVJ_RESPECT_DNT') && $this->hasDoNotTrack()) {
            return false;
        }

        if ((bool) Configuration::get('CIVJ_REQUIRE_CONSENT') && !$this->hasAnalyticsConsent()) {
            return false;
        }

        if ((bool) Configuration::get('CIVJ_IGNORE_BOTS') && $this->isBotRequest()) {
            return false;
        }

        $visitorKey = $this->sanitizeKey(isset($data['visitor_key']) ? $data['visitor_key'] : '');
        $sessionKey = $this->sanitizeKey(isset($data['session_key']) ? $data['session_key'] : '');
        $url = $this->sanitizeText(isset($data['url']) ? $data['url'] : '', 2048);

        if (!$visitorKey || !$sessionKey || !$url) {
            return false;
        }

        if (!$this->isShopUrl($url)) {
            return false;
        }

        $contextData = isset($data['context']) && is_array($data['context']) ? $data['context'] : [];
        $controller = $this->sanitizeText(isset($contextData['controller']) ? $contextData['controller'] : '', 128);
        $idProduct = isset($contextData['id_product']) ? (int) $contextData['id_product'] : 0;
        $idCategory = isset($contextData['id_category']) ? (int) $contextData['id_category'] : 0;
        $referrer = $this->sanitizeText(isset($data['referrer']) ? $data['referrer'] : '', 2048);
        $title = $this->sanitizeText(isset($data['title']) ? $data['title'] : '', 255);
        $screen = $this->sanitizeText(isset($data['screen']) ? $data['screen'] : '', 32);
        $timeOnPrevious = isset($data['time_on_previous']) ? (int) $data['time_on_previous'] : null;
        if ($timeOnPrevious !== null && ($timeOnPrevious < 0 || $timeOnPrevious > 3600)) {
            $timeOnPrevious = null;
        }

        $idSession = $this->getOrCreateSession($visitorKey, $sessionKey, $url, $referrer);
        if (!$idSession) {
            return false;
        }

        $insert = [
            'id_civj_session' => (int) $idSession,
            'url' => pSQL($url),
            'page_title' => pSQL($title),
            'controller' => pSQL($controller),
            'id_product' => $idProduct > 0 ? (int) $idProduct : null,
            'id_category' => $idCategory > 0 ? (int) $idCategory : null,
            'referrer' => pSQL($referrer),
            'time_on_previous' => $timeOnPrevious !== null ? (int) $timeOnPrevious : null,
            'screen' => pSQL($screen),
            'date_add' => date('Y-m-d H:i:s'),
        ];

        $ok = Db::getInstance()->insert('civj_pageview', $insert);
        Db::getInstance()->update('civj_session', ['date_upd' => date('Y-m-d H:i:s')], 'id_civj_session = ' . (int) $idSession);

        if (mt_rand(1, 100) === 7) {
            $this->purgeOldData((int) Configuration::get('CIVJ_RETENTION_DAYS'), false);
        }

        return $ok;
    }

    private function syncLoggedInCustomerForSession($idSession)
    {
        if (!(bool) Configuration::get('CIVJ_LINK_CUSTOMER')) {
            return true;
        }

        if (!isset($this->context->customer) || !Validate::isLoadedObject($this->context->customer) || !$this->context->customer->isLogged()) {
            return true;
        }

        $idCustomer = (int) $this->context->customer->id;
        if ($idCustomer <= 0) {
            return true;
        }

        $currentIdCustomer = (int) Db::getInstance()->getValue('SELECT `id_customer` FROM `' . _DB_PREFIX_ . 'civj_session` WHERE `id_civj_session` = ' . (int) $idSession);
        if ($currentIdCustomer === $idCustomer) {
            return true;
        }

        if ($currentIdCustomer > 0) {
            return true;
        }

        return Db::getInstance()->update('civj_session', [
            'id_customer' => (int) $idCustomer,
            'date_upd' => date('Y-m-d H:i:s'),
        ], 'id_civj_session = ' . (int) $idSession);
    }

    private function getOrCreateSession($visitorKey, $sessionKey, $url, $referrer)
    {
        $idSession = (int) Db::getInstance()->getValue('SELECT `id_civj_session` FROM `' . _DB_PREFIX_ . 'civj_session` WHERE `session_key` = "' . pSQL($sessionKey) . '"');
        if ($idSession > 0) {
            $this->syncLoggedInCustomerForSession($idSession);
            return $idSession;
        }

        $sourceData = $this->detectTrafficSource($url, $referrer);
        $ua = isset($_SERVER['HTTP_USER_AGENT']) ? (string) $_SERVER['HTTP_USER_AGENT'] : '';
        $ip = Tools::getRemoteAddr();
        $idCustomer = null;

        if ((bool) Configuration::get('CIVJ_LINK_CUSTOMER') && isset($this->context->customer) && $this->context->customer->isLogged()) {
            $idCustomer = (int) $this->context->customer->id;
        }

        $insert = [
            'visitor_key' => pSQL($visitorKey),
            'session_key' => pSQL($sessionKey),
            'id_customer' => $idCustomer,
            'first_url' => pSQL($url),
            'first_referrer' => pSQL($referrer),
            'source' => pSQL($sourceData['source']),
            'medium' => pSQL($sourceData['medium']),
            'campaign' => pSQL($sourceData['campaign']),
            'device' => pSQL($this->detectDevice($ua)),
            'user_agent_hash' => pSQL(hash('sha256', _COOKIE_KEY_ . '|' . $ua)),
            'ip_hash' => pSQL(hash('sha256', _COOKIE_KEY_ . '|' . $ip)),
            'date_add' => date('Y-m-d H:i:s'),
            'date_upd' => date('Y-m-d H:i:s'),
        ];

        if (!Db::getInstance()->insert('civj_session', $insert)) {
            return 0;
        }

        return (int) Db::getInstance()->Insert_ID();
    }

    private function detectTrafficSource($url, $referrer)
    {
        $source = '';
        $medium = '';
        $campaign = '';

        $parts = parse_url($url);
        if (!empty($parts['query'])) {
            parse_str($parts['query'], $query);
            if (!empty($query['utm_source'])) {
                $source = $this->sanitizeText($query['utm_source'], 128);
                $medium = $this->sanitizeText(isset($query['utm_medium']) ? $query['utm_medium'] : '', 128);
                $campaign = $this->sanitizeText(isset($query['utm_campaign']) ? $query['utm_campaign'] : '', 255);
                return [
                    'source' => $source,
                    'medium' => $medium ?: 'campaign',
                    'campaign' => $campaign,
                ];
            }
        }

        if (!$referrer) {
            return ['source' => 'direct', 'medium' => 'none', 'campaign' => ''];
        }

        $refHost = strtolower((string) parse_url($referrer, PHP_URL_HOST));
        $shopHost = strtolower((string) parse_url($this->context->shop->getBaseURL(true, true), PHP_URL_HOST));

        if (!$refHost || $refHost === $shopHost || Tools::substr($refHost, -Tools::strlen('.' . $shopHost)) === '.' . $shopHost) {
            return ['source' => 'internal', 'medium' => 'referral', 'campaign' => ''];
        }

        $map = [
            'google.' => ['google', 'organic'],
            'bing.' => ['bing', 'organic'],
            'duckduckgo.' => ['duckduckgo', 'organic'],
            'yahoo.' => ['yahoo', 'organic'],
            'facebook.' => ['facebook', 'social'],
            'fb.' => ['facebook', 'social'],
            'l.facebook.' => ['facebook', 'social'],
            'instagram.' => ['instagram', 'social'],
            'threads.' => ['threads', 'social'],
            't.co' => ['x-twitter', 'social'],
            'twitter.' => ['x-twitter', 'social'],
            'x.com' => ['x-twitter', 'social'],
            'pinterest.' => ['pinterest', 'social'],
            'youtube.' => ['youtube', 'social'],
            'linkedin.' => ['linkedin', 'social'],
        ];

        foreach ($map as $needle => $values) {
            if (strpos($refHost, $needle) !== false) {
                return ['source' => $values[0], 'medium' => $values[1], 'campaign' => ''];
            }
        }

        return ['source' => $refHost, 'medium' => 'referral', 'campaign' => ''];
    }

    private function detectDevice($ua)
    {
        $ua = strtolower($ua);
        if (strpos($ua, 'tablet') !== false || strpos($ua, 'ipad') !== false) {
            return 'tablet';
        }
        if (strpos($ua, 'mobile') !== false || strpos($ua, 'iphone') !== false || strpos($ua, 'android') !== false) {
            return 'mobile';
        }
        return 'desktop';
    }

    public function hasAnalyticsConsent()
    {
        foreach ($this->getConsentCookieNames() as $cookieName) {
            if (!isset($_COOKIE[$cookieName])) {
                continue;
            }
            $value = strtolower((string) $_COOKIE[$cookieName]);
            if ($value === '1' || $value === 'true' || $value === 'yes' || $value === 'accepted' || $value === 'allowed' || $value === 'granted') {
                return true;
            }
            if (strpos($value, 'analytics') !== false && (strpos($value, 'true') !== false || strpos($value, '1') !== false || strpos($value, 'granted') !== false || strpos($value, 'accepted') !== false)) {
                return true;
            }
            if (strpos($value, 'all') !== false || strpos($value, 'accept') !== false || strpos($value, 'grant') !== false) {
                return true;
            }
        }

        return false;
    }

    private function getConsentCookieNames()
    {
        $raw = (string) Configuration::get('CIVJ_CONSENT_COOKIES');
        $names = [];
        foreach (explode(',', $raw) as $name) {
            $name = trim($name);
            if ($name !== '') {
                $names[] = $name;
            }
        }
        return array_values(array_unique($names));
    }

    private function hasDoNotTrack()
    {
        return (isset($_SERVER['HTTP_DNT']) && $_SERVER['HTTP_DNT'] === '1')
            || (isset($_SERVER['HTTP_SEC_GPC']) && $_SERVER['HTTP_SEC_GPC'] === '1');
    }

    private function isBotRequest()
    {
        $ua = strtolower(isset($_SERVER['HTTP_USER_AGENT']) ? (string) $_SERVER['HTTP_USER_AGENT'] : '');
        if ($ua === '') {
            return false;
        }

        $needles = [
            'bot', 'crawl', 'spider', 'slurp', 'bingpreview', 'facebookexternalhit',
            'whatsapp', 'telegrambot', 'preview', 'headlesschrome', 'lighthouse', 'pagespeed',
        ];

        foreach ($needles as $needle) {
            if (strpos($ua, $needle) !== false) {
                return true;
            }
        }

        return false;
    }

    private function isShopUrl($url)
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        if (!$host) {
            return false;
        }
        $shopHost = strtolower((string) parse_url($this->context->shop->getBaseURL(true, true), PHP_URL_HOST));
        if (!$shopHost) {
            return false;
        }
        return $host === $shopHost || Tools::substr($host, -Tools::strlen('.' . $shopHost)) === '.' . $shopHost;
    }

    private function purgeOldData($days, $force)
    {
        $days = max(1, (int) $days);
        Db::getInstance()->execute('DELETE FROM `' . _DB_PREFIX_ . 'civj_session` WHERE `date_add` < DATE_SUB(NOW(), INTERVAL ' . (int) $days . ' DAY)');
        return true;
    }

    private function sanitizeKey($value)
    {
        $value = preg_replace('/[^a-zA-Z0-9_\-]/', '', (string) $value);
        return Tools::substr($value, 0, 64);
    }

    private function sanitizeText($value, $maxLength)
    {
        $value = trim((string) $value);
        $value = str_replace(["\0", "\r"], '', $value);
        return Tools::substr($value, 0, (int) $maxLength);
    }

    private function shortUrl($url)
    {
        $url = (string) $url;
        if ($url === '') {
            return '-';
        }
        $display = $url;
        $host = parse_url($url, PHP_URL_HOST);
        $path = parse_url($url, PHP_URL_PATH);
        $query = parse_url($url, PHP_URL_QUERY);
        if ($host) {
            $display = $host . ($path ?: '/');
            if ($query) {
                $display .= '?' . $query;
            }
        }
        if (Tools::strlen($display) > 120) {
            $display = Tools::substr($display, 0, 117) . '...';
        }
        return '<span title="' . $this->escapeHtml($url) . '">' . $this->escapeHtml($display) . '</span>';
    }

    private function escapeHtml($value)
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }

    private function escapeJs($value)
    {
        return str_replace(["\\", "'", "\n", "\r"], ["\\\\", "\\'", " ", " "], (string) $value);
    }
}
