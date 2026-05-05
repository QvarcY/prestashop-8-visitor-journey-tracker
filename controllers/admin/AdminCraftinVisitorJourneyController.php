<?php
/**
 * Back office statistics controller for CraftIN Visitor Journey.
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class AdminCraftinVisitorJourneyController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        parent::__construct();
    }

    public function initContent()
    {
        parent::initContent();

        if (!$this->module || !is_object($this->module) || !method_exists($this->module, 'renderAdminStatisticsPage')) {
            $this->content .= $this->displayError($this->trans('Module is not available.', [], 'Admin.Notifications.Error'));
        } else {
            $baseUrl = $this->context->link->getAdminLink('AdminCraftinVisitorJourney', true);
            $this->content .= $this->module->renderAdminStatisticsPage($baseUrl);
        }

        $this->context->smarty->assign([
            'content' => $this->content,
        ]);
    }
}
