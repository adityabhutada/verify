<?php
// prevent script execution when included
if (!defined('TESTING')) {
    define('TESTING', true);
}
require_once __DIR__ . '/../webhook.php';

use PHPUnit\Framework\TestCase;

class WebhookTest extends TestCase
{
    public function testInvalidJsonReturns400()
    {
        [$status, $response] = handleWebhook('{bad json');
        $this->assertEquals(400, $status);
        $this->assertIsArray($response);
        $this->assertArrayHasKey('error', $response);
    }
}
