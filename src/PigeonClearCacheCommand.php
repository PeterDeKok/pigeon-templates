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

use Illuminate\Console\Command;

class PigeonClearCacheCommand extends Command {

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pigeon:clear-cache';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';
    /**
     * @var \PeterDeKok\PigeonTemplates\PigeonTemplateRenderer $renderer
     */
    protected $renderer;

    /**
     * Create a new command instance.
     *
     * @param \PeterDeKok\PigeonTemplates\PigeonTemplateRenderer $renderer
     */
    public function __construct(PigeonTemplateRenderer $renderer) {
        parent::__construct();

        $this->renderer = $renderer;
    }

    /**
     * Execute the console command.
     */
    public function handle() {
        if (!$this->confirm('Are you sure you want to delete all PigeonTemplates cache files?'))
            return;

        if ($this->renderer->deleteCachedPigeonViews(true))
            $this->info('Deleted all PigeonTemplates cache files.');
        else
            $this->error('Something went wrong while deleting all PigeonTemplates cache files.');
    }
}
