<?php

namespace PolymerMallard\View;

/**
 * TwigToVue
 *
 * https://github.com/rcrowe/TwigBridge
 * https://twig.symfony.com/doc/3.x/tags/for.html
 * https://github.com/Rct567/DomQuery
 * https://twig.symfony.com/doc/2.x/advanced.html
 */
// class TwigToVue {

//     /**
//      * QueyrPath / DOMparser
//      * @var QueryPath
//      */
//     public $qp;

//     private $_attributesHtml = [
//         'class', 'href', 'id', 'style',
//     ];

//     /**
//      * Parsed tags from template
//      * @var array
//      */
//     private $_parsedTags;

//     /**
//      * Resulting string
//      * @var string
//      */
//     private $_result;

//     /**
//      * HTML tags
//      *
//      * @var [type]
//      */
//     private $_tagsHtml = [
//         'for '     => [ 'tag' => 'for' ],
//         'endfor'   => [ 'tag' => 'endfor' ],
//         'include ' => [ 'tag' => 'include' ],
//         'if '      => [ 'tag' => 'if' ],
//         'elseif '  => [ 'tag' => 'elseif' ],
//         'else if ' => [ 'tag' => 'elseif' ],
//         'else-if ' => [ 'tag' => 'elseif' ],
//         'else'     => [ 'tag' => 'else' ],
//         'endif'    => [ 'tag' => 'endif' ],
//     ];

//     /**
//      * Template
//      * @var string
//      */
//     private $_template;

//     /**
//      * XML
//      * @var XML
//      */
//     private $_xml;

//     /**
//      * [fromSource description]
//      *
//      * @param  [type] $path      [description]
//      * @param  string $namespace [description]
//      *
//      * @return [type]            [description]
//      */
//     public static function fromSource($path, $namespace = '')
//     {
//         if (!strpos($path, '.twig')) {
//             $path .= '.twig';
//         }

//         $source = resource_path('views/' . $namespace . '/' . $path);

//         // $template is a filepath ending with `twig`
//         $template = file_get_contents($source);

//         return new self($template);
//     }

//     /**
//      * Constructor
//      */
//     public function __construct($template = '')
//     {
//         $this->_result = $template;
//         $this->_template = $template;

//         //
//         $this->_parsedTags = $this->parseTags();

//         // Convert "href='xyz'" to :href=xyz
//         $this->convertAttributes();

//         // Convert {% if .. %} to v-if=".."
//         $this->convertConditionals();

//         // Convert {% for .. %} to v-for=".."
//         $this->convertLoops();

//         // Convert {% include .. %} to <Component />
//         $this->convertIncludes();
//     }

//     /**
//      * Return HTML
//      *
//      * @return [type] [description]
//      */
//     public function parse()
//     {
//         return $this->_result;
//     }

//     /**
//      * Convert Attributes
//      *
//      * @return void
//      */
//     private function convertAttributes()
//     {
//         // Update all attributes
//         $attributes = $this->_attributesHtml;
//         foreach ($attributes as $attribute) {
//             foreach ($this->qp->find('[' . $attribute . '*="{{"]') as $item) {
//                 // Set new attribute
//                 $value = $item->attr($attribute);
//                 $item->attr(':' . $attribute, $this->removeTags($value));

//                 // Remove old attribute
//                 $item->removeAttr($attribute);
//             }
//         }

//         $this->_result = $this->qp->html();
//     }

//     /**
//      * Convert Conditionals
//      *
//      * @return void
//      */
//     private function convertConditionals()
//     {
//         // Check for conditionals
//         foreach ($this->qp->find('if') as $item) {
//             $condition = $item->attr('condition');

//             // Get child
//             $child = $item->children()->first();

//             // Apply
//             $attributeValue = $condition;
//             $child->attr(':v-if', $attributeValue);

//             // Remove the for loop
//             $child->unwrap();
//         }

//         $this->_result = $this->qp->html();
//     }

//     /**
//      * Convert Loops
//      *
//      * @return void
//      */
//     private function convertLoops()
//     {
//         // Apply for loops
//         foreach ($this->qp->find('for') as $item) {
//             $iterator = $item->attr('iterator');
//             preg_match('#([^ ]+) in (.*)$#U', $iterator, $matches);
//             list($original, $model, $collection) = $matches;

//             // Get child
//             $child = $item->children()->first();

//             // Apply
//             $attributeValue = "($model, index) of $collection";
//             $child->attr('v-for', $attributeValue);
//             $child->attr('v-bind:key', "index");

//             // Remove the for loop
//             $child->unwrap();
//         }

//         $this->_result = $this->qp->html();
//     }

//     /**
//      * Convert Includes
//      *
//      * @return void
//      */
//     private function convertIncludes()
//     {
//         // Apply for loops
//         foreach ($this->qp->find('include') as $item) {
//             $component = $item->attr('component');
//             $attributes = $item->attr();
//             array_shift($attributes);

//             // Add attributes
//             $item->after('<' . $component . ' />');

//             foreach ($attributes as $key => $value) {
//                 $item->next()->attr($key, $value);
//             }

//             // Apply
//             // $attributeValue = "($model, index) in $collection";
//             // $child->attr(':v-for', $attributeValue);

//             $item->remove();
//         }

//         $this->_result = $this->qp->html();
//     }

//     /**
//      * [parseTags description]
//      *
//      * @param  string
//      * @return void
//      */
//     private function parseTags()
//     {
//         $pattern = '#{% (.*) %}#Usm';
//         $subject = $this->_result;
//         $matches;
//         $flags = null;

//         // Find all {% ... %} tags
//         preg_match_all($pattern, $subject, $matches, $flags);

//         // Loop through found tags like {% for ... %} and
//         // convert them to HTML elements
//         foreach ($matches[0] as $index => $match) {
//             $innerMatch = $matches[1][$index];
//             $tag = $this->identifyTag($innerMatch);
//             $value = trim(str_replace($tag, '', $innerMatch));

//             // Convert tags to HTML elements
//             $this->convertToHtml($tag, $match, $value);
//         }

//         // Create XML
//         $this->_xml = @simplexml_load_string($this->_result);
//         $this->qp = qp($this->_xml);
//     }

//     /**
//      * [identifyTag description]
//      *
//      * @param  [type] $tag [description]
//      *
//      * @return [type]      [description]
//      */
//     private function identifyTag($tag)
//     {
//         // Search for tags
//         foreach ($this->_tagsHtml as $key => $value) {
//             if (substr($tag, 0, strlen($key)) === $key) {
//                 return isset($value['convert'])
//                     ? $value['convert']
//                     : $value['tag'];
//             }
//         }

//         return '';
//     }

//     /**
//      * Convert to HTML tags
//      *
//      * @return void
//      */
//     private function convertToHtml($tag, $search, $attributeValue = '')
//     {
//         if ($tag === 'endfor') {
//             $this->_result = str_replace($search, '</for>', $this->_result);
//         }
//         else if ($tag === 'for') {
//             $this->_result = str_replace($search, '<for iterator="' . $attributeValue . '">', $this->_result);
//         }
//         else if ($tag === 'if') {
//             $this->_result = str_replace($search, '<if condition="' . $attributeValue . '">', $this->_result);
//         }
//         // else if ($tag === 'elseif' || $tag === 'else if' || $tag === 'else-if') {
//         //     $this->_result = str_replace($search, '<else-if condition="' . $attributeValue . '">', $this->_result);
//         // }
//         // else if ($tag === 'else') {
//         //     $this->_result = str_replace($search, '<else>', $this->_result);
//         // }
//         else if ($tag === 'endif') {
//             $this->_result = str_replace($search, '</if>', $this->_result);
//         }
//         else if ($tag === 'include') {
//             preg_match('#(\'|\")((.*)\/?)(\.twig)?(\'|\")#U', $attributeValue, $matches);
//             $matches = array_slice($matches, 2, -2);
//             $filepath = str_replace('.twig', '', $matches[0]);
//             $filepath = preg_replace('#[^a-zA-Z0-9\/]#U', '', $filepath);
//             $parts = explode('/', $filepath);
//             $component = implode('', array_map('ucfirst', $parts));

//             // Attributes
//             $attributes = '';
//             $with = $this->stringBetween($attributeValue, '{', '}');
//             preg_match_all('#[ \n](.*)\:(.*)[,\n]#Us', $with, $matches);

//             // Iterate
//             if (count($matches[0])) {
//                 for ($i = 0; $i < count($matches[0]); $i++) {
//                     $key = trim($matches[1][$i]);
//                     $value = str_replace('"', '\'', trim($matches[2][$i]));

//                     if ($key && $value) {
//                         $attributes .= ':' . $key . '="' . $value . '" ';
//                     }
//                 }
//             }

//             $this->_result = str_replace($search, '<include component="' . $component . '" ' . $attributes . ' />', $this->_result);
//         }
//     }

//     /**
//      * Remove tags
//      *
//      * @param  string $str
//      * @return string
//      */
//     private function removeTags($str) {
//         return trim(preg_replace('#({{|}})#Um', '', $str));
//     }

//     /**
//      * [stringBetween description]
//      *
//      * @param  [type] $start [description]
//      * @param  [type] $end   [description]
//      *
//      * @return [type]        [description]
//      */
//     private function stringBetween($string, $start, $end) {
//         $string = ' ' . $string;
//         $ini = strpos($string, $start);
//         if ($ini == 0) return '';
//         $ini += strlen($start);
//         $len = strpos($string, $end, $ini) - $ini;
//         return substr($string, $ini, $len);
//     }

// }
