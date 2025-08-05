<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddPrefixRecordToJournalPrefixes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('journal_prefixes')->insert([
            ['prefix' => 'SI', 'description' => 'Saldo Inicial', 'modifiable' => true],
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('journal_prefixes')->where('prefix', 'SI')->delete();
    }
}
