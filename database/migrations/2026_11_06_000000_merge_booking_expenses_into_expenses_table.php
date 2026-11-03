<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Expenses become one central table. A nullable booking_id marks a
     * direct cost tied to a specific booking; NULL means a general
     * operating expense. The separate booking_expenses table (and its
     * rows) are folded into expenses and then dropped.
     */
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->foreignId('booking_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
        });

        if (Schema::hasTable('booking_expenses')) {
            DB::table('booking_expenses')->orderBy('id')->each(function ($row) {
                DB::table('expenses')->insert([
                    'booking_id' => $row->booking_id,
                    'category' => $row->category,
                    'amount' => $row->amount,
                    'description' => $row->description,
                    'expense_date' => $row->expense_date ?? substr((string) $row->created_at, 0, 10),
                    'created_at' => $row->created_at,
                    'updated_at' => $row->updated_at,
                ]);
            });

            Schema::drop('booking_expenses');
        }
    }

    public function down(): void
    {
        Schema::create('booking_expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->string('category');
            $table->string('description')->nullable();
            $table->decimal('amount', 10, 2);
            $table->date('expense_date')->nullable();
            $table->timestamps();
        });

        DB::table('expenses')->whereNotNull('booking_id')->orderBy('id')->each(function ($row) {
            DB::table('booking_expenses')->insert([
                'booking_id' => $row->booking_id,
                'category' => $row->category,
                'description' => $row->description,
                'amount' => $row->amount,
                'expense_date' => $row->expense_date,
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
            ]);
        });

        DB::table('expenses')->whereNotNull('booking_id')->delete();

        Schema::table('expenses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('booking_id');
        });
    }
};
