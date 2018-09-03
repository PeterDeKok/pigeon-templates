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

namespace PeterDeKok\PigeonTemplates\Exceptions;

class PigeonConfigurationException extends PigeonException {

    protected $configKey;

    public function __construct(string $key, int $code = 0, \Throwable $previous = null) {
        $this->configKey = $key;

        $message = "Missing or incorrect configuration for [{$key}]";

        parent::__construct($message, $code, $previous);
    }

    public function getConfigKey() {
        return $this->configKey;
    }
}
