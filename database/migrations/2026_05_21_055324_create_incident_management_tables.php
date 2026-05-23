<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('departments', function (Blueprint $table) {
            $table->id('dept_id');
            $table->string('dept_code', 20)->unique();
            $table->string('dept_name', 100);
            $table->string('manager_name', 100)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('locations', function (Blueprint $table) {
            $table->id('location_id');
            $table->string('location_code', 30)->unique();
            $table->string('location_name', 100);
            $table->enum('location_type', ['building', 'floor', 'line', 'cell', 'station']);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('item_categories', function (Blueprint $table) {
            $table->id('category_id');
            $table->string('category_code', 30)->unique();
            $table->string('category_name', 100);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('items', function (Blueprint $table) {
            $table->id('item_id');
            $table->string('asset_tag', 50)->unique();
            $table->string('serial_number', 100)->nullable();
            $table->string('item_name', 200);
            $table->string('brand', 100)->nullable();
            $table->string('model', 100)->nullable();
            $table->text('description')->nullable();
            $table->foreignId('category_id')->nullable()->constrained('item_categories', 'category_id');
            $table->enum('item_type', ['machine', 'equipment', 'tool', 'component', 'sensor', 'vehicle'])->nullable();
            $table->foreignId('location_id')->nullable()->constrained('locations', 'location_id');
            $table->foreignId('dept_id')->nullable()->constrained('departments', 'dept_id');
            $table->enum('status', ['operational', 'maintenance', 'broken', 'retired', 'calibration_due'])->default('operational');
            $table->date('installation_date')->nullable();
            $table->date('last_calibration_date')->nullable();
            $table->date('calibration_due_date')->nullable();
            $table->boolean('is_critical')->default(false);
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();
        });

        Schema::create('incidents', function (Blueprint $table) {
            $table->id('incident_id');
            $table->string('incident_code', 20)->unique();
            $table->string('title', 200);
            $table->text('description');
            $table->foreignId('item_id')->constrained('items', 'item_id');
            $table->foreignId('component_item_id')->nullable()->constrained('items', 'item_id');
            $table->foreignId('location_id')->constrained('locations', 'location_id');
            $table->enum('severity', ['Low', 'Medium', 'High', 'Critical']);
            $table->enum('status', ['open', 'investigating', 'awaiting_approval', 'repairing', 'verifying', 'closed'])->default('open');
            $table->timestamp('detected_at');
            $table->timestamp('investigating_started_at')->nullable();
            $table->timestamp('repair_started_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('reported_by')->constrained('users');
            $table->foreignId('handled_by')->nullable()->constrained('users');
            $table->foreignId('closed_by')->nullable()->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->foreignId('verified_by')->nullable()->constrained('users');
            $table->text('root_cause_hypothesis')->nullable();
            $table->text('investigation_notes')->nullable();
            $table->boolean('hypothesis_approved')->default(false);
            $table->text('hypothesis_review_notes')->nullable();
            $table->timestamp('hypothesis_approved_at')->nullable();
            $table->text('corrective_actions')->nullable();
            $table->text('parts_replaced')->nullable();
            $table->text('verification_notes')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->text('closing_notes')->nullable();
            $table->timestamps();

            $table->index(['status', 'severity']);
            $table->index('detected_at');
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id('log_id');
            $table->foreignId('incident_id')->constrained('incidents', 'incident_id');
            $table->foreignId('user_id')->constrained('users');
            $table->string('action', 50);
            $table->text('action_details')->nullable();
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('created_at');
        });

        Schema::create('attachments', function (Blueprint $table) {
            $table->id('attachment_id');
            $table->foreignId('incident_id')->constrained('incidents', 'incident_id');
            $table->foreignId('uploaded_by')->constrained('users');
            $table->string('file_name', 255);
            $table->string('file_path', 500);
            $table->integer('file_size')->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->string('description', 200)->nullable();
            $table->timestamp('uploaded_at')->useCurrent();
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->id('notif_id');
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('incident_id')->constrained('incidents', 'incident_id');
            $table->enum('type', ['new_incident', 'approval_needed', 'hypothesis_approved', 'verification_needed', 'closing_requested', 'closed']);
            $table->text('message');
            $table->boolean('is_read')->default(false);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('read_at')->nullable();

            $table->index(['user_id', 'is_read']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('attachments');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('incidents');
        Schema::dropIfExists('items');
        Schema::dropIfExists('item_categories');
        Schema::dropIfExists('locations');
        Schema::dropIfExists('departments');
    }
};
