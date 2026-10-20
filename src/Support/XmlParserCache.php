<?php

declare(strict_types=1);

namespace Kodhe\Framework\Xmlrpc\Support;

/**
 * XML Parser cache for performance optimization
 */
class XmlParserCache
{
    /**
     * @var array
     */
    private static $parsers = [];

    /**
     * Get a cached XML parser or create a new one
     *
     * @param string $encoding
     * @return resource
     */
    public static function getParser(string $encoding = 'UTF-8')
    {
        $key = $encoding;

        if (!isset(self::$parsers[$key])) {
            $parser = xml_parser_create($encoding);

            // Disable external entity resolution to mitigate XXE attacks.
            // Available since PHP 8.2; on older versions the expat parser
            // does not fetch external entities by default.
            if (PHP_VERSION_ID >= 80200) {
                xml_set_external_entity_ref_handler($parser, static fn () => false);
            }

            self::$parsers[$key] = $parser;
        }

        return self::$parsers[$key];
    }

    /**
     * Reset parser state for reuse
     *
     * @param resource $parser
     * @return void
     */
    public static function resetParser($parser): void
    {
        xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, true);
    }

    /**
     * Clear all cached parsers
     *
     * @return void
     */
    public static function clear(): void
    {
        foreach (self::$parsers as $parser) {
            xml_parser_free($parser);
        }
        self::$parsers = [];
    }

    /**
     * Destructor - cleanup
     */
    public function __destruct()
    {
        self::clear();
    }
}
