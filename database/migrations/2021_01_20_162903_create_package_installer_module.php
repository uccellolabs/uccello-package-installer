<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Uccello\Core\Database\Migrations\Migration;
use Uccello\Core\Models\Module;
use Uccello\Core\Models\Domain;

class CreatePackageInstallerModule extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $module = $this->createModule();
        $this->activateModuleOnDomains($module);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Delete module
        Module::where('name', 'package-installer')->forceDelete();
    }

    protected function createModule()
    {
        $module = Module::create([
            'name' => 'package-installer',
            'icon' => 'cloud_upload',
            'model_class' => null,
            'data' => [
                "package" => "uccello/package-installer",
                "admin" => true,
                "menu" => false
            ]
        ]);

        return $module;
    }

    protected function activateModuleOnDomains($module)
    {
        $domains = Domain::all();
        foreach ($domains as $domain) {
            $domain->modules()->attach($module);
        }
    }
}
