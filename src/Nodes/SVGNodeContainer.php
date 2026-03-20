<?php

declare (strict_types=1);
namespace SVG\Nodes;

use SVG\Nodes\Structures\Svg_Style;
use SVG\Rasterization\Svg_Rasterizer;
use SVG\Rasterization\Transform\Transform_Parser;
use SVG\Shims\Str;
use SVG\Utilities\Svg_Style_Parser;
/**
 * Represents an SVG image element that contains child elements.
 */
abstract class Svg_Node_Container extends Svg_Node
{
    /**
     * @var SVGNode[] $children This node's child nodes.
     */
    protected array $children;
    /**
     * @var string[] $globalStyles A 2D array mapping CSS selectors to values.
     */
    protected array $container_styles;
    public function __construct()
    {
        parent::__construct();
        $this->container_styles = [];
        $this->children = [];
    }
    /**
     * Inserts an SVGNode instance at the given index, or, if no index is given,
     * at the end of the child list.
     * Does nothing if the node already exists in this container.
     *
     * @param SVGNode  $node  The node to add to this container's children.
     * @param int|null $index The position to insert at (optional).
     *
     * @return $this This node instance, for call chaining.
     */
    public function add_child(Svg_Node $node, ?int $index = null): Svg_Node_Container
    {
        if ($node === $this || $node->parent === $this) {
            return $this;
        }
        if (isset($node->parent)) {
            $node->parent->remove_child($node);
        }
        $index ??= count($this->children);
        // insert and set new parent
        array_splice($this->children, $index, 0, [$node]);
        $node->parent = $this;
        if ($node instanceof Svg_Style) {
            // if node is SVGStyle then add rules to container's style
            $this->add_container_style($node);
        }
        return $this;
    }
    /**
     * Removes a child node, given either as its instance or as the index it's
     * located at, from this container.
     *
     * @param SVGNode|int $child The node (or respective index) to remove.
     *
     * @return $this This node instance, for call chaining.
     */
    public function remove_child($child): Svg_Node_Container
    {
        $index = $this->resolve_child_index($child);
        if ($index === false) {
            return $this;
        }
        $node = $this->children[$index];
        $node->parent = null;
        array_splice($this->children, $index, 1);
        return $this;
    }
    /**
     * Replaces a child node with another node.
     *
     * @param SVGNode|int $child The node (or respective index) to replace.
     * @param SVGNode     $node  The replacement node.
     *
     * @return $this This node instance, for call chaining.
     */
    public function set_child($child, Svg_Node $node): Svg_Node_Container
    {
        $index = $this->resolve_child_index($child);
        if ($index === false) {
            return $this;
        }
        $this->remove_child($index);
        $this->add_child($node, $index);
        return $this;
    }
    /**
     * Resolves a child node to its index. If an index is given, it is returned
     * without modification.
     *
     * @param SVGNode|int $nodeOrIndex The node (or respective index).
     *
     * @return int|false The index, or false if argument invalid or not a child.
     */
    private function resolve_child_index($node_or_index)
    {
        if (is_int($node_or_index)) {
            return $node_or_index;
        }
        if ($node_or_index instanceof Svg_Node) {
            return array_search($node_or_index, $this->children, true);
        }
        return false;
    }
    /**
     * @return int The amount of children in this container.
     */
    public function count_children(): int
    {
        return count($this->children);
    }
    /**
     * @param int $index The index of the child to get.
     * @return SVGNode The child node at the given index.
     */
    public function get_child(int $index): Svg_Node
    {
        return $this->children[$index];
    }
    /**
     * Adds the SVGStyle element rules to container's styles.
     *
     * @param SVGStyle $styleNode The style node to add rules from.
     *
     * @return $this This node instance, for call chaining.
     */
    public function add_container_style(Svg_Style $style_node): Svg_Node_Container
    {
        $new_styles = Svg_Style_Parser::parse_css($style_node->get_value());
        $this->container_styles = array_merge($this->container_styles, $new_styles);
        return $this;
    }
    /**
     * @inheritdoc
     */
    public function rasterize(Svg_Rasterizer $rasterizer): void
    {
        if ($this->get_computed_style('display') === 'none') {
            return;
        }
        // 'visibility' can be overridden -> only applied in shape nodes.
        Transform_Parser::parse_transform_string($this->get_attribute('transform'), $rasterizer->push_transform());
        foreach ($this->children as $child) {
            $child->rasterize($rasterizer);
        }
        $rasterizer->pop_transform();
    }
    /**
     * Returns a node's 'global' style rules.
     *
     * @param SVGNode $node The node for which we need to obtain.
     * its container style rules.
     *
     * @return string[] The style rules to be applied.
     */
    public function get_container_style_for_node(Svg_Node $node): array
    {
        $pattern = $node->get_id_and_class_pattern();
        return $this->get_container_style_by_pattern($pattern);
    }
    /**
     * Returns style rules for the given node id + class pattern.
     *
     * @param string|null $pattern The node's pattern.
     *
     * @return string[] The style rules to be applied.
     */
    public function get_container_style_by_pattern(?string $pattern): array
    {
        if ($pattern === null) {
            return [];
        }
        $node_styles = [];
        if ($this->parent instanceof \SVG\Nodes\Svg_Node_Container) {
            $node_styles = $this->parent->get_container_style_by_pattern($pattern);
        }
        $keys = $this->preg_grep_style($pattern);
        foreach ($keys as $key) {
            $node_styles = array_merge($node_styles, $this->container_styles[$key]);
        }
        return $node_styles;
    }
    /**
     * Returns the array consisting of the keys of the style rules that match
     * the given pattern.
     *
     * @param string $pattern The pattern to search for.
     *
     * @return string[] The matches array
     */
    private function preg_grep_style(string $pattern): array
    {
        return preg_grep($pattern, array_keys($this->container_styles));
    }
    /**
     * @inheritdoc
     */
    public function get_elements_by_tag_name(string $tag_name, array &$result = []): array
    {
        foreach ($this->children as $child) {
            if ($tag_name === '*' || $child->get_name() === $tag_name) {
                $result[] = $child;
            }
            $child->get_elements_by_tag_name($tag_name, $result);
        }
        return $result;
    }
    /**
     * @inheritdoc
     */
    public function get_elements_by_class_name($class_name, array &$result = []): array
    {
        if (!is_array($class_name)) {
            $class_name = preg_split('/\s+/', Str::trim($class_name));
        }
        // shortcut if empty
        if (empty($class_name) || $class_name[0] === '') {
            return $result;
        }
        foreach ($this->children as $child) {
            $class = ' ' . $child->get_attribute('class') . ' ';
            $all_match = true;
            foreach ($class_name as $cn) {
                if (strpos($class, ' ' . $cn . ' ') === false) {
                    $all_match = false;
                    break;
                }
            }
            if ($all_match) {
                $result[] = $child;
            }
            $child->get_elements_by_class_name($class_name, $result);
        }
        return $result;
    }
}