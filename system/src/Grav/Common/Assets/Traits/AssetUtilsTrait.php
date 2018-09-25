<?php
/**
 * @package    Grav.Common.Assets.Traits
 *
 * @copyright  Copyright (C) 2015 - 2018 Trilby Media, LLC. All rights reserved.
 * @license    MIT License; see LICENSE file for details.
 */

namespace Grav\Common\Assets\Traits;

use Grav\Common\Grav;
use Grav\Common\Utils;

trait AssetUtilsTrait
{
    /**
     * Determine whether a link is local or remote.
     * Understands both "http://" and "https://" as well as protocol agnostic links "//"
     *
     * @param  string $link
     * @return bool
     */
    public static function isRemoteLink($link)
    {
        $base = Grav::instance()['uri']->rootUrl(true);

        // sanity check for local URLs with absolute URL's enabled
        if (Utils::startsWith($link, $base)) {
            return false;
        }

        return ('http://' === substr($link, 0, 7) || 'https://' === substr($link, 0, 8) || '//' === substr($link, 0,
                2));
    }

    /**
     * Download and concatenate the content of several links.
     *
     * @param  array $links
     * @param  bool  $css
     *
     * @return string
     */
    protected function gatherLinks(array $links, $css = true)
    {
        $buffer = '';


        foreach ($links as $asset) {
            $relative_dir = '';
            $local = true;

            $link = $asset->getAsset();
            $relative_path = $link;

            if ($this->isRemoteLink($link)) {
                $local = false;
                if ('//' === substr($link, 0, 2)) {
                    $link = 'http:' . $link;
                }
            } else {
                // Fix to remove relative dir if grav is in one
                if (($this->base_url != '/') && (strpos($this->base_url, $link) == 0)) {
                    $base_url = '#' . preg_quote($this->base_url, '#') . '#';
                    $relative_path = ltrim(preg_replace($base_url, '/', $link, 1), '/');
                }

                $relative_dir = dirname($relative_path);
                $link = ROOT_DIR . $relative_path;
            }

            $file = ($this->fetch_command instanceof Closure) ? @$this->fetch_command->__invoke($link) : @file_get_contents($link);

            // No file found, skip it...
            if ($file === false) {
                continue;
            }

            // Double check last character being
            if (!$css) {
                $file = rtrim($file, ' ;') . ';';
            }

            // If this is CSS + the file is local + rewrite enabled
            if ($css && $local && $this->css_rewrite) {
                $file = $this->cssRewrite($file, $relative_dir);
            }

            $file = rtrim($file) . PHP_EOL;
            $buffer .= $file;
        }

        // Pull out @imports and move to top
        if ($css) {
            $buffer = $this->moveImports($buffer);
        }

        return $buffer;
    }

    /**
     * Moves @import statements to the top of the file per the CSS specification
     *
     * @param  string $file the file containing the combined CSS files
     *
     * @return string       the modified file with any @imports at the top of the file
     */
    protected function moveImports($file)
    {
        $imports = [];

        $file = preg_replace_callback(self::CSS_IMPORT_REGEX, function ($matches) {
            $imports[] = $matches[0];

            return '';
        }, $file);

        return implode("\n", $imports) . "\n\n" . $file;
    }

    /**
     *
     * Build an HTML attribute string from an array.
     *
     * @param  array $attributes
     *
     * @return string
     */
    protected function renderAttributes()
    {
        $html = '';
        $no_key = ['loading'];

        foreach ($this->attributes as $key => $value) {
            if (is_numeric($key)) {
                $key = $value;
            }
            if (is_array($value)) {
                $value = implode(' ', $value);
            }

            if (in_array($key, $no_key)) {
                $element = htmlentities($value, ENT_QUOTES, 'UTF-8', false);
            } else {
                $element = $key . '="' . htmlentities($value, ENT_QUOTES, 'UTF-8', false) . '"';
            }

            $html .= ' ' . $element;
        }

        return $html;
    }

    /**
     * Render Querystring
     *
     * @return string
     */
    protected function renderQueryString($asset = null)
    {
        $querystring = '';

        $asset = $asset ?? $this->asset;

        if (!empty($this->query)) {
            if (Utils::contains($asset, '?')) {
                $querystring .=  '&' . $this->query;
            } else {
                $querystring .= '?' . $this->query;
            }
        }

        if ($this->timestamp) {
            if (Utils::contains($asset, '?') || $querystring) {
                $querystring .=  '&' . $this->timestamp;
            } else {
                $querystring .= '?' . $this->timestamp;
            }
        }

        return $querystring;
    }
}
