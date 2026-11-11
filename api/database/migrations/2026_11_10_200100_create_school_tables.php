<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('parent_name')->nullable();
            $table->string('parent_phone')->nullable();
            $table->string('grade')->nullable();
            $table->string('status')->default('active'); // active | left
            $table->timestamps();
            $table->index(['tenant_id', 'name']);
        });

        Schema::create('class_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('subject')->nullable();
            $table->string('mode')->default('physical'); // online | physical
            $table->unsignedTinyInteger('day_of_week')->nullable(); // 1 = Monday … 7 = Sunday
            $table->string('start_time', 5)->nullable(); // HH:MM
            $table->unsignedInteger('monthly_fee')->default(0);
            $table->string('meeting_link')->nullable();
            $table->string('hall')->nullable();
            $table->timestamps();
        });

        Schema::create('enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('class_group_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['student_id', 'class_group_id']);
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('class_group_id')->constrained()->cascadeOnDelete();
            $table->string('month', 7); // YYYY-MM
            $table->unsignedInteger('amount');
            $table->string('method'); // bank | cash | card
            $table->string('status')->default('pending'); // pending | approved
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'month', 'status']);
        });

        Schema::create('attendance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('class_group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->boolean('present');
            $table->timestamps();
            $table->unique(['class_group_id', 'student_id', 'date']);
        });

        Schema::create('exams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('class_group_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->date('held_on');
            $table->unsignedSmallInteger('max_marks')->default(100);
            $table->timestamps();
        });

        Schema::create('exam_marks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('exam_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('marks');
            $table->timestamps();
            $table->unique(['exam_id', 'student_id']);
        });
    }

    public function down(): void
    {
        foreach (['exam_marks', 'exams', 'attendance_records', 'payments', 'enrollments', 'class_groups', 'students'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
