<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nominations', function (Blueprint $table) {
            $table->string('reference_code', 7)->nullable()->after('id');
        });

        $usedCodes = [];

        DB::table('nominations')
            ->whereNull('reference_code')
            ->orderBy('id')
            ->pluck('id')
            ->each(function ($id) use (&$usedCodes) {
                $code = $this->generateReferenceCode($usedCodes);
                $usedCodes[$code] = true;

                DB::table('nominations')
                    ->where('id', $id)
                    ->update(['reference_code' => $code]);
            });

        Schema::table('nominations', function (Blueprint $table) {
            $table->unique('reference_code', 'nominations_reference_code_unique');
        });
    }

    public function down(): void
    {
        Schema::table('nominations', function (Blueprint $table) {
            $table->dropUnique('nominations_reference_code_unique');
            $table->dropColumn('reference_code');
        });
    }

    private function generateReferenceCode(array $usedCodes): string
    {
        $letters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $numbers = '0123456789';
        $characters = $letters.$numbers;

        do {
            $code = $letters[random_int(0, strlen($letters) - 1)]
                .$numbers[random_int(0, strlen($numbers) - 1)];

            for ($i = 0; $i < 5; $i++) {
                $code .= $characters[random_int(0, strlen($characters) - 1)];
            }

            $code = str_shuffle($code);
        } while (isset($usedCodes[$code]) || DB::table('nominations')->where('reference_code', $code)->exists());

        return $code;
    }
};
