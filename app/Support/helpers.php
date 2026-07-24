<?php

if (!function_exists('media')) {
    /**
     * Build a URL for a media asset, preferring the CDN when configured.
     */
    function media(string $path = ''): string
    {
        return rtrim(env('MEDIA_CDN_URL', asset('storage')), '/') . '/' . ltrim($path, '/');
    }
}

if (!function_exists('lottie')) {
    /**
     * Render a lottie-player tag for an icon hosted on the media CDN.
     */
    function lottie(string $icon, string $cls = ''): string
    {
        return '<lottie-player src="' . media('icons/' . $icon . '.json') . '" ' . $cls . ' autoplay loop mode="normal"></lottie-player>';
    }
}
