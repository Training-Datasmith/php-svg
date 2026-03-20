<?php

declare (strict_types=1);
namespace SVG\Rasterization;

use InvalidArgumentException;
use RuntimeException;
use SVG\Fonts\Font_Registry;
use SVG\Nodes\Svg_Node;
use SVG\Rasterization\Renderers\Renderer;
use SVG\Rasterization\Transform\Transform;
use SVG\Utilities\Colors\Color;
use SVG\Utilities\Units\Length;
/**
 * This class is the main entry point for the rasterization process.
 *
 * Each constructed instance represents one output image.
 * Rasterization happens by invoking `render()` with the id of a specific
 * renderer, e.g. 'ellipse' or 'polygon', which then performs the actual
 * drawing.
 * Note that renderers DO NOT correspond 1:1 to node types (e.g. there is no
 * renderer 'circle', but 'ellipse' with equal radii is used).
 *
 * @SuppressWarnings("coupling")
 */
class Svg_Rasterizer
{
    /**
     * @var Renderers\Renderer[] $renderers Map of shapes to renderers.
     */
    private static array $renderers;
    private ?\SVG\Fonts\Font_Registry $font_registry = null;
    /**
     * @var float[] The document's viewBox (x, y, w, h).
     */
    private $view_box;
    /**
     * @var int $width  The output image width, in pixels.
     */
    private int $width;
    /**
     * @var int $height The output image height, in pixels.
     */
    private int $height;
    /**
     * @var resource $outImage The output image as a GD resource.
     */
    private $out_image;
    // precomputed properties for getter methods, used often during render
    private ?float $doc_width;
    private ?float $doc_height;
    private float $diagonal_scale;
    private array $transform_stack;
    /**
     * @param string|null $docWidth   The original SVG document width, as a string.
     * @param string|null $docHeight  The original SVG document height, as a string.
     * @param float[]|null $viewBox   The document's viewBox.
     * @param int $width              The output image width, in pixels.
     * @param int $height             The output image height, in pixels.
     * @param string|null $background The background color (hex/rgb[a]/hsl[a]/...).
     */
    public function __construct(?string $doc_width, ?string $doc_height, ?array $view_box, int $width, int $height, ?string $background = null)
    {
        $this->view_box = empty($view_box) ? null : $view_box;
        $this->width = $width;
        $this->height = $height;
        // precompute properties
        $this->doc_width = Length::convert($doc_width ?: '100%', $width);
        $this->doc_height = Length::convert($doc_height ?: '100%', $height);
        $scale_x = $width / (!empty($view_box) ? $view_box[2] : $this->doc_width ?? 0);
        $scale_y = $height / (!empty($view_box) ? $view_box[3] : $this->doc_height ?? 0);
        $this->diagonal_scale = hypot($scale_x, $scale_y) / M_SQRT2;
        $offset_x = !empty($view_box) ? -($view_box[0] * $scale_x) : 0;
        $offset_y = !empty($view_box) ? -($view_box[1] * $scale_y) : 0;
        // the transform stack starts out with a simple viewport transform
        $transform = Transform::identity();
        $transform->translate($offset_x, $offset_y);
        $transform->scale($scale_x, $scale_y);
        $this->transform_stack = [$transform];
        // create image
        $this->out_image = self::create_image($width, $height, $background);
        self::create_dependencies();
    }
    /**
     * Sets up a new truecolor GD image resource with the given dimensions.
     *
     * The returned image supports and is filled with transparency.
     *
     * @param int $width              The output image width, in pixels.
     * @param int $height             The output image height, in pixels.
     * @param string|null $background The background color (hex/rgb[a]/hsl[a]/...).
     *
     * @return resource The created GD image resource.
     */
    private static function create_image(int $width, int $height, ?string $background)
    {
        $img = imagecreatetruecolor($width, $height);
        imagealphablending($img, true);
        imagesavealpha($img, true);
        $bg_rgb = 0x7f000000;
        if (!empty($background)) {
            $bg_color = Color::parse($background);
            $alpha = 127 - (int) ($bg_color[3] * 127 / 255);
            $bg_rgb = ($alpha << 24) + ($bg_color[0] << 16) + ($bg_color[1] << 8) + $bg_color[2];
        }
        imagefill($img, 0, 0, $bg_rgb);
        return $img;
    }
    /**
     * Makes sure the singleton static variables are all instantiated.
     *
     * This includes registering all of the standard renderers, as well as
     * preparing the path parser and the path approximator.
     */
    private static function create_dependencies(): void
    {
        if (isset(self::$renderers)) {
            return;
        }
        self::$renderers = ['rect' => new Renderers\Rect_Renderer(), 'line' => new Renderers\Line_Renderer(), 'ellipse' => new Renderers\Ellipse_Renderer(), 'polygon' => new Renderers\Polygon_Renderer(), 'path' => new Renderers\Path_Renderer(), 'image' => new Renderers\Image_Renderer(), 'text' => new Renderers\Text_Renderer()];
    }
    /**
     * Finds the renderer registered with the given id.
     *
     * @param string $id The id of a registered renderer instance.
     *
     * @return Renderer The requested renderer.
     * @throws InvalidArgumentException If no such renderer exists.
     */
    private static function get_renderer(string $id): Renderer
    {
        if (!isset(self::$renderers[$id])) {
            throw new InvalidArgumentException('no such renderer: ' . $id);
        }
        return self::$renderers[$id];
    }
    public function set_font_registry(Font_Registry $font_registry): void
    {
        $this->font_registry = $font_registry;
    }
    public function get_font_registry(): ?Font_Registry
    {
        return $this->font_registry;
    }
    /**
     * Uses the specified renderer to draw an object, as described via the
     * params attribute, and by utilizing the provided node context.
     *
     * The node is required for access to things like the opacity as well as
     * stroke/fill attributes etc.
     *
     * @param string  $rendererId The id of the renderer to use.
     * @param array $params       An array of options to pass to the renderer.
     * @param SVGNode $context    The SVGNode that serves as drawing context.
     *
     *
     * @throws InvalidArgumentException If no such renderer exists.
     */
    public function render(string $renderer_id, array $params, Svg_Node $context): void
    {
        $renderer = self::get_renderer($renderer_id);
        $renderer->render($this, $params, $context);
    }
    /**
     * @return float|null The original SVG document width, in pixels.
     */
    public function get_document_width(): ?float
    {
        return $this->doc_width;
    }
    /**
     * @return float|null The original SVG document height, in pixels.
     */
    public function get_document_height(): ?float
    {
        return $this->doc_height;
    }
    /**
     * Obtain the normalized diagonal of the SVG viewport. The normalized diagonal is to be used as the reference size
     * for percentages that don't strictly refer to the horizontal or vertical axis. Examples of such values are
     * a circle's radius attribute or the stroke-width.
     *
     * This is computed by the formula <code>hypot(documentWidth, documentHeight)/sqrt(2)</code>.
     *
     * @return float The normalized diagonal length.
     */
    public function get_normalized_diagonal(): float
    {
        // https://svgwg.org/svg2-draft/coords.html#Units
        // For any other length value expressed as a percentage of the SVG viewport, the percentage must be calculated
        // as a percentage of the normalized diagonal of the ‘viewBox’ applied to that viewport. If no ‘viewBox’ is
        // specified, then the normalized diagonal of the SVG viewport must be used. The normalized diagonal length must
        // be calculated with sqrt((width)**2 + (height)**2)/sqrt(2).
        return hypot($this->doc_width ?? 0, $this->doc_height ?? 0) / M_SQRT2;
    }
    /**
     * @return int The output image width, in pixels.
     */
    public function get_width(): int
    {
        return $this->width;
    }
    /**
     * @return int The output image height, in pixels.
     */
    public function get_height(): int
    {
        return $this->height;
    }
    /**
     * Determine the normalized diagonal scaling factor. This is the factor that should be used when scaling percentages
     * for properties that are not strictly horizontal or strictly vertical, such as stroke-width.
     *
     * @return float The scaling factor of the view diagonal.
     */
    public function get_diagonal_scale(): float
    {
        return $this->diagonal_scale;
    }
    /**
     * @return float[]|null The document's viewBox.
     */
    public function get_view_box(): ?array
    {
        return $this->view_box;
    }
    /**
     * Obtain a Transform object from userspace coordinates into output image coordinates.
     *
     * This will NOT create a copy, so mutating the returned object is unsafe (= might affect later code in unexpected
     * ways). Instead, perform a call to <code>pushTransform()</code> and mutate the return value of that. Then, when
     * done using the changed transform, call <code>popTransform()</code> to revert back to the previous state.
     *
     * @return Transform The created transform.
     */
    public function get_current_transform(): Transform
    {
        return $this->transform_stack[count($this->transform_stack) - 1];
    }
    /**
     * Create a copy of the current transform and push it onto the transform stack, so that it becomes the new
     * current transform. The copied transform is then returned and can be manipulated. When done rendering with this
     * mutated transform, call <code>popTransform()</code> to revert back to the previous transform.
     *
     * @return Transform The copy of the current transform, ready to have operations appended to it.
     */
    public function push_transform(): Transform
    {
        $next_transform = clone $this->transform_stack[count($this->transform_stack) - 1];
        $this->transform_stack[] = $next_transform;
        return $next_transform;
    }
    /**
     * Revert back to the previous transform, by removing the last transform that was pushed via
     * <code>pushTransform()</code>. There must be a matching call to <code>popTransform</code> for every call to
     * <code>pushTransform()</code>. Popping a transform when no pushed transform remains is an error.
     *
     * @throws RuntimeException If trying to pop a transform but the stack contains only the initial transform.
     */
    public function pop_transform(): void
    {
        if (count($this->transform_stack) <= 1) {
            throw new RuntimeException('popTransform() called with no transform on the stack!');
        }
        array_pop($this->transform_stack);
    }
    /**
     * Applies final processing steps to the output image. It is then returned.
     *
     * @return resource The GD image resource this rasterizer is operating on.
     */
    public function finish()
    {
        return $this->out_image;
    }
    /**
     * @return resource The GD image resource this rasterizer is operating on.
     */
    public function get_image()
    {
        return $this->out_image;
    }
}