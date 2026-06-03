<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nominations', function (Blueprint $table) {
            $table->ipAddress('nominator_ip')->nullable()->after('nominated_by');
            $table->string('nominator_device_hash', 64)->nullable()->after('nominator_ip');
            $table->text('nominator_user_agent')->nullable()->after('nominator_device_hash');
            $table->unique('nominator_ip', 'nominations_nominator_ip_unique');
            $table->unique('nominator_device_hash', 'nominations_nominator_device_hash_unique');
        });
    }

    public function down(): void
    {
        Schema::table('nominations', function (Blueprint $table) {
            $table->dropUnique('nominations_nominator_device_hash_unique');
            $table->dropUnique('nominations_nominator_ip_unique');
            $table->dropColumn([
                'nominator_user_agent',
                'nominator_device_hash',
                'nominator_ip',
            ]);
        });
    }
};
