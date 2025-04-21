<?php

namespace ImageServe\Controllers;

use ImageServe\Controllers\Middleware\RateLimiter;
use Intervention\Image\Drivers\Imagick\Driver;
use Intervention\Image\EncodedImage;
use Intervention\Image\ImageManager;
use Intervention\Image\MediaType;

class App
{
    public static function processMiddleware(callable $callback): void
    {
        RateLimiter::attempt();

        $callback();
    }

    /**
     * Load an asset based on the provided segment and options.
     *
     * @param string $segment The segment to load the asset from.
     * @param array<mixed> $options The options to pass to the asset loader.
     * @return void
     */
    public static function loadAsset(string $segment, array $options): void
    {
        $asset_location = self::getAssetLocation($segment);

        if (!file_exists($asset_location)) {
            http_response_code(404);
            echo "Asset not found";
            die();
        }

        // create new image instance
        $image = ImageManager::imagick()->read($asset_location);

        // resize only image height to 200 pixel
        $image->scaleDown(width: $options["width"] ?? 200);

        // Encode the image based on the provided options
        $encoded = $image->encodeByMediaType(quality: $options["quality"] ?? 90);

        // Render the image to the client
        if (!$encoded instanceof EncodedImage) {
            http_response_code(500);
            echo "Image not loaded";
            die();
        }

        self::renderImage($encoded);
    }

    /**
     * Get the location of an asset based on the provided segment.
     *
     * @param string $asset The asset to get the location for.
     * @return string The location of the asset.
     */
    public static function getAssetLocation(string $asset): string
    {
        // Get the server root path
        $serverRoot = dirname(__DIR__, 2) . "/" . $_ENV["ASSETS_PATH"];

        // Ensure the asset path is relative to the server root
        $assetPath = rtrim($serverRoot, "/") . "/" . ltrim($asset, "/");

        return $assetPath;
    }

    /**
     * Render the image to the client.
     *
     * @param EncodedImage $encoded The image to render.
     * @return never
     */
    private static function renderImage(EncodedImage $encoded): never
    {
        // Set appropriate headers for the image
        $expires_sec = strtotime("+3 month");
        $expires = gmdate("D, d M Y H:i:s \G\M\T", $expires_sec);

        header("Content-Type: " . $encoded->mediaType());
        header("Content-Length: " . strlen($encoded));
        header("Cache-Control: public, max-age=$expires_sec");
        header("Expires: " . $expires);

        echo $encoded;
        exit();
    }
}
