<?php

declare (strict_types=1);
namespace SVG\Rasterization\Renderers;

use SVG\Fonts\Font_Registry;
use SVG\Nodes\Svg_Node;
use SVG\Rasterization\Svg_Rasterizer;
use SVG\Rasterization\Transform\Transform;
use SVG\Shims\Str;
use SVG\Utilities\Colors\Color;
use SVG\Utilities\Units\Length;
/**
 * This extends the Renderer class to offer features for multi-pass rendering
 * of shapes. The render options are first prepared, then given to the primitive
 * methods (stroke, fill).
 */
abstract class Multi_Pass_Renderer extends Renderer
{
    /**
     * @inheritdoc
     */
    public function render(Svg_Rasterizer $rasterizer, array $options, Svg_Node $context): void
    {
        $transform = $rasterizer->get_current_transform();
        $params = $this->prepare_render_params($options, $transform, $rasterizer->get_font_registry());
        if (!isset($params)) {
            return;
        }
        $paint_order = self::get_paint_order($context);
        foreach ($paint_order as $paint) {
            if ($paint === 'stroke') {
                $this->paint_stroke($rasterizer, $context, $params);
            } elseif ($paint === 'fill') {
                $this->paint_fill($rasterizer, $context, $params);
            }
        }
    }
    /**
     * @param $params
     */
    private function paint_stroke(Svg_Rasterizer $rasterizer, Svg_Node $context, $params): void
    {
        $stroke = $context->get_computed_style('stroke');
        if (isset($stroke) && $stroke !== 'none') {
            $stroke_opacity = self::parse_opacity($context->get_computed_style('stroke-opacity'));
            $stroke = self::prepare_color($stroke, $context, $stroke_opacity);
            $stroke_width = $context->get_computed_style('stroke-width');
            $stroke_width = Length::convert($stroke_width, $rasterizer->get_normalized_diagonal());
            $stroke_width = $stroke_width * $rasterizer->get_diagonal_scale();
            if ($stroke_width > 0) {
                $this->render_stroke($rasterizer->get_image(), $params, $stroke, $stroke_width);
            }
        }
    }
    /**
     * @param $params
     */
    private function paint_fill(Svg_Rasterizer $rasterizer, Svg_Node $context, $params): void
    {
        $fill = $context->get_computed_style('fill');
        if (isset($fill) && $fill !== 'none') {
            $fill_opacity = self::parse_opacity($context->get_computed_style('fill-opacity'));
            $fill = self::prepare_color($fill, $context, $fill_opacity);
            $this->render_fill($rasterizer->get_image(), $params, $fill);
        }
    }
    /**
     * Converts the options array into a new parameters array that the render methods can make more sense of.
     *
     * Specifically, the intention is to allow subclasses to outsource coordinate translation, approximation of curves
     * and the like to this method rather than dealing with it in the render methods. This shall encourage single passes
     * over the input data (for performance reasons).
     *
     * If this method determines that rendering isn't possible (e.g. because the shape is empty), it shall return null.
     *
     * @param array             $options      The associative array of raw options.
     * @param Transform         $transform    The coordinate transform to apply, to go from user to output coordinates.
     * @param FontRegistry|null $fontRegistry The font registry to use for text rendering.
     *
     * @return array|null The new associative array of computed render parameters, if there is something to render.
     */
    abstract protected function prepare_render_params(array $options, Transform $transform, ?Font_Registry $font_registry);
    /**
     * Renders the shape's filled version in the given color, using the params
     * array obtained from the prepare method.
     *
     * Doing nothing is valid behavior if the shape can't be filled
     * (for example, a line).
     *
     * @see Renderer::prepareRenderParams() For info on the params array.
     *
     * @param resource $image  The image resource to render to.
     * @param array    $params The render params.
     * @param int      $color  The color (a GD int) to fill the shape with.
     */
    abstract protected function render_fill($image, $params, int $color): void;
    /**
     * Renders the shape's outline in the given color, using the params array
     * obtained from the prepare method.
     *
     * @see Renderer::prepareRenderParams() For info on the params array.
     *
     * @param resource $image  The image resource to render to.
     * @param array    $params The render params.
     * @param int      $color  The color (a GD int) to outline the shape with.
     * @param float    $strokeWidth  The stroke's thickness, in pixels.
     */
    abstract protected function render_stroke($image, $params, int $color, float $stroke_width): void;
    /**
     * @return string[]
     */
    private static function get_paint_order(Svg_Node $context): array
    {
        $paint_order = $context->get_computed_style('paint-order');
        $paint_order = preg_replace('#\s{2,}#', ' ', Str::trim($paint_order));
        $default_order = ['fill', 'stroke', 'markers'];
        if ($paint_order === 'normal' || empty($paint_order)) {
            return $default_order;
        }
        $paint_order = array_intersect(explode(' ', $paint_order), $default_order);
        return array_merge($paint_order, array_diff($default_order, $paint_order));
    }
    /**
     * Parses the color string and applies the node's total opacity to it,
     * then returns it as a GD color int.
     *
     * @param string|null  $color           The CSS color value.
     * @param SVGNode      $context         The node serving as the opacity reference.
     * @param float        $specificOpacity An additional opacity factor specific to the paint operation.
     *
     * @return int The prepared color as a GD color integer.
     */
    private static function prepare_color(?string $color, Svg_Node $context, float $specific_opacity = 1.0): int
    {
        $color = Color::parse($color);
        $rgb = ($color[0] << 16) + ($color[1] << 8) + $color[2];
        $opacity = self::calculate_total_opacity($context) * $specific_opacity;
        $a = 127 - $opacity * (int) ($color[3] * 127 / 255);
        return $rgb | (int) $a << 24;
    }
    /**
     * Obtains the node's very own opacity value, as specified in its styles,
     * taking care of 'inherit' and defaulting to 1.
     *
     * @param SVGNode $node The node to get the opacity value of.
     *
     * @return float The node's own opacity value.
     */
    private static function get_node_opacity(Svg_Node $node): float
    {
        $opacity = $node->get_style('opacity');
        if ($opacity === 'inherit') {
            $parent = $node->get_parent();
            if (isset($parent)) {
                return self::get_node_opacity($parent);
            }
        }
        return self::parse_opacity($opacity);
    }
    /**
     * Calculates the node's total opacity by multiplying its own with all of
     * its parents' ones.
     *
     * @param SVGNode $node The node of which to calculate the opacity.
     *
     * @return float The node's total opacity.
     */
    private static function calculate_total_opacity(Svg_Node $node): float
    {
        $opacity = self::get_node_opacity($node);
        $parent = $node->get_parent();
        if (isset($parent)) {
            return $opacity * self::calculate_total_opacity($parent);
        }
        return $opacity;
    }
    /**
     * Parse an alpha value (such as from the 'opacity', 'fill-opacity', or 'stroke-opacity' attributes).
     *
     * @param string|null $value The raw attribute value.
     * @return float The parsed alpha value in the range 0 to 1. Invalid inputs are mapped to 1.
     */
    private static function parse_opacity(?string $value): float
    {
        // https://svgwg.org/svg2-draft/render.html#ObjectAndGroupOpacityProperties
        // https://drafts.csswg.org/css-color/#transparency
        if ($value === null) {
            return 1;
        }
        // real numbers
        if (is_numeric($value)) {
            return (float) $value;
        }
        // percentages
        $matches = [];
        if (preg_match('/^([+-]?\d+(?:\.\d+)?|\.\d+)%$/', $value, $matches)) {
            return max(0, min(100, $matches[1])) / 100;
        }
        return 1;
    }
}