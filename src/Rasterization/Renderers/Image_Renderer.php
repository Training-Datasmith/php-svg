<?php

declare (strict_types=1);
namespace SVG\Rasterization\Renderers;

use SVG\Nodes\Svg_Node;
use SVG\Rasterization\Svg_Rasterizer;
use SVG\SVG;
/**
 * This renderer can draw referenced images (from <image> tags).
 *
 * Options:
 * - string href: the image URI
 * - float x: the x coordinate of the upper left corner
 * - float y: the y coordinate of the upper left corner
 * - float width: the width
 * - float height: the height
 */
class Image_Renderer extends Renderer
{
    /**
     * @inheritdoc
     */
    public function render(Svg_Rasterizer $rasterizer, array $options, Svg_Node $context): void
    {
        $transform = $rasterizer->get_current_transform();
        $x = $options['x'] ?? 0;
        $y = $options['y'] ?? 0;
        $transform->map($x, $y);
        // TODO support "auto" values for width and height
        $width = $options['width'] ?? 0;
        $height = $options['height'] ?? 0;
        if ($width <= 0 || $height <= 0) {
            return;
        }
        $transform->resize($width, $height);
        $image = $rasterizer->get_image();
        $img = $this->load_image($options['href'], $width, $height);
        if (!empty($img) && (is_resource($img) || $img instanceof \Gd_Image)) {
            imagecopyresampled(
                $image,
                // dst
                $img,
                // src
                $x,
                // dst_x
                $y,
                // dst_y
                0,
                // src_x
                0,
                // src_y
                $width,
                // dst_w
                $height,
                // dst_h
                imagesx($img),
                // src_w
                imagesy($img)
            );
        }
    }
    /**
     * Loads the image locatable via the given HREF and creates a GD resource
     * for it.
     *
     * This method supports data URIs, as well as SVG files (they are rasterized
     * through this very library). As such, the dimensions given are the
     * dimensions the rasterized SVG would have.
     *
     * @param string $href The image URI.
     * @param int    $w    The width that the rasterized image should have.
     * @param int    $h    The height that the rasterized image should have.
     *
     * @return resource The loaded image.
     */
    private function load_image(string $href, int $w, int $h)
    {
        $content = $this->load_image_content($href);
        if (strpos($content, '<svg') !== false && strrpos($content, '</svg>') !== false) {
            $svg = SVG::from_string($content);
            return $svg->to_raster_image($w, $h);
        }
        return imagecreatefromstring($content);
    }
    /**
     * Loads the data of an image locatable via the given HREF into a string.
     *
     * @param string $href The image URI.
     *
     * @return string The image content.
     */
    private function load_image_content(string $href): string
    {
        $data_prefix = 'data:';
        // check if $href is data URI
        if (substr($href, 0, strlen($data_prefix)) === $data_prefix) {
            $comma_pos = strpos($href, ',');
            $metadata = substr($href, 0, $comma_pos);
            $content = substr($href, $comma_pos + 1);
            if (strpos($metadata, ';base64') !== false) {
                return base64_decode($content);
            }
            return $content;
        }
        // Only allow http and https schemes to prevent SSRF via file://, phar://, php://, etc.
        $scheme = strtolower((string) parse_url($href, PHP_URL_SCHEME));
        if ($scheme !== 'http' && $scheme !== 'https') {
            return '';
        }
        return file_get_contents($href);
    }
}