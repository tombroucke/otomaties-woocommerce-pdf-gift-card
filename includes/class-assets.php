<?php
namespace Otomaties\WooCommerce\Gift_Card;

use \Illuminate\Support\Collection;

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 */

class Assets
{

    private $entryPointsPath = '';
    private $publicUrl = '';
    private $bundle = null;

    public function __construct()
    {
        $this->entryPointsPath = plugin_dir_path(dirname(__FILE__)) . 'public/entrypoints.json';
        $this->publicUrl = plugin_dir_url(dirname(__FILE__)) . 'public/';
    }

    /**
     * Get manifest.
     *
     * @return Collection
     */
    private function getManifest(): Collection
    {
        $path = realpath($this->entryPointsPath);
        
        if (!$path) {
            throw new \Exception('Run yarn build');
        }

        return Collection::make(
            json_decode(
                file_get_contents(
                    $this->entryPointsPath
                )
            )
        );
    }

    /**
     * Do entrypoint.
     *
     * @param string $name
     * @param string $type
     * @param object $entrypoint
     *
     * @return Collection
     */
    public function entrypoint(
        string $name,
        string $type,
        Object $entrypoint
    ): Collection {
        $entrypoint->modules = Collection::make(
            $entrypoint->$type
        );

        $hasDependencies = $type == 'js' &&
            property_exists($entrypoint, 'dependencies');

        $entrypoint->dependencies = Collection::make(
            $hasDependencies
            ? $entrypoint->dependencies
            : [],
        );

        return $entrypoint->modules->map(
            function ($module, $index) use ($type, $name, $entrypoint) {
                $name = "{$type}.{$name}.{$index}";

                $dependencies = $entrypoint->dependencies->all();

                $entrypoint->dependencies->push($name);

                return (object) [
                'name' => $name,
                'uri' => $this->publicUrl . $module,
                'deps' => $dependencies,
                ];
            }
        );
    }

    /**
     * Enqueue all assets from a bundle key.
     *
     * @param string $bundleName
     * @return Assets
     */
    public function bundle(string $bundleName)
    {
        /**
         * Filter specified bundle
         */
        $filterBundle = function ($_a, $key) use ($bundleName) {
            return $key === $bundleName;
        };

        /**
         * Prepare entrypoints
         */
        $prepEntry = function ($item, $name): object {
            $entries = [];
            if (property_exists($item, 'js')) {
                $entries['js'] = $this->entrypoint($name, 'js', $item);
            }
            if (property_exists($item, 'css')) {
                $entries['css'] = $this->entrypoint($name, 'css', $item);
            }
            return (object) $entries;
        };

        /**
         * Manifest source
         */
        $this->bundle = $this->getManifest()

        /**
         * Filter for requested bundle
         */
        ->filter($filterBundle)

        /**
         * Prepare entrypoints
         */
        ->map($prepEntry);

        return $this;
    }

    public function enqueue()
    {
        $this->enqueueCss()->enqueueJs();
        return $this;
    }

    public function enqueueJs()
    {

        /**
         * Filter out HMR assets
         */
        $filterHot = function ($entry): bool {
            return !strpos($entry->uri, 'hot-update');
        };


        $this->bundle->each(function ($entrypoint) use ($filterHot): void {
            if (property_exists($entrypoint, 'js')) {
                $entrypoint
                    ->js
                    ->filter($filterHot)
                    ->each(function ($entry) {
                        wp_enqueue_script(...[
                            $entry->name,
                            $entry->uri,
                            $entry->deps,
                            null,
                            true,
                        ]);
                    });
            }
        });

        return $this;
    }

    public function enqueueCss()
    {

        /**
         * Filter out HMR assets
         */
        $filterHot = function ($entry): bool {
            return !strpos($entry->uri, 'hot-update');
        };

        $this->bundle->each(function ($entrypoint) use ($filterHot): void {
            if (property_exists($entrypoint, 'css')) {
                $entrypoint
                    ->css
                    ->filter($filterHot)
                    ->each(function ($entry) {
                        wp_enqueue_style(...[
                            $entry->name,
                            $entry->uri,
                            $entry->deps,
                            null,
                        ]);
                    });
            }
        });

        return $this;
    }
    
    public function localize($name, $object)
    {

        /**
         * Filter out HMR assets
         */
        $filterHot = function ($entry): bool {
            return !strpos($entry->uri, 'hot-update');
        };

        $this->bundle->each(function ($entrypoint) use ($filterHot, $name, $object): void {
            if (property_exists($entrypoint, 'js')) {
                $script = $entrypoint
                    ->js
                    ->filter($filterHot)
                    ->last();
                
                if ($script) {
                    wp_localize_script($script->name, $name, $object);
                }
            }
        });

        return $this;
    }
}
