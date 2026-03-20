<?php

declare (strict_types=1);
namespace SVG\Reading;

use Simple_Xml_Element;
use SVG\Nodes\Svg_Node;
use SVG\Nodes\Svg_Node_Container;
use SVG\SVG;
use SVG\Utilities\Svg_Style_Parser;
/**
 * This class is used to read XML strings or files and turn them into instances
 * of SVG by parsing the document tree.
 *
 * In contrast to SVGWriter, a single instance can perform any number of reads.
 */
class Svg_Reader
{
    /**
     * Parses the given string as XML and turns it into an instance of SVG.
     * Returns null when parsing fails.
     *
     * @param string $string The XML string to parse.
     *
     * @return SVG|null An image object representing the parse result.
     */
    public function parse_string(string $string): ?SVG
    {
        $xml = new Simple_Xml_Element($string, LIBXML_PARSEHUGE);
        return $this->parse_xml($xml);
    }
    /**
     * Parses the file at the given path/URL as XML and turns it into an
     * instance of SVG.
     *
     * The path can be on the local file system, or a URL on the network.
     * Returns null when parsing fails.
     *
     * @param string $filename The path or URL of the file to parse.
     *
     * @return SVG|null An image object representing the parse result.
     */
    public function parse_file(string $filename): ?SVG
    {
        $xml = simplexml_load_file($filename);
        return $this->parse_xml($xml);
    }
    /**
     * Parses the given XML document into an instance of SVG.
     * Returns null when parsing fails.
     *
     * @param SimpleXMLElement $xml The root node of the SVG document to parse.
     *
     * @return SVG|null An image object representing the parse result.
     */
    public function parse_xml(Simple_Xml_Element $xml): ?SVG
    {
        $name = $xml->get_name();
        if ($name !== 'svg') {
            return null;
        }
        $img = new SVG();
        $doc = $img->get_document();
        $namespaces = $xml->get_namespaces(true);
        $doc->set_namespaces($namespaces);
        $ns_keys = array_keys($namespaces);
        if (!in_array('', $ns_keys, true) && !in_array(null, $ns_keys, true)) {
            $ns_keys[] = '';
        }
        $this->apply_attributes($doc, $xml, $ns_keys);
        $this->apply_styles($doc, $xml);
        $this->add_children($doc, $xml, $ns_keys);
        return $img;
    }
    /**
     * Iterates over all XML attributes and applies them to the given node.
     *
     * Since styles in SVG can also be expressed with attributes, this method
     * checks the name of each attribute and, if it matches that of a style,
     * applies it as a style instead. The actual 'style' attribute is ignored.
     *
     * @see SVGReader::$styleAttributes The attributes considered styles.
     *
     * @param SVGNode           $node       The node to apply the attributes to.
     * @param SimpleXMLElement  $xml        The attribute source.
     * @param string[]          $namespaces Array of allowed namespace prefixes.
     */
    private function apply_attributes(Svg_Node $node, Simple_Xml_Element $xml, array $namespaces): void
    {
        foreach ($namespaces as $ns) {
            foreach ($xml->attributes($ns, true) as $key => $value) {
                if ($key === 'style') {
                    continue;
                }
                if (Attribute_Registry::is_style($key)) {
                    $converted_value = Attribute_Registry::convert_style_attribute($key, $value);
                    $node->set_style($key, $converted_value);
                    continue;
                }
                if (!empty($ns) && $ns !== 'svg') {
                    $key = $ns . ':' . $key;
                }
                $node->set_attribute($key, $value);
            }
        }
    }
    /**
     * Parses the 'style' attribute (if it exists) and applies all styles to the
     * given node.
     *
     * This method does NOT handle styles expressed as attributes (stroke="").
     * @see SVGReader::applyAttributes() For styles expressed as attributes.
     *
     * @param SVGNode           $node The node to apply the styles to.
     * @param SimpleXMLElement  $xml  The attribute source.
     */
    private function apply_styles(Svg_Node $node, Simple_Xml_Element $xml): void
    {
        if (!isset($xml['style'])) {
            return;
        }
        $styles = Svg_Style_Parser::parse_styles($xml['style']);
        foreach ($styles as $key => $value) {
            $node->set_style($key, $value);
        }
    }
    /**
     * Iterates over all children, parses them into library class instances,
     * and adds them to the given node container.
     *
     * @param SVGNodeContainer  $node       The node to add the children to.
     * @param SimpleXMLElement  $xml        The XML node containing the children.
     * @param string[]          $namespaces Array of allowed namespace prefixes.
     */
    private function add_children(Svg_Node_Container $node, Simple_Xml_Element $xml, array $namespaces): void
    {
        foreach ($namespaces as $ns) {
            foreach ($xml->children($ns, true) as $child) {
                $node->add_child($this->parse_node($ns, $child, $namespaces));
            }
        }
    }
    /**
     * Parses the given XML element into an instance of a SVGNode subclass.
     * Unknown node types use a generic implementation.
     *
     * @param string            $ns         The tag name namespace prefix.
     * @param SimpleXMLElement  $xml        The XML element to parse.
     * @param string[]          $namespaces Array of allowed namespace prefixes.
     *
     * @return SVGNode The parsed node.
     *
     * @SuppressWarnings(PHPMD.ErrorControlOperator)
     */
    private function parse_node(string $ns, Simple_Xml_Element $xml, array $namespaces): Svg_Node
    {
        $tag_name = $xml->get_name();
        if (!empty($ns) && $ns !== 'svg') {
            $tag_name = $ns . ':' . $tag_name;
        }
        $node = Node_Registry::create($tag_name);
        // obtain array of namespaces that are declared directly on this node
        $extra_namespaces = @$xml->get_doc_namespaces(false, false);
        if (!empty($extra_namespaces)) {
            $namespaces = array_unique(array_merge($namespaces, array_keys($extra_namespaces)));
            $node->set_namespaces($extra_namespaces);
        }
        $this->apply_attributes($node, $xml, $namespaces);
        $this->apply_styles($node, $xml);
        $node->set_value($xml);
        if ($node instanceof Svg_Node_Container) {
            $this->add_children($node, $xml, $namespaces);
        }
        return $node;
    }
}