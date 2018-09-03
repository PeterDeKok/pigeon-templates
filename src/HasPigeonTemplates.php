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

/**
 * @mixin \Eloquent
 */
trait HasPigeonTemplates {

    /**
     * Get the instance one level up in the pigeon-templates pecking order.
     *
     * This method needs to be overwritten to return the correct parent in the hierarchy
     *
     * @return \Illuminate\Database\Eloquent\Model|\PeterDeKok\PigeonTemplates\HasPigeonTemplates
     */
    abstract public function pigeonParent();

    /**
     * Get the pigeon-templates directly linked to this model instance.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany|\Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function templates() {
        return $this->morphToMany(config('pigeon-templates.model', PigeonTemplate::class), 'pigeon_templatable')
            ->withTimestamps()
            ->using(config('pigeon-templates.pivot', PigeonTemplateLink::class));
    }

    /**
     * Render the template
     *
     * Renders the pigeon-template and all child pigeon-templates.
     * The model instance this method is called from will act as the base of the entire template hierarchy.
     *
     * The @template blade directive is a wrapper for this method.
     *
     * The data 'base' offset will be appended or overwritten with the model instance this method is called from.
     *
     * The data 'data' offset will be appended or overwritten to reference the data array. (recursive loop).
     * This is done so after rendering the view, any child views loaded with the @template directive can also retrieve
     * the same data.
     *
     * Within this method all logic for the content-types is called.
     *
     * @param array $data
     * @param string|null $templateType
     *
     * @return string
     * @throws \Exception
     */
    public function renderPigeon(array $data = [], string $templateType = null) {
        return PigeonTemplateRenderer::render($this, $data, $templateType);
    }
}