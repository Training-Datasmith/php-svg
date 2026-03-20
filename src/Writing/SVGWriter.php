<?php

declare (strict_types=1);
namespace SVG\Writing;

use SVG\Nodes\C_Data_Container;
use SVG\Nodes\Svg_Node;
use SVG\Nodes\Svg_Node_Container;
use SVG\Shims\Str;
/**
 * This class is used for composing ("writing") XML strings from nodes.
 * Every instance corresponds to one output string.
 */
class Svg_Writer
{
    /**
     * @var string $outString The XML output string being written
     */
    private string $out_string = '';
    public function __construct(bool $is_standalone = true)
    {
        if ($is_standalone) {
            $this->out_string = '<?xml version="1.0" encoding="utf-8"?>';
        }
    }
    /**
     * @return string The XML output string up until the point currently written.
     */
    public function get_string(): string
    {
        return $this->out_string;
    }
    /**
     * Converts the given node into its XML representation and appends that to
     * this writer's output string.
     *
     * The generated string contains the attributes defined via
     * SVGNode::getSerializableAttributes() and the styles defined via
     * SVGNode::getSerializableStyles().
     * Container nodes (<g></g>) and self-closing tags (<rect />) are
     * distinguished correctly.
     *
     * @param SVGNode $node The node to write.
     */
    public function write_node(Svg_Node $node): void
    {
        $this->out_string .= '<' . $node->get_name();
        $this->append_namespaces($node->get_serializable_namespaces());
        $this->append_attributes($node->get_serializable_attributes());
        $this->append_styles($node->get_serializable_styles());
        $text_content = htmlspecialchars($node->get_value());
        if ($node instanceof C_Data_Container) {
            $this->out_string .= '>';
            $this->write_cdata($node->get_value());
            $this->out_string .= '</' . $node->get_name() . '>';
            return;
        }
        if ($node instanceof Svg_Node_Container && $node->count_children() > 0) {
            $this->out_string .= '>';
            for ($i = 0, $n = $node->count_children(); $i < $n; ++$i) {
                $this->write_node($node->get_child($i));
            }
            $this->out_string .= $text_content . '</' . $node->get_name() . '>';
            return;
        }
        if (Str::trim($text_content) !== '') {
            $this->out_string .= '>' . $text_content . '</' . $node->get_name() . '>';
            return;
        }
        $this->out_string .= ' />';
    }
    /**
     * Appends all attributes defined in the given associative array to this
     * writer's output.
     *
     * @param string[] $namespaces An associative array of attribute strings.
     */
    private function append_namespaces(array $namespaces): void
    {
        $normalized = [];
        foreach ($namespaces as $key => $value) {
            $namespace = self::serialize_namespace($key);
            $normalized[$namespace] = $value;
        }
        $this->append_attributes($normalized);
    }
    /**
     * Converts the given namespace string to standard form, i.e. ensuring that
     * it either equals 'xmlns' or starts with 'xmlns:'.
     *
     * @param string $namespace The namespace string.
     *
     * @return string The modified namespace string to be added as attribute.
     */
    private static function serialize_namespace(string $namespace): string
    {
        if ($namespace === '' || $namespace === 'xmlns') {
            return 'xmlns';
        }
        if (substr($namespace, 0, 6) !== 'xmlns:') {
            return 'xmlns:' . $namespace;
        }
        return $namespace;
    }
    /**
     * Converts the given styles into a CSS string, then appends a 'style'
     * attribute with the value set to that string to this writer's output.
     *
     * @param string[] $styles An associative array of styles for the attribute.
     */
    private function append_styles(array $styles): void
    {
        if (empty($styles)) {
            return;
        }
        $string = '';
        $prepend_semicolon = false;
        foreach ($styles as $key => $value) {
            if ($prepend_semicolon) {
                $string .= '; ';
            }
            $prepend_semicolon = true;
            $string .= $key . ': ' . $value;
        }
        $this->append_attribute('style', $string);
    }
    /**
     * Appends all attributes defined in the given associative array to this
     * writer's output.
     *
     * @param string[] $attrs An associative array of attribute strings.
     */
    private function append_attributes(array $attrs): void
    {
        foreach ($attrs as $key => $value) {
            $this->append_attribute($key, $value);
        }
    }
    /**
     * Appends a single attribute given by key and value to this writer's
     * output.
     *
     * @param string $attrName  The attribute name.
     * @param string $attrValue The attribute value.
     */
    private function append_attribute(string $attr_name, string $attr_value): void
    {
        $xml1 = defined('ENT_XML1') ? ENT_XML1 : 16;
        $attr_name = htmlspecialchars($attr_name, $xml1 | ENT_COMPAT);
        $attr_value = htmlspecialchars($attr_value, $xml1 | ENT_COMPAT);
        $this->out_string .= ' ' . $attr_name . '="' . $attr_value . '"';
    }
    /**
     * Appends CDATA content given the $cdata value to the writer's output.
     *
     * @param string $cdata The content.
     */
    private function write_cdata(string $cdata): void
    {
        $this->out_string .= '<![CDATA[' . $cdata . ']]>';
    }
}