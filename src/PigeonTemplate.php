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

use Auth;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use PeterDeKok\PigeonTemplates\Exceptions\PigeonConfigurationException;

class PigeonTemplate extends Model {

    use SoftDeletes;
    protected $defaultTemplate = false;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'name',
        'type',
        'content',
        'thumbnail',
    ];
    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

    public function pigeonable() {
        return $this->hasMany(config('pigeon-templates.pivot', PigeonTemplateLink::class));
    }

    /**
     * A default template can be instantiated, but never saved!
     *
     * @param array $options
     *
     * @return bool
     */
    public function save(array $options = []) {
        if ($this->defaultTemplate)
            return false;

        return parent::save($options);
    }

    /**
     * Retrieve the view namespace
     *
     * @return \Illuminate\Config\Repository|mixed
     */
    public static function getNamespace() {
        return config('pigeon-templates.namespace', 'pigeon-templates');
    }

    /**
     * Retrieve the content of the template
     *
     * @return mixed
     * @throws \PeterDeKok\PigeonTemplates\Exceptions\PigeonConfigurationException
     */
    public function getContent() {
        return $this->content ?? PigeonTemplateRenderer::getPigeonContentConfig($this->type, 'content');
    }

    /**
     * @param string $templateType
     *
     * @return \Illuminate\Config\Repository|mixed
     * @throws \PeterDeKok\PigeonTemplates\Exceptions\PigeonConfigurationException
     */
    public static function getDefault(string $templateType) {
        if (!array_key_exists($templateType, $config = config('pigeon-templates.default-types')))
            throw new PigeonConfigurationException("pigeon-templates.default-types.{$templateType}");

        $template = new static;

        $template->defaultTemplate = true;
        $template->type = $templateType;
        $template->name = "Default {$templateType}";
        $template->user_id = null;
        $template->content = $config[$templateType]['content'];
        $template->thumbnail = $config['thumbnail'] ?? null;

        $template->setUpdatedAt(Carbon::createFromTimestamp(0));

        return $template;
    }

    public function isDefault() {
        return $this->defaultTemplate;
    }

    /**
     * Retrieves the current user ID via a predefined provider or a custom method.
     *
     * @return mixed
     */
    protected static function getCurrentUserID() {
        $authPackage = config('pigeon-templates.auth-package', 'Auth');

        switch ($authPackage) {
            default:
            case 'Auth':
                $user = Auth::user();

                break;
            case 'custom':
                $user = call_user_func([$authPackage, config('pigeon-templates.auth.method', 'user')]);

                break;
        }

        return @$user->getKey() ?? null;
    }
}
