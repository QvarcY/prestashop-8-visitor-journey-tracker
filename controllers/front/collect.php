<?php
/**
 * Front controller that receives visitor journey pageview events.
 */

class CraftinvisitorjourneyCollectModuleFrontController extends ModuleFrontController
{
    public $ajax = true;
    public $ssl = true;
    public $display_header = false;
    public $display_footer = false;

    public function initContent()
    {
        parent::initContent();
        $this->processCollect();
    }

    public function postProcess()
    {
        $this->processCollect();
    }

    private function processCollect()
    {
        if (ob_get_length()) {
            @ob_clean();
        }

        header('Content-Type: application/json; charset=utf-8');
        header('X-Robots-Tag: noindex, nofollow', true);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            die(json_encode(['success' => false, 'error' => 'method_not_allowed']));
        }

        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);

        if (!is_array($data)) {
            http_response_code(400);
            die(json_encode(['success' => false, 'error' => 'bad_json']));
        }

        try {
            $ok = $this->module->recordPageView($data);
            die(json_encode(['success' => (bool) $ok]));
        } catch (Exception $e) {
            http_response_code(500);
            die(json_encode(['success' => false, 'error' => 'server_error']));
        }
    }
}
