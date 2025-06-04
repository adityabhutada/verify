<?php
// ensure the script doesn't execute when included
define('TESTING', true);

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../submit.php';

use PHPUnit\Framework\TestCase;

class SubmitTest extends TestCase
{
    public function testValidateValid()
    {
        $data = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'phone' => '1234567890',
            'street' => 'Main St',
            'city' => 'NY',
            'state' => 'NY',
            'zip' => '12345',
            'dob' => '1990-01-01',
            'terms' => 'on'
        ];
        $this->assertTrue(validate($data));
    }

    public function testValidateMissingField()
    {
        $data = [
            'first_name' => '',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'phone' => '1234567890',
            'street' => 'Main St',
            'city' => 'NY',
            'state' => 'NY',
            'zip' => '12345',
            'dob' => '1990-01-01',
            'terms' => 'on'
        ];
        $this->assertFalse(validate($data));
    }

    public function testValidateTermsNotChecked()
    {
        $data = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'phone' => '1234567890',
            'street' => 'Main St',
            'city' => 'NY',
            'state' => 'NY',
            'zip' => '12345',
            'dob' => '1990-01-01'
        ];
        $this->assertFalse(validate($data));
    }

    public function testAuthorizationHeader()
    {
        stream_wrapper_unregister('https');
        stream_wrapper_register('https', MockStream::class);

        $data = ['foo' => 'bar'];
        send_api_request($data);

        stream_wrapper_restore('https');

        $expected = 'Authorization: Bearer ' . API_KEY;
        $this->assertStringContainsString($expected, MockStream::$lastHeaders);
    }
}

class MockStream
{
    public $context;
    public static $lastHeaders;

    private $pos = 0;
    private $data = '{"error":false}';

    public function stream_open($path, $mode, $options, &$opened_path)
    {
        $opts = stream_context_get_options($this->context);
        self::$lastHeaders = $opts['http']['header'] ?? '';
        return true;
    }

    public function stream_read($count)
    {
        $chunk = substr($this->data, $this->pos, $count);
        $this->pos += strlen($chunk);
        return $chunk;
    }

    public function stream_eof()
    {
        return $this->pos >= strlen($this->data);
    }

    public function stream_stat()
    {
        return [];
    }
}
