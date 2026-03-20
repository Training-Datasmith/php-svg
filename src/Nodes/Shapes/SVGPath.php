<?php

declare (strict_types=1);
namespace SVG\Nodes\Shapes;

use SVG\Nodes\Svg_Node_Container;
use SVG\Rasterization\Path\Path_Parser;
use SVG\Rasterization\Svg_Rasterizer;
use SVG\Rasterization\Transform\Transform_Parser;
/**
 * Represents the SVG tag 'path'.
 */
class Svg_Path extends Svg_Node_Container
{
    public const TAG_NAME = 'path';
    private static Path_Parser $path_parser;
    /**
     * @param string|null $d The path description.
     */
    public function __construct(?string $d = null)
    {
        parent::__construct();
        $this->set_attribute('d', $d);
    }
    /**
     * @return string|null The path description string.
     */
    public function get_description(): ?string
    {
        return $this->get_attribute('d');
    }
    /**
     * Sets the path description string.
     *
     * @param string|null $d The new description.
     *
     * @return $this This node instance, for call chaining.
     */
    public function set_description(?string $d): Svg_Path
    {
        return $this->set_attribute('d', $d);
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
        $d = $this->get_description();
        if (!isset($d)) {
            return;
        }
        $commands = self::get_path_parser()->parse($d);
        Transform_Parser::parse_transform_string($this->get_attribute('transform'), $rasterizer->push_transform());
        $rasterizer->render('path', ['commands' => $commands, 'fill-rule' => strtolower($this->get_computed_style('fill-rule') ?: 'nonzero')], $this);
        $rasterizer->pop_transform();
    }
    private static function get_path_parser(): Path_Parser
    {
        self::$path_parser ??= new Path_Parser();
        return self::$path_parser;
    }
}