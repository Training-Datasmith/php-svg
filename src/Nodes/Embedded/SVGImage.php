<?php

declare (strict_types=1);
namespace SVG\Nodes\Embedded;

use RuntimeException;
use SVG\Nodes\Svg_Node_Container;
use SVG\Rasterization\Svg_Rasterizer;
use SVG\Rasterization\Transform\Transform_Parser;
use SVG\Utilities\Units\Length;
/**
 * Represents the SVG tag 'image'.
 * Has the special attributes xlink:href, x, y, width, height.
 */
class Svg_Image extends Svg_Node_Container
{
    public const TAG_NAME = 'image';
    /**
     * @param string|null $href   The image path, URL or URI.
     * @param mixed $x      The x coordinate of the upper left corner.
     * @param mixed $y      The y coordinate of the upper left corner.
     * @param mixed $width  The width.
     * @param mixed $height The height.
     */
    public function __construct(?string $href = null, $x = null, $y = null, $width = null, $height = null)
    {
        parent::__construct();
        $this->set_attribute('xlink:href', $href);
        $this->set_attribute('x', $x);
        $this->set_attribute('y', $y);
        $this->set_attribute('width', $width);
        $this->set_attribute('height', $height);
    }
    /**
     * Creates a new SVGImage directly from file
     *
     * @param mixed $x
     * @param mixed $y
     * @param mixed $width
     * @param mixed $height
     *
     */
    public static function from_file(string $path, string $mime_type, $x = null, $y = null, $width = null, $height = null): Svg_Image
    {
        $image_content = file_get_contents($path);
        if ($image_content === false) {
            throw new RuntimeException('Image file "' . $path . '" could not be read.');
        }
        return self::from_string($image_content, $mime_type, $x, $y, $width, $height);
    }
    /**
     * Creates a new SVGImage directly from a raw binary image string
     *
     * @param mixed $x
     * @param mixed $y
     * @param mixed $width
     * @param mixed $height
     *
     */
    public static function from_string(string $image_content, string $mime_type, $x = null, $y = null, $width = null, $height = null): Svg_Image
    {
        return new self(sprintf('data:%s;base64,%s', $mime_type, base64_encode($image_content)), $x, $y, $width, $height);
    }
    /**
     * @return string|null The image path, URL or URI.
     */
    public function get_href(): ?string
    {
        return $this->get_attribute('xlink:href') ?: $this->get_attribute('href');
    }
    /**
     * Sets this image's path, URL or URI.
     *
     * @param string|null $href The new image hyper reference.
     *
     * @return $this This node instance, for call chaining.
     */
    public function set_href(?string $href): Svg_Image
    {
        return $this->set_attribute('xlink:href', $href);
    }
    /**
     * @return string|null The x coordinate of the upper left corner.
     */
    public function get_x(): ?string
    {
        return $this->get_attribute('x');
    }
    /**
     * Sets the x coordinate of the upper left corner.
     *
     * @param mixed $x The new coordinate.
     *
     * @return $this This node instance, for call chaining.
     */
    public function set_x($x): Svg_Image
    {
        return $this->set_attribute('x', $x);
    }
    /**
     * @return string|null The y coordinate of the upper left corner.
     */
    public function get_y(): ?string
    {
        return $this->get_attribute('y');
    }
    /**
     * Sets the y coordinate of the upper left corner.
     *
     * @param mixed $y The new coordinate.
     *
     * @return $this This node instance, for call chaining.
     */
    public function set_y($y): Svg_Image
    {
        return $this->set_attribute('y', $y);
    }
    /**
     * @return string|null The width.
     */
    public function get_width(): ?string
    {
        return $this->get_attribute('width');
    }
    /**
     * @param mixed $width The new width.
     *
     * @return $this This node instance, for call chaining.
     */
    public function set_width($width): Svg_Image
    {
        return $this->set_attribute('width', $width);
    }
    /**
     * @return string|null The height.
     */
    public function get_height(): ?string
    {
        return $this->get_attribute('height');
    }
    /**
     * @param mixed $height The new height.
     *
     * @return $this This node instance, for call chaining.
     */
    public function set_height($height): Svg_Image
    {
        return $this->set_attribute('height', $height);
    }
    /**
     * @inheritdoc
     */
    public function rasterize(Svg_Rasterizer $rasterizer): void
    {
        if ($this->get_computed_style('display') === 'none') {
            return;
        }
        $visibility = $this->get_computed_style('visibility');
        if ($visibility === 'hidden' || $visibility === 'collapse') {
            return;
        }
        Transform_Parser::parse_transform_string($this->get_attribute('transform'), $rasterizer->push_transform());
        $rasterizer->render('image', ['href' => $this->get_href(), 'x' => Length::convert($this->get_x(), $rasterizer->get_document_width()), 'y' => Length::convert($this->get_y(), $rasterizer->get_document_height()), 'width' => Length::convert($this->get_width(), $rasterizer->get_document_width()), 'height' => Length::convert($this->get_height(), $rasterizer->get_document_height())], $this);
        $rasterizer->pop_transform();
    }
}