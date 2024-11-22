<?php

namespace XRayLog\Tests;

use PHPUnit\Framework\TestCase;
use XRayLog\XRayLogger;
use XRayLog\Tests\MockServer;

class XRayLoggerTest extends TestCase
{
    private $mockServer;
    private $logger;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockServer = new MockServer();
        $this->logger = new XRayLogger('test-project', $this->mockServer->getClient());
    }

    public function testLogSendsCorrectRequest()
    {
        $this->mockServer->addResponse(200, [], json_encode(['status' => 'success']));

        $data = ['test' => 'data'];
        $this->logger->log('info', $data);

        $request = $this->mockServer->getLastRequest();
        $this->assertNotNull($request);
        
        $this->assertEquals('POST', $request->getMethod());
        $this->assertEquals(XRayLogger::API_URL . '/receive', (string) $request->getUri());

        $requestBody = json_decode($request->getBody()->getContents(), true);
        $this->assertArrayHasKey('project', $requestBody);
        $this->assertEquals('test-project', $requestBody['project']);
        $this->assertArrayHasKey('level', $requestBody);
        $this->assertEquals('INFO', $requestBody['level']);
    }

    public function testInfoLogging()
    {
        $this->mockServer->addResponse(200, [], json_encode(['status' => 'success']));
        
        $data = ['message' => 'info test'];
        $this->logger->info($data);
        
        $request = $this->mockServer->getLastRequest();
        $requestBody = json_decode($request->getBody()->getContents(), true);
        
        $this->assertEquals('INFO', $requestBody['level']);
    }

    public function testErrorLogging()
    {
        $this->mockServer->addResponse(200, [], json_encode(['status' => 'success']));
        
        $data = ['message' => 'error test'];
        $this->logger->error($data);
        
        $request = $this->mockServer->getLastRequest();
        $requestBody = json_decode($request->getBody()->getContents(), true);
        
        $this->assertEquals('ERROR', $requestBody['level']);
    }

    public function testWarningLogging()
    {
        $this->mockServer->addResponse(200, [], json_encode(['status' => 'success']));
        
        $data = ['message' => 'warning test'];
        $this->logger->warning($data);
        
        $request = $this->mockServer->getLastRequest();
        $requestBody = json_decode($request->getBody()->getContents(), true);
        
        $this->assertEquals('WARNING', $requestBody['level']);
    }

    public function testDebugLogging()
    {
        $this->mockServer->addResponse(200, [], json_encode(['status' => 'success']));
        
        $data = ['message' => 'debug test'];
        $this->logger->debug($data);
        
        $request = $this->mockServer->getLastRequest();
        $requestBody = json_decode($request->getBody()->getContents(), true);
        
        $this->assertEquals('DEBUG', $requestBody['level']);
    }

    public function testLogHandlesErrorResponse()
    {
        $this->mockServer->addResponse(500, [], json_encode(['error' => 'Server Error']));
        $this->expectException(\RuntimeException::class);
        $this->logger->log('error', ['test' => 'error']);
    }

    public function testSetProject()
    {
        $newProjectName = 'new-project';
        $this->logger->setProject($newProjectName);

        $this->mockServer->addResponse(200, [], json_encode(['status' => 'success']));
        $this->logger->log('info', ['test' => 'project']);

        $request = $this->mockServer->getLastRequest();
        $requestBody = json_decode($request->getBody()->getContents(), true);
        $this->assertEquals($newProjectName, $requestBody['project']);
    }
}
