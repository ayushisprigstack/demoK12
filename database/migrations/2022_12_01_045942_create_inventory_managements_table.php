<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
   
    public function up()
    {
        Schema::create('inventory_management', function (Blueprint $table) {
            $table->id();
            $table->date('Purchase_date')->nullable();
            $table->date('OEM_warranty_until')->nullable();            
            $table->date('Extended_warranty_until')->nullable();
            $table->date('ADP_coverage')->nullable();
            $table->string('OEM')->nullable();
            $table->string('Device_model')->nullable();
            $table->string('OS')->nullable();
            $table->string('Serial_number')->nullable();
            $table->string('Asset_tag')->nullable();
            $table->string('Building')->nullable();
            $table->string('Grade')->nullable();
            $table->string('Student_name')->nullable();
            $table->string('Student_ID')->nullable();
            $table->string('Parent_email')->nullable();
            $table->string('Parent_phone_number')->nullable();
            $table->tinyint('Parental_coverage')->nullable();
            $table->string('Repair_cap')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }
   
    public function down()
    {
        Schema::dropIfExists('inventory_management');
    }
};
