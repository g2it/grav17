<?php
/**
 * @package    Grav.Common.Assets
 *
 * @copyright  Copyright (C) 2015 - 2018 Trilby Media, LLC. All rights reserved.
 * @license    MIT License; see LICENSE file for details.
 */

namespace Grav\Common\Assets;

use Grav\Common\Assets\Traits\AssetUtilsTrait;
use Grav\Common\Grav;
use Grav\Common\Uri;
use Grav\Common\Utils;
use Grav\Framework\Object\PropertyObject;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;

abstract class BaseAsset extends PropertyObject
{
    use AssetUtilsTrait;

    const CSS_ASSET = true;
    const JS_ASSET = false;

    /** @const Regex to match CSS import content */
    const CSS_IMPORT_REGEX = '{@import(.*?);}';

    protected $asset;

    protected $asset_type;
    protected $order;
    protected $group;
    protected $position;
    protected $priority;
    protected $attributes = [];

    protected $base_url;
    protected $timestamp;
    protected $modified;
    protected $remote;
    protected $query = '';

    // Needed for 'inline' support
    protected $fetch_command;
    protected $css_rewrite = false;
    protected $css_minify = false;

    abstract function render();

    public function __construct(array $elements = [], $key = null)
    {
        $base_config = [
            'group' => 'head',
            'position' => 'pipeline',
            'priority' => 10,
            'modified' => null,
            'asset' => null
        ];

        // Merge base defaults
        $elements = array_merge($base_config, $elements);

        parent::__construct($elements, $key);
    }

    public function init($asset, $options)
    {
        $config = Grav::instance()['config'];
        $uri = Grav::instance()['uri'];

        // set attributes
        foreach ($options as $key => $value) {
            if ($this->hasProperty($key)) {
                $this->setProperty($key, $value);
            } else {
                $this->attributes[$key] = $value;
            }
        }

        // Do some special stuff for CSS/JS (not inline)
        if (!Utils::startsWith($this->getType(), 'inline')) {
            $this->base_url = rtrim($uri->rootUrl($config->get('system.absolute_urls')), '/') . '/';
            $this->remote = $this->isRemoteLink($asset);

            // Move this to render?
            if (!$this->remote) {

                $asset_parts = parse_url($asset);
                if (isset($asset_parts['query'])) {
                    $this->query = $asset_parts['query'];
                    unset($asset_parts['query']);
                    $asset = Uri::buildUrl($asset_parts);
                }

                $this->modified = $this->getLastModificationTime($asset);
                $asset = $this->buildLocalLink($asset);
            }
        }

        $this->asset = $asset;

        return $this;
    }

    public function getAsset()
    {
        return $this->asset;
    }

    public function getRemote()
    {
        return $this->remote;
    }


    /**
     *
     * Get the last modification time of asset
     *
     * @param  string $asset    the asset string reference
     *
     * @return string           the last modifcation time or false on error
     */
    protected function getLastModificationTime($asset)
    {
        $file = GRAV_ROOT . $asset;
        if (Grav::instance()['locator']->isStream($asset)) {
            $file = $this->buildLocalLink($asset, true);
        }

        return file_exists($file) ? filemtime($file) : false;
    }

    /**
     *
     * Build local links including grav asset shortcodes
     *
     * @param  string $asset    the asset string reference
     * @param  bool   $absolute build absolute asset link
     *
     * @return string           the final link url to the asset
     */
    protected function buildLocalLink($asset, $absolute = false)
    {
        /** @var UniformResourceLocator $locator */
        $locator = Grav::instance()['locator'];

        if ($locator->isStream($asset)) {
            $asset = $locator->findResource($asset, $absolute);
        }

        $uri = $absolute ? $asset : $this->base_url . ltrim($asset, '/');


        return $asset ? $uri : false;
    }




    /**
     * Implements JsonSerializable interface.
     *
     * @return array
     */
    public function jsonSerialize()
    {
        return ['type' => $this->getType(), 'elements' => $this->getElements()];
    }
}
