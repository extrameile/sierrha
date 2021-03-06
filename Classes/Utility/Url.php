<?php
declare(strict_types=1);

namespace Plan2net\Sierrha\Utility;

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

use TYPO3\CMS\Core\Controller\ErrorPageController;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * A utility for URL handling.
 */
class Url
{

    /**
     * Fetches content of URL, returns fallback on error
     *
     * @param string $url
     * @param string $fallbackLabelTitle
     * @param string $fallbackLabelDetails
     * @return string
     */
    public function fetchWithFallback(string $url, string $fallbackLabelTitle, string $fallbackLabelDetails): string
    {
        $content = $this->fetch($url);
        if (trim(strip_tags($content)) === '') {
            // an empty message is considered an error
            // @todo add error logging
            $content = '';
        }

        if ($content === '') {
            $languageService = $this->getLanguageService();
            $content = GeneralUtility::makeInstance(ErrorPageController::class)->errorAction(
                $languageService->sL('LLL:EXT:sierrha/Resources/Private/Language/locallang.xlf:'.$fallbackLabelTitle),
                $languageService->sL('LLL:EXT:sierrha/Resources/Private/Language/locallang.xlf:'.$fallbackLabelDetails)
            );
        }

        return $content;
    }

    /**
     * @param string $url
     * @return string
     */
    protected function fetch(string $url): string
    {
        $content = '';
        $requestFactory = GeneralUtility::makeInstance(RequestFactory::class);
        try {
            $response = $requestFactory->request($url);
            if ($response->getStatusCode() === 200) {
                $content = $requestFactory->request($url)->getBody()->getContents();
            } else {
                // @todo add error logging
            }
        } catch (\Exception $e) {
            // @todo add error logging
        }

        return $content;
    }

    /**
     * @return LanguageService
     */
    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'] ?? GeneralUtility::makeInstance(LanguageService::class);
    }
}
