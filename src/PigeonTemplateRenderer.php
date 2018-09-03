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

use Exception;
use Illuminate\Contracts\View\Factory;
use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Compilers\BladeCompiler;
use PeterDeKok\PigeonTemplates\Exceptions\PigeonConfigurationException;
use PeterDeKok\PigeonTemplates\Exceptions\PigeonInvalidContentTypeException;
use PeterDeKok\PigeonTemplates\Exceptions\PigeonMaxDepthExceededException;
use PeterDeKok\PigeonTemplates\Exceptions\PigeonModelMissingTraitException;
use PeterDeKok\PigeonTemplates\Exceptions\PigeonTemplateNotFoundException;
use Psr\Log\LoggerInterface;

class PigeonTemplateRenderer {

    /**
     * This method implements a heavily adapted version of the singular design pattern.
     *
     * For every (base) render a new 'singular' is created.
     * This is done to start 'fresh' if there are ever multiple renders to be created in one request.
     *
     * Any nested renders (through the blade extension @template) will be performed on the 'singular' instance.
     *
     * @var static $singular
     */
    protected static $singular;
    /**
     * While rendering errors CAN be silenced.
     *
     * This is to make sure that it is possible to render a (partially) successful render, even though some dependency
     * in your data, or an error in a template part does not interfere with the rest of the render.
     *
     * ---
     * Use this wisely!
     * ---
     *
     * This feature will make it harder to spot these mistakes (as it can result in only missing a single pixel).
     *
     * However if you use proper log monitoring (you really should!), this might be ok, wanted or even the best course of action.
     *
     * This field is used (and injected into) to enable you to use your own logging implementation as long as it adheres to
     * the PSR-3 standards.
     *
     * @var \Psr\Log\LoggerInterface $logger
     */
    protected $logger;
    /**
     * The view factory to render view based templates
     *
     * @var \Illuminate\Contracts\View\Factory
     */
    protected $view;
    /**
     * Holds the Filesystem manager
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $file;
    /**
     * Holds an instance of the BladeCompiler
     *
     * @var \Illuminate\View\Compilers\BladeCompiler
     */
    protected $bladeCompiler;
    /**
     * The base model is the model this render is created for.
     *
     * @var \Illuminate\Database\Eloquent\Model|\PeterDeKok\PigeonTemplates\PigeonTemplatesContract $baseModel
     */
    protected $baseModel;
    /**
     * The data to be injected into the blade engine.
     *
     * @var array $data
     */
    protected $data;
    /**
     * The base template type is the template type of this render.
     * A model can potentially have multiple template types associated.
     *
     * @var string $baseTemplateType
     */
    protected $baseTemplateType;
    /**
     * The stack holds a history of all parent template parts.
     * This is mainly used to detect infinite loops.
     *
     * @var string[] $stack
     */
    protected $stack = [];

    public function __construct(LoggerInterface $logger, Factory $view, Filesystem $file, BladeCompiler $bladeCompiler) {
        $this->logger = $logger;
        $this->view = $view;
        $this->file = $file;
        $this->bladeCompiler = $bladeCompiler;
    }

    /**
     * Render the template
     *
     * Renders the pigeon-template and recursively all child pigeon-templates.
     * The model instance this method is called from will act as the base of the entire template hierarchy.
     *
     * The data injected into the template is set through this method. The same data will be used for ALL following calls
     * to any child template parts. So any child views loaded with the @template directive can also make use of the same data.
     *
     * @param \Illuminate\Database\Eloquent\Model|\PeterDeKok\PigeonTemplates\PigeonTemplatesContract $model
     * @param array|null $data
     * @param string|null $templateType
     *
     * @return string
     * @throws \Exception
     */
    public static function render(PigeonTemplatesContract $model, array $data = null, string $templateType = null) {
        static::$singular = $renderer = resolve(static::class);

        if (is_null($data))
            $data = [str_singular($model->getTable()) => $model];

        $renderer->setBaseModel($model);
        $renderer->setData($data);
        $renderer->setBaseTemplateType($templateType);

        $compiled = $renderer->renderPigeon();

        // I choose to NOT keep these template parts, as this could easily populate too much diskspace.
        // This will have some impact on speed of course.
        // I might reconsider at a later time, but for me (at this point in time) the diskspace issue is a bigger problem
        $renderer->deleteCachedPigeonViews();

        return $compiled;
    }

    /**
     * Render a nested template part
     *
     * Renders the pigeon-template and recursively all child pigeon-templates.
     *
     * The @template blade directive is a wrapper for this method.
     *
     * @param string $templateType
     *
     * @return string
     * @throws \Exception
     */
    public static function renderNested(string $templateType) {
        return static::$singular->renderPigeon($templateType);
    }

    /**
     * @param \Illuminate\Database\Eloquent\Model|\PeterDeKok\PigeonTemplates\PigeonTemplatesContract $baseModel
     */
    public function setBaseModel(PigeonTemplatesContract $baseModel) {
        $this->baseModel = $baseModel;
    }

    /**
     * @param array $data
     */
    public function setData(array $data) {
        $this->data = $data;
    }

    /**
     *
     * @param string|null $baseTemplateType
     *
     * @throws \PeterDeKok\PigeonTemplates\Exceptions\PigeonConfigurationException
     */
    public function setBaseTemplateType(string $baseTemplateType = null) {
        $this->baseTemplateType = $this->getTemplateType($baseTemplateType);
    }

    /**
     * Render the template
     *
     * @param string|null $templateType
     * @param \Illuminate\Database\Eloquent\Model|\PeterDeKok\PigeonTemplates\PigeonTemplatesContract $model
     *
     * @return string
     * @throws \Exception
     */
    public function renderPigeon(string $templateType = null, PigeonTemplatesContract $model = null) {
        try {
            $model = $model ?? $this->baseModel;

            $this->pushStack($templateType);

            $contentType = $this->getContentType(); // TODO delete
            dump("Template is of type [{$templateType} => {$contentType}]"); // TODO delete

            $compiled = $this->renderPigeonContent($model);

            $this->popStack();
        } catch (Exception $exception) {
            $this->logger->error($exception->getMessage(), ['exception' => $exception]);

            dump($exception->getMessage()); // TODO Remove this

            if (config('pigeon-templates.ignore-errors', false) !== true)
                throw $exception;
        }

        return $compiled ?? '';
    }

    /**
     * Recursively retrieves the first available Template.
     *
     * Retrieves the template for the given templateType starting at the bottom of the pigeon-templates pecking order.
     * The pecking order should be specified by the pigeonParent() method on each model in the pecking order hierarchy.
     *
     * @param \Illuminate\Database\Eloquent\Model|\PeterDeKok\PigeonTemplates\PigeonTemplatesContract $model
     *
     * @return \PeterDeKok\PigeonTemplates\PigeonTemplate
     * @throws \PeterDeKok\PigeonTemplates\Exceptions\PigeonModelMissingTraitException
     */
    public function getPigeonTemplate(PigeonTemplatesContract $model) {
        if (!array_key_exists(HasPigeonTemplates::class, trait_uses_recursive($model)))
            throw new PigeonModelMissingTraitException($model);

        $templateType = end($this->stack);

        $template = $model->templates()
            ->where('type', $templateType)
            ->orderBy('pigeon_templatables.updated_at', 'desc')
            ->first();

        if (!is_null($template))
            return $template;

        if (is_null($parent = $model->pigeonParent()))
            return call_user_func([config('pigeon-templates.model', PigeonTemplate::class), 'getDefault'], $templateType);

        return $this->getPigeonTemplate($parent);
    }

    /**
     * This method handles all different logic for all content-types.
     *
     * This method can be overridden to change or extend the behaviour of this logic.
     *
     * For extending, I recommend calling the overridden parent::renderPigeonContent method first.
     * By catching the PigeonInvalidContentTypeException you then know to test for the custom content-type(s).
     * Don't forget to rethrow the exception if the content-type is still invalid.
     *
     * For changing, I recommend Testing for the custom content-type(s) first (and returning early in case of a match).
     * And only calling the parent::renderPigeonContent if the content-type is not a match with the custom one(s).
     *
     * @param \PeterDeKok\PigeonTemplates\PigeonTemplatesContract $model
     *
     * @return string
     * @throws \PeterDeKok\PigeonTemplates\Exceptions\PigeonConfigurationException
     * @throws \PeterDeKok\PigeonTemplates\Exceptions\PigeonInvalidContentTypeException
     * @throws \PeterDeKok\PigeonTemplates\Exceptions\PigeonModelMissingTraitException
     * @throws \PeterDeKok\PigeonTemplates\Exceptions\PigeonTemplateNotFoundException
     */
    protected function renderPigeonContent(PigeonTemplatesContract $model) {
        $template = $this->getPigeonTemplate($model);

        $contentType = $this->getContentType();

        if ($contentType !== 'view' && is_null($template))
            throw new PigeonTemplateNotFoundException(end($this->stack), $this->baseModel);

        $content = $template->getContent();

        switch ($contentType) {
            case 'view':
                $viewNamespace = call_user_func([config('pigeon-templates.model', PigeonTemplate::class), 'getNamespace']);

                if (!$this->view->exists($viewNamespace . '::' . $content))
                    throw new PigeonTemplateNotFoundException(end($this->stack), $this->baseModel);

                $compiled = $this->compilePigeonView($content, $this->data);

                break;
            case 'html':
                $compiled = $content;

                break;
            case 'blade':
                $view = $this->createPigeonView($template, $content);

                $compiled = $this->compilePigeonView($view, $this->data);

                break;
            case 'image-url':
            case 'data-image':
                $compiled = "<img class=\"pigeon-image\" src=\"{$content}\" />";

                break;
            case '':
            default:
                throw new PigeonInvalidContentTypeException($contentType, end($this->stack));

                break;
        }

        return $compiled ?? '';
    }

    /**
     * Renders the view to an HTML page.
     *
     * @param string $viewHint
     * @param array $data
     *
     * @return string
     */
    protected function compilePigeonView(string $viewHint, array $data) {
        $viewNamespace = call_user_func([config('pigeon-templates.model', PigeonTemplate::class), 'getNamespace']);

        return $this->view->make("{$viewNamespace}::{$viewHint}", $data)->render();
    }

    /**
     * Creates a view from raw HTML/Blade syntax, so any data can be included.
     *
     * @param \PeterDeKok\PigeonTemplates\PigeonTemplate $template
     * @param string $content
     *
     * @return string
     */
    protected function createPigeonView(PigeonTemplate $template, string $content) {
        $cachePath = config('view.compiled');

        if ($template->isDefault()) {
            $viewName = 'default_' . $template->type;
            $cacheSubDir = '_default';
        } else {
            $viewName = $template->getKey();
            $cacheSubDir = '_other' . DIRECTORY_SEPARATOR . $viewName;
        }

        $viewHint = implode('.', [
            'pigeon-cache',
            $cacheSubDir,
            $viewName,
        ]);

        $dir = resource_path(implode(DIRECTORY_SEPARATOR, [
            'views',
            'vendor',
            'pigeon-templates',
            'pigeon-cache',
            $cacheSubDir,
        ]));

        $path = $dir . DIRECTORY_SEPARATOR . $viewName . '.blade.php';

        $timestamp = $template->getAttribute($template->getUpdatedAtColumn())->timestamp;

        if ($this->file->exists($path) && $this->file->lastModified($path) >= $timestamp) {
            dump('view already exists');
            dump('view last modified [' . $this->file->lastModified($path) . ']');
            dump('template last modified [' . $timestamp . ']');

            return $viewHint;
        }

        dump('creating view');

        $compiled = (new BladeCompiler($this->file, $cachePath))->compileString($content);

        if (!$this->file->exists($dir))
            $this->file->makeDirectory($dir, 493, true);

        $this->file->put($path, $compiled);

        // The updated timestamp of the file is cached by PHP (filemtime()... slightly annoying)
        clearstatcache(true, $path);

        dump('view last modified [' . $this->file->lastModified($path) . ']');
        dump('template last modified [' . $timestamp . ']');

        return $viewHint;
    }

    /**
     * Deletes the created blade views.
     *
     * They are no longer necessary as they are already cached by the Laravel view cache.
     *
     * @param bool $includingDefault
     *
     * @return bool
     */
    public function deleteCachedPigeonViews(bool $includingDefault = false) {
        // $viewHints = explode('.', $view);
        //
        // if (reset($viewHints) !== 'pigeon-cache' || next($viewHints) === '_default')
        //     return;

        $path = resource_path(implode(DIRECTORY_SEPARATOR, [
            'views',
            'vendor',
            'pigeon-templates',
            'pigeon-cache',
        ]));

        dump($path);

        dump('deleting');

        $result = true;

        $result &= $this->file->cleanDirectory($path . DIRECTORY_SEPARATOR . '_other');

        if ($includingDefault)
            $result &= $this->file->cleanDirectory($path . DIRECTORY_SEPARATOR . '_default');

        dump($result);

        if (!$result)
            $this->logger->error('[Pigeon Templates] Could not clean pigeon-cache directory');

        return (bool) $result;
    }

    /**
     * Adds the latest template type to the stack and tests its depth.
     *
     * @param string|null $templateType
     *
     * @throws \PeterDeKok\PigeonTemplates\Exceptions\PigeonMaxDepthExceededException
     * @throws \PeterDeKok\PigeonTemplates\Exceptions\PigeonConfigurationException
     */
    protected function pushStack(string &$templateType = null) {
        $this->stack[] = $this->getTemplateType($templateType);

        $this->testStackDepth();
    }

    /**
     * Removes the last template type from the stack
     */
    protected function popStack() {
        array_pop($this->stack);
    }

    /**
     * Test if the stack depth is still within bounds.
     *
     * This is to detect infinite loops and discourage overly nested templates as they will slow down the process considerably.
     *
     * @throws \PeterDeKok\PigeonTemplates\Exceptions\PigeonMaxDepthExceededException
     */
    protected function testStackDepth() {
        if (count($this->stack ?? []) > config('pigeon-templates.max-depth', 15))
            throw new PigeonMaxDepthExceededException($this->stack);
    }

    /**
     * Returns the input or if no input given, retrieves the default template type.
     *
     * @param string|null $templateType
     *
     * @return string
     * @throws \PeterDeKok\PigeonTemplates\Exceptions\PigeonConfigurationException
     */
    public function getTemplateType(string &$templateType = null) {
        if (empty($templateType) && empty($templateType = config('pigeon-templates.base-type', 'base')))
            throw new PigeonConfigurationException("pigeon-templates.base-type");

        return $templateType;
    }

    /**
     * Returns the content type of the template's type
     *
     * @return string;
     * @throws \PeterDeKok\PigeonTemplates\Exceptions\PigeonConfigurationException
     */
    public function getContentType() {
        return static::getPigeonContentConfig(end($this->stack), 'content-type');
    }

    /**
     * @param string $templateType
     * @param string $key
     *
     * @return string
     * @throws \PeterDeKok\PigeonTemplates\Exceptions\PigeonConfigurationException
     */
    public static function getPigeonContentConfig(string $templateType, string $key) {
        if (is_null($config = config('pigeon-templates.default-types')) || !is_array($config))
            throw new PigeonConfigurationException('pigeon-templates.default-types');

        if (!array_key_exists($templateType, $config) || !is_array($templateTypeConfig = $config[$templateType]))
            throw new PigeonConfigurationException("pigeon-templates.default-types.{$templateType}");

        if (!array_key_exists($key, $templateTypeConfig))
            throw new PigeonConfigurationException("pigeon-templates.default-types.{$templateType}.{$key}");

        return $templateTypeConfig[$key];
    }
}