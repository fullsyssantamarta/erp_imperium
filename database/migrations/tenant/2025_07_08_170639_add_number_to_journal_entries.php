<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Modules\Accounting\Models\JournalEntry;

class AddNumberToJournalEntries extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('journal_entries', function (Blueprint $table) {
            $table->unsignedInteger('number')->after('journal_prefix_id');
        });

        $entries = JournalEntry::orderBy('id')->get()->groupBy('journal_prefix_id');
        foreach ($entries as $prefixId => $group) {
            foreach ($group as $i => $entry) {
                $entry->number = $i + 1;
                $entry->save();
            }
        }

        Schema::table('journal_entries', function (Blueprint $table) {
            $table->unique(['journal_prefix_id', 'number']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('journal_entries', function (Blueprint $table) {
            $table->dropUnique(['journal_prefix_id', 'number']);
            $table->dropColumn('number');
        });
    }
}
