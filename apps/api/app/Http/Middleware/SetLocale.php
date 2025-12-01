<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to set the application locale based on the Accept-Language header.
 *
 * Priority order:
 * 1. X-Language header (explicit override)
 * 2. Accept-Language header (browser preference)
 * 3. Default locale from config
 */
final class SetLocale
{
    /**
     * Supported locales.
     *
     * @var array<string>
     */
    private const SUPPORTED_LOCALES = ['en', 'fr', 'ar'];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->determineLocale($request);
        App::setLocale($locale);

        /** @var Response $response */
        $response = $next($request);

        // Add Content-Language header to response
        $response->headers->set('Content-Language', $locale);

        return $response;
    }

    /**
     * Determine the locale from the request.
     */
    private function determineLocale(Request $request): string
    {
        // Priority 1: X-Language header (explicit override)
        $explicitLocale = $request->header('X-Language');
        if ($explicitLocale !== null && $this->isSupported($explicitLocale)) {
            return $explicitLocale;
        }

        // Priority 2: Accept-Language header
        $acceptLanguage = $request->header('Accept-Language');
        if ($acceptLanguage !== null) {
            $locale = $this->parseAcceptLanguage($acceptLanguage);
            if ($locale !== null) {
                return $locale;
            }
        }

        // Priority 3: Default locale
        return config('app.locale', 'en');
    }

    /**
     * Parse the Accept-Language header and return the best matching locale.
     */
    private function parseAcceptLanguage(string $acceptLanguage): ?string
    {
        // Parse the Accept-Language header
        // Example: "fr-FR,fr;q=0.9,en-US;q=0.8,en;q=0.7"
        $languages = [];
        $parts = explode(',', $acceptLanguage);

        foreach ($parts as $part) {
            $part = trim($part);
            if ($part === '') {
                continue;
            }

            // Split by semicolon to get quality value
            $langParts = explode(';', $part);
            $lang = trim($langParts[0]);

            // Extract just the language code (before the hyphen)
            $langCode = strtolower(explode('-', $lang)[0]);

            // Get quality value (default 1.0)
            $quality = 1.0;
            if (isset($langParts[1])) {
                $qPart = trim($langParts[1]);
                if (str_starts_with($qPart, 'q=')) {
                    $quality = (float) substr($qPart, 2);
                }
            }

            if ($this->isSupported($langCode)) {
                $languages[$langCode] = $quality;
            }
        }

        // Sort by quality (highest first)
        arsort($languages);

        // Return the first (highest quality) supported language
        $sorted = array_keys($languages);

        return count($sorted) > 0 ? $sorted[0] : null;
    }

    /**
     * Check if a locale is supported.
     */
    private function isSupported(string $locale): bool
    {
        return in_array(strtolower($locale), self::SUPPORTED_LOCALES, true);
    }
}
