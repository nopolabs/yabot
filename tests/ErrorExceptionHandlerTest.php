<?php
namespace Nopolabs\Yabot\Tests;

use ErrorException;
use Nopolabs\Yabot\ErrorExceptionHandler;
use PHPUnit\Framework\TestCase;

class ErrorExceptionHandlerTest extends TestCase
{
    public function testHandler()
    {
        try {
            call_user_func([ErrorExceptionHandler::class, 'handler'], E_USER_ERROR, 'testing', __FILE__, __LINE__);
            $this->fail('Expected ErrorException');
        } catch (ErrorException $error) {
            $this->assertEquals('testing', $error->getMessage());
            $this->assertEquals(__FILE__, $error->getFile());
            $this->assertEquals(__LINE__ - 5, $error->getLine());
            $this->assertEquals(E_USER_ERROR, $error->getSeverity());
        }
    }
}