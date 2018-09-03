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

return [

    /*
    |--------------------------------------------------------------------------
    | Base type
    |--------------------------------------------------------------------------
    |
    | Here you may change the base type for all models.
    |
    | When starting the retrieval process of an instance's template(s),
    | this type will be searched for.
    |
    | Every type (including this one) can have a default view or value.
    |
    */

    'base-type' => 'base',

    /*
    |--------------------------------------------------------------------------
    | Default Template Types
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default views or values for template types.
    |
    | This can be overwritten on any level in the hierarchy. An overwrite can
    | be a specific link between a template and a model instance, or a
    | wildcard link between a template and a model.
    |
    | When no link can be found for a model instance, this view or value
    | will be loaded as the template.
    |
    | This will happen when calling methods like:
    | - $model->pigeonGeneratePdf() TODO
    | - $model->pigeonGetTemplateParts() TODO
    |
    | If the default is a view, it can still have a recursive pattern
    |
    */

    'default-types' => [
        'base' => [
            'content-type'  => 'view',
            'content' => 'base',
        ],
        'profilepicture' => [
            'content-type' => 'image-url',
            'content' => 'http://via.placeholder.com/595x842/adf/fff?text=Default+background',
        ],
        'logo' => [
            'content-type' => 'data-image',
            'content' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAGQAAABkCAYAAABw4pVUAAAAnElEQVR42u3RAQ0AAAgDoL9/aK3hHFSgyUw4o0KEIEQIQoQgRAhChAgRghAhCBGCECEIEYIQhAhBiBCECEGIEIQgRAhChCBECEKEIAQhQhAiBCFCECIEIQgRghAhCBGCECEIQYgQhAhBiBCECEEIQoQgRAhChCBECEIQIgQhQhAiBCFCEIIQIQgRghAhCBGCECFChCBECEKEIOS7BU5Hx50BmcQaAAAAAElFTkSuQmCC', // A 100x100px blue png
        ],
        'content' => [
            'content-type' => 'blade',
            'content' => '<h1>Blade example: changed 7 content</h1><p>Hi {{ $name }}</p><p>This is an example to show that the renders can respect the blade engine and resolve data injected to the render method</p><div style="background: rgba(0, 255, 0, 0.3); width: 50%;">@template("html")</div>',
        ],
        'overlay' => [
            'content-type' => 'blade',
            'content' => '<div style="opacity: .8; width: 90%;"><h3 style="background: blue;"><br />Overlay<br />&nbsp;</h3>@template("overlay")</div>',
        ],
        'html' => [
            'content-type' => 'html',
            'content' => '<div><h1>HTML Example</h1></div>',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Views namespace
    |--------------------------------------------------------------------------
    |
    | Here you may change the namespace for the views.
    |
    */

    'namespace' => 'pigeon-templates',

    /*
    |--------------------------------------------------------------------------
    | Max Depth
    |--------------------------------------------------------------------------
    |
    | Here you may change the maximum depth templates can reach.
    |
    | Be careful with high numbers as templates can be introduced recursively
    | and thus introduce an infinite loop.
    |
    | Also, a high number of legitimate recursions will have significant
    | impact on performance and resources
    |
    */

    'max-depth' => 15,

    /*
    |--------------------------------------------------------------------------
    | Template Model
    |--------------------------------------------------------------------------
    |
    | Here you may change the Template Model and/or the Pivot Model.
    |
    | Note that any model/pivot you choose here needs to extend the original
    | model and/or pivot
    |
    */

    'model' => \PeterDeKok\PigeonTemplates\PigeonTemplate::class,
    'pivot' => \PeterDeKok\PigeonTemplates\PigeonTemplateLink::class,

    /*
    |--------------------------------------------------------------------------
    | Auth Manager
    |--------------------------------------------------------------------------
    |
    | Here you may change the authorization manager used in your application
    |
    | Out of the box only laravel Auth is configured, but a custom manager can
    | be configured like this:
    |
    | 'auth' => [
    |     'provider' => 'custom',
    |     'method'   => 'getUserMethodName',
    |     'model'    => \App\Models\User::class,
    | ]
    |
    | This however does assume that the User model is an Eloquent Model.
    | If this is not the case, the template model needs to be customised
    | manually.
    |
    */

    'auth' => [
        'provider' => Auth::class,
    ],

    /*
     |--------------------------------------------------------------------------
     | Ingore errors
     |--------------------------------------------------------------------------
     |
     | While rendering errors CAN be silenced.
     |
     | This is to make sure that it is possible to render a (partially) successful render, even though some dependency
     | in your data, or an error in a template part does not interfere with the rest of the render.
     |
     | This might be especially useful when rendering asynchronous.
     |
     | ---
     | Use this wisely!
     | ---
     |
     | This feature will make it harder to spot these mistakes (as it can result in only missing a single pixel).
     |
     | However if you use proper log monitoring (you really should!), this might be ok, wanted or even the best course of action.
     |
     | It all depends on your specific implementation AND business strategies. Use your own discretion to decide.
     |
     */

    'ignore-errors' => true,

];
