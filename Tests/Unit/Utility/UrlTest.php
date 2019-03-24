<?php

namespace Plan2net\Sierrha\Tests\Error;

use Plan2net\Sierrha\Utility\Url;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Controller\ErrorPageController;

/**
 * @backupGlobals enabled
 */
class UrlTest extends UnitTestCase
{

    const ERROR_PAGE_CONTROLLER_OUTPUT = 'FOOBAR';

    /**
     * System Under Test
     *
     * @var Url
     */
    protected $sut;

    protected function setUp()
    {
        $this->sut = new Url();

        $languageServiceStub = $this->createMock(LanguageService::class);
        $languageServiceStub->method('sL')->willReturn('lorem ipsum');
        $GLOBALS['LANG'] = $languageServiceStub;
    }

    protected function setupErrorPageControllerStub()
    {
        $errorPageControllerStub = $this->getMockBuilder(ErrorPageController::class)
                                        ->disableOriginalConstructor()
                                        ->getMock();
        $errorPageControllerStub->method('errorAction')
                                ->willReturn(self::ERROR_PAGE_CONTROLLER_OUTPUT);
        GeneralUtility::addInstance(ErrorPageController::class, $errorPageControllerStub);
    }

    /**
     * @test
     * @backupGlobals enabled
     */
    public function internalServerErrorlOnFetchingUrlIsDetected()
    {
        $requestHandlerStub = $this->getMockBuilder(RequestFactory::class)
                                   ->getMock();
        $requestHandlerStub->method('request')
                           ->willReturn(new Response('php://memory', 500));
        GeneralUtility::addInstance(RequestFactory::class, $requestHandlerStub);
        $this->setupErrorPageControllerStub();

        $result = $this->sut->fetchWithFallback('http://foo.bar/', '', '');
        $this->assertEquals(self::ERROR_PAGE_CONTROLLER_OUTPUT, $result);
    }

    /**
     * @test
     * @backupGlobals enabled
     */
    public function emptyContentOfFetchedUrlIsDetected()
    {
        $requestHandlerStub = $this->getMockBuilder(RequestFactory::class)
                                   ->getMock();
        $requestHandlerStub->method('request')
                           ->willReturn(new Response()); // will return an empty string
        GeneralUtility::addInstance(RequestFactory::class, $requestHandlerStub);
        $this->setupErrorPageControllerStub();

        $result = $this->sut->fetchWithFallback('http://foo.bar/', '', '');
        $this->assertEquals(self::ERROR_PAGE_CONTROLLER_OUTPUT, $result);
    }

    /**
     * @test
     * @backupGlobals enabled
     */
    public function unusableContentOfFetchedUrlIsDetected()
    {
        $responseBody = fopen('php://memory', 'r+');
        fputs($responseBody, ' <h1> </h1> ');
        rewind($responseBody);
        $requestHandlerStub = $this->getMockBuilder(RequestFactory::class)
                                   ->getMock();
        $requestHandlerStub->method('request')
                           ->willReturn(new Response($responseBody));
        GeneralUtility::addInstance(RequestFactory::class, $requestHandlerStub);
        $this->setupErrorPageControllerStub();

        $result = $this->sut->fetchWithFallback('http://foo.bar/', '', '');
        $this->assertEquals(self::ERROR_PAGE_CONTROLLER_OUTPUT, $result);
    }

    /**
     * @test
     * @backupGlobals enabled
     */
    public function usableContentOfFetchedUrlIsReturned()
    {
        $errorPageContent = 'LOREM IPSUM';

        $responseBody = fopen('php://memory', 'r+');
        fputs($responseBody, $errorPageContent);
        rewind($responseBody);
        $requestHandlerStub = $this->getMockBuilder(RequestFactory::class)
                                   ->getMock();
        $requestHandlerStub->method('request')
                           ->willReturn(new Response($responseBody));
        GeneralUtility::addInstance(RequestFactory::class, $requestHandlerStub);

        $result = $this->sut->fetchWithFallback('http://foo.bar/', '', '');
        $this->assertEquals($errorPageContent, $result);
    }

}
