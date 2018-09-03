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

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePigeonTemplatesTables extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('pigeon_templates', function (Blueprint $table) {
            $table->increments('id');

            $table->integer('user_id')->unsigned();

            $table->string('name');
            $table->string('type');

            $table->mediumText('content');
            $table->string('thumbnail')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('restrict')
                ->onUpdate('cascade');
        });

        Schema::create('pigeon_templatables', function (Blueprint $table) {
            $table->increments('id');

            $table->integer('pigeon_template_id')->unsigned();

            $table->string('pigeon_templatable_type');
            $table->integer('pigeon_templatable_id')->unsigned();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['pigeon_template_id', 'pigeon_templatable_type', 'pigeon_templatable_id'], 'pigeon_templatables_unique');

            $table->foreign('pigeon_template_id')
                ->references('id')
                ->on('pigeon_templates')
                ->onDelete('cascade')
                ->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('pigeon_templatables');
        Schema::dropIfExists('pigeon_templates');
    }
}
