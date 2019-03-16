<?php
declare(strict_types=1);

namespace Plan2net\Sierrha\Error;

/*
 * Copyright 2019 plan2net GmbH
 * 
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Error\PageErrorHandler\PageErrorHandlerInterface;
use TYPO3\CMS\Core\LinkHandling\LinkService;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Site\Entity\Site;

/**
 * A foundation class for error handlers.
 */
abstract class BaseHandler implements PageErrorHandlerInterface {

	/**
	 * @var int
	 */
	protected $statusCode;

	/**
	 * @var array
	 */
	protected $handlerConfiguration;

	/**
	 * @param int $statusCode
	 * @param array $configuration
	 */
	public function __construct(int $statusCode, array $configuration) {
		$this->statusCode = $statusCode;
		$this->handlerConfiguration = $configuration;
	}

    /**
     * Resolve TYPO3 style URL into real world URL, replace language markers for external URL
     *
     * @param ServerRequestInterface $request
     * @param string                 $typoLinkUrl
     * @return string
     * @throws \TYPO3\CMS\Core\Routing\InvalidRouteArgumentsException
     */
    protected function resolveUrl(ServerRequestInterface $request, string $typoLinkUrl): string
    {
        $linkService = GeneralUtility::makeInstance(LinkService::class);
        $urlParams = $linkService->resolve($typoLinkUrl);
        if ($urlParams['type'] !== 'page' && $urlParams['type'] !== 'url') {
            throw new \InvalidArgumentException('The error handler accepts only TYPO3 links of type "page" or "url"', 1547651754);
        }
        if ($urlParams['type'] === 'url') {
            /* @var $siteLanguage SiteLanguage */
            $siteLanguage = $request->getAttribute('language');
            $resolvedUrl = str_replace(
                ['###ISO_639-1###', '###IETF_BCP47'],
                [$siteLanguage->getTwoLetterIsoCode(), $siteLanguage->getHreflang()],
                $urlParams['url']
            );

            return $resolvedUrl;
        }

        /* @var $site Site */
        $site = $request->getAttribute('site', null);
        if (!$site instanceof Site) {
            $site = GeneralUtility::makeInstance(SiteFinder::class)->getSiteByPageId((int)$urlParams['pageuid']);
        }

        return (string)$site->getRouter()->generateUri(
            (int)$urlParams['pageuid'],
            ['_language' => $request->getAttribute('language', null)]
        );
    }

    /**
     * Fetch content of URL
     * 
     * @param string $url
     * @return string
     */
    protected function fetchUrl(string $url, string $labelTitle, string $labelDetails): string
    {
        $content = GeneralUtility::getUrl($url);
        if ($content === false) {
            // @todo add error logging
            $content = '';
        } elseif (trim(strip_tags($content)) === '') {
            // an empty message is considered an error 
            // @todo add error logging
            $content = '';
        }

        if ($content === '') {
            $languageService = $this->getLanguageService();
            $content = GeneralUtility::makeInstance(ErrorPageController::class)->errorAction(
                $languageService->sL('LLL:EXT:sierrha/Resources/Private/Language/locallang.xlf:' . $labelTitle),
                $languageService->sL('LLL:EXT:sierrha/Resources/Private/Language/locallang.xlf:' . $labelDetails)
            );
        }

        return $content;
    }

    /**
     * @return LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}
