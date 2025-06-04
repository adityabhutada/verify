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
            'phone' => '+11234567890',
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
            'phone' => '+11234567890',
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
            'phone' => '+11234567890',
            'street' => 'Main St',
            'city' => 'NY',
            'state' => 'NY',
            'zip' => '12345',
            'dob' => '1990-01-01'
        ];
        $this->assertFalse(validate($data));
    }

    public function testValidateInvalidEmail()
    {
        $data = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'not-an-email',
            'phone' => '+11234567890',
            'street' => 'Main St',
            'city' => 'NY',
            'state' => 'NY',
            'zip' => '12345',
            'dob' => '1990-01-01',
            'terms' => 'on'
        ];
        $this->assertFalse(validate($data));
    }

    public function testValidateInvalidPhone()
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
        $this->assertFalse(validate($data));
    }
}
