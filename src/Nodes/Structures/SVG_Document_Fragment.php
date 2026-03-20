<?php

declare (strict_types=1);
namespace SVG\Nodes\Structures;

use SVG\Nodes\Svg_Node;
use SVG\Nodes\Svg_Node_Container;
use SVG\Rasterization\Svg_Rasterizer;
use SVG\Utilities\Units\Length;
/**
 * Represents the SVG tag 'svg'. This is the root node for every image.
 * Has the special attributes x, y, width, height.
 */
class Svg_Document_Fragment extends Svg_Node_Container
{
    public const TAG_NAME = 'svg';
    /**
     * @var array $initialStyles A map of style keys to their defaults.
     */
    private static array $initial_styles = ['fill' => '#000000', 'stroke' => 'none', 'stroke-width' => '1', 'opacity' => '1', 'font-size' => '16px'];
    /**
     * @param mixed $width  The declared width.
     * @param mixed $height The declared height.
     */
    public function __construct($width = null, $height = null)
    {
        parent::__construct();
        $this->set_attribute('width', $width);
        $this->set_attribute('height', $height);
    }
    /**
     * @return bool Whether this is the root document.
     */
    public function is_root(): bool
    {
        return $this->get_parent() === null;
    }
    /**
     * @return string|null The declared width of this document or null.
     */
    public function get_width(): ?string
    {
        return $this->get_attribute('width');
    }
    /**
     * Declares a new width for this document.
     *
     * @param mixed $width The new width.
     *
     * @return $this This node instance, for call chaining.
     */
    public function set_width($width): Svg_Document_Fragment
    {
        return $this->set_attribute('width', $width);
    }
    /**
     * @return string|null The declared height of this document or null.
     */
    public function get_height(): ?string
    {
        return $this->get_attribute('height');
    }
    /**
     * Declares a new height for this document.
     *
     * @param mixed $height The new height.
     *
     * @return $this This node instance, for call chaining.
     */
    public function set_height($height): Svg_Document_Fragment
    {
        return $this->set_attribute('height', $height);
    }
    /**
     * @inheritdoc
     */
    public function get_computed_style(string $name): ?string
    {
        // return either explicit declarations ...
        $style = parent::get_computed_style($name);
        if (isset($style) || !isset(self::$initial_styles[$name])) {
            return $style;
        }
        // ... or the default one.
        return self::$initial_styles[$name];
    }
    /**
     * @inheritdoc
     */
    public function rasterize(Svg_Rasterizer $rasterizer): void
    {
        if ($this->is_root()) {
            parent::rasterize($rasterizer);
            return;
        }
        // create new rasterizer for nested viewport
        $sub_rasterizer = new Svg_Rasterizer(
            $this->get_width(),
            // document width
            $this->get_height(),
            // document height
            $this->get_view_box(),
            // viewBox
            Length::convert($this->get_width() ?: '100%', $rasterizer->get_width()),
            Length::convert($this->get_height() ?: '100%', $rasterizer->get_height())
        );
        // perform rasterization as usual
        parent::rasterize($sub_rasterizer);
        $img = $sub_rasterizer->finish();
        // copy nested viewport onto parent viewport
        imagecopy(
            $rasterizer->get_image(),
            // destination
            $img,
            // source
            0,
            // dst_x
            0,
            // dst_y
            0,
            // srx_x
            0,
            // src_y
            $sub_rasterizer->get_width(),
            // src_w
            $sub_rasterizer->get_height()
        );
        imagedestroy($img);
    }
    /**
     * @inheritdoc
     */
    public function get_serializable_attributes(): array
    {
        $attrs = parent::get_serializable_attributes();
        if (isset($attrs['width']) && $attrs['width'] === '100%') {
            unset($attrs['width']);
        }
        if (isset($attrs['height']) && $attrs['height'] === '100%') {
            unset($attrs['height']);
        }
        return $attrs;
    }
    /**
     * @inheritdoc
     */
    public function get_serializable_namespaces(): array
    {
        if ($this->is_root()) {
            return parent::get_serializable_namespaces() + ['' => 'http://www.w3.org/2000/svg', 'xlink' => 'http://www.w3.org/1999/xlink'];
        }
        return parent::get_serializable_namespaces();
    }
    /**
     * Returns the node with the given id, or null if no such node exists in the
     * document.
     *
     * @param string $id The id to search for.
     *
     * @return SVGNode|null The node with the given id if it exists.
     */
    public function get_element_by_id(string $id): ?Svg_Node
    {
        // start with document
        $stack = [$this];
        while (!empty($stack)) {
            $elem = array_pop($stack);
            // check current node
            if ($elem->get_attribute('id') === $id) {
                return $elem;
            }
            // add children to stack (tree order traversal)
            if ($elem instanceof Svg_Node_Container) {
                for ($i = $elem->count_children() - 1; $i >= 0; --$i) {
                    $stack[] = $elem->get_child($i);
                }
            }
        }
        return null;
    }
}