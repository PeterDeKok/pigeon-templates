<?php

/*
 * PeterDeKok/PigeonTemplates
 *
 * Copyright (C) 2018 peterdekok.nl
 *
 * Peter De Kok <info@peterdekok.nl>
 * <https://package.peterdekok.nl/pigeon-templates/>
 *
 * This program is free software: you can redistribute it and/or modify it under the terms of
 * the GNU General Public License as published by the Free Software Foundation,
 * either version 3 of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
 * without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <https://www.gnu.org/licenses/>.
 */

namespace PeterDeKok\PigeonTemplates;

use Blade;
use File;
use Illuminate\Support\ServiceProvider;
use PeterDeKok\PigeonTemplates\Exceptions\PigeonConfigurationException;

class PigeonTemplatesServiceProvider extends ServiceProvider {

    /**
     * Bootstrap services.
     *
     * @return void
     * @throws \PeterDeKok\PigeonTemplates\Exceptions\PigeonConfigurationException
     */
    public function boot() {
        $this->registerConfig();

        $this->registerMigrations();

        $this->registerViews();

        $this->extendBlade();

        $this->registerCommands();
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register() {}

    /**
     * Register PigeonTemplates' config files.
     *
     * @return void
     * @throws \PeterDeKok\PigeonTemplates\Exceptions\PigeonConfigurationException
     */
    protected function registerConfig() {
        $packagePath = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'config';

        $this->mergeConfigFrom($packagePath . DIRECTORY_SEPARATOR . 'pigeon-templates.php', 'pigeon-templates');

        $this->testConfig();

        if ($this->app->runningInConsole()) {
            $this->publishes([
                $packagePath . DIRECTORY_SEPARATOR . 'pigeon-templates.php' => config_path('pigeon-templates.php'),
            ], 'pigeon-templates-config');
        }
    }

    /**
     * @throws \PeterDeKok\PigeonTemplates\Exceptions\PigeonConfigurationException
     */
    protected function testConfig() {
        if (is_null($templateType = config('pigeon-templates.base-type')) || empty($templateType) || !is_string($templateType))
            throw new PigeonConfigurationException('pigeon-templates.base-type');

        if (is_null($configDefaultTypes = config('pigeon-templates.default-types')) || !is_array($configDefaultTypes))
            throw new PigeonConfigurationException('pigeon-templates.default-types');

        foreach ($configDefaultTypes as $configDefaultTypeName => $configDefaultType) {
            if (!is_array($configDefaultType))
                throw new PigeonConfigurationException("pigeon-templates.default-types.{$configDefaultTypeName}");

            foreach (['content', 'content-type'] as $key) {
                if (!array_key_exists($key, $configDefaultType))
                    throw new PigeonConfigurationException("pigeon-templates.default-types.{$configDefaultTypeName}.{$key}");
            }
        }

        $authPackage = config('pigeon-templates.auth-package', 'Auth');

        if ($authPackage === 'custom' &&
            (is_null($authMethod = config('pigeon-templates.auth.method')) || !is_callable([$authPackage, $authMethod])))
            throw new PigeonConfigurationException('pigeon-templates.auth');
    }

    /**
     * Register PigeonTemplates' migration files.
     *
     * @return void
     */
    protected function registerMigrations() {
        $packagePath =
            __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'migrations';

        if ($this->app->runningInConsole()) {
            $this->loadMigrationsFrom($packagePath);

            $this->publishes([
                $packagePath => database_path('migrations'),
            ], 'pigeon-templates-migrations');
        }
    }

    /**
     * Register PigeonTemplates' view files.
     *
     * Register views and make them accessible through the namespace, for both published and unpublished views.
     *
     * @return void
     */
    protected function registerViews() {
        $packagePath = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'views';
        $hintPath = resource_path('views' . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'pigeon-templates');

        if (!File::exists($hintPath))
            File::makeDirectory($hintPath, 493, true);

        $this->loadViewsFrom($packagePath, config('pigeon-templates.namespace', 'pigeon-templates'));

        if ($this->app->runningInConsole()) {
            $this->publishes([
                $packagePath => $hintPath,
            ], 'pigeon-templates-views');
        }
    }

    /**
     * Extend Blade to include @template directive.
     */
    protected function extendBlade() {
        Blade::directive('template', function (string $templateType) {
            $templateType = trim($templateType, '\'"');

            dump('retrieving...[' . $templateType . ']');

            $content = sprintf('\PeterDeKok\PigeonTemplates\PigeonTemplateRenderer::renderNested(\'%s\')', $templateType);

            return "<?php echo $content; ?>";
        });
    }

    /**
     * Register PigeonTemplates' commands
     */
    protected function registerCommands() {
        $this->commands([
            PigeonClearCacheCommand::class
        ]);
    }
}
