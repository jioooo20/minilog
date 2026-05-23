<?php

use App\Http\Controllers\IncidentController;
use App\Http\Controllers\IncidentWorkflowController;
use App\Http\Controllers\exportController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AuditController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\MasterDataController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// =========================================================================
// PUBLIC ROUTES (Tanpa Auth)
// =========================================================================
Route::redirect('/', '/login');

// =========================================================================
// AUTH ROUTES (Semua yang login)
// =========================================================================
Route::middleware(['auth', 'verified'])->group(function () {

    // Dashboard (semua role)
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    // Master data management page (supervisor + engineer read access)
    Route::prefix('master-data')->middleware('role:engineer|supervisor')->group(function () {
        Route::get('/', [MasterDataController::class, 'index'])
            ->name('master-data.index');
    });

    // Engineer dashboard data (AJAX)
    Route::middleware('role:engineer')->group(function () {
        Route::get('/dashboard/engineer-data', [\App\Http\Controllers\dashboardController::class, 'engineerData'])
            ->name('dashboard.engineer-data');
    });

    // Operator dashboard data (AJAX)
    Route::middleware('role:operator')->group(function () {
        Route::get('/dashboard/operator-data', [\App\Http\Controllers\dashboardController::class, 'operatorData'])
            ->name('dashboard.operator-data');
    });

    // ---------------------------------------------------------------------
    // PROFILE ROUTES (semua role)
    // ---------------------------------------------------------------------
    Route::prefix('profile')->group(function () {
        Route::get('/', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/', [ProfileController::class, 'update'])->name('profile.update');
        Route::delete('/', [ProfileController::class, 'destroy'])->name('profile.destroy');
    });

    // INCIDENTS ROUTES (CRUD + Listing) — handled by concise group below

    // ---------------------------------------------------------------------
    // AUDIT LOGS ROUTES (Supervisor only)
    // ---------------------------------------------------------------------
    Route::prefix('audit')->middleware('role:supervisor')->group(function () {
        Route::get('/incidents/{incident}', [AuditController::class, 'show'])
            ->name('audit.incident.show');
        Route::get('/export', [AuditController::class, 'export'])
            ->name('audit.export');
        Route::get('/incidents/{incident}/final-report', [exportController::class, 'finalReport'])
            ->name('audit.final-report');
        Route::get('/', [AuditController::class, 'index'])
            ->name('audit.index');
    });

    // ---------------------------------------------------------------------
    // NOTIFICATIONS ROUTES (semua role)
    // ---------------------------------------------------------------------
    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index'])
            ->name('notifications.index');
        Route::post('/{notification}/read', [NotificationController::class, 'markAsRead'])
            ->name('notifications.mark-read');
        Route::post('/mark-all-read', [NotificationController::class, 'markAllAsRead'])
            ->name('notifications.mark-all-read');
    });

    // ---------------------------------------------------------------------
    // MASTER DATA ROUTES (Read only, untuk dropdown & referensi)
    // ---------------------------------------------------------------------
    Route::prefix('master-data')->group(function () {
        // Items: semua role yang login bisa lihat (tapi terbatas pada akses mereka)
        Route::get('/items', [MasterDataController::class, 'getItems'])
            ->name('master-data.items');
        Route::get('/items/{item}', [MasterDataController::class, 'showItem'])
            ->name('master-data.items.show');

        // Locations: semua role
        Route::get('/locations', [MasterDataController::class, 'getLocations'])
            ->name('master-data.locations');

        // Departments: semua role
        Route::get('/departments', [MasterDataController::class, 'getDepartments'])
            ->name('master-data.departments');

        // Categories: semua role
        Route::get('/categories', [MasterDataController::class, 'getCategories'])
            ->name('master-data.categories');

        // Engineers (untuk dropdown assign): Supervisor + Engineer
        Route::middleware('role:engineer|supervisor')->group(function () {
            Route::get('/engineers', [MasterDataController::class, 'getEngineers'])
                ->name('master-data.engineers');
        });
    });

    // MASTER DATA MUTATIONS (supervisor only)
    Route::prefix('master-data')->middleware('role:supervisor')->group(function () {
        Route::post('/{type}', [MasterDataController::class, 'store'])
            ->name('master-data.store');
        Route::patch('/{type}/{record}', [MasterDataController::class, 'update'])
            ->name('master-data.update');
        Route::delete('/{type}/{record}', [MasterDataController::class, 'destroy'])
            ->name('master-data.destroy');
        Route::post('/{type}/{record}/restore', [MasterDataController::class, 'restore'])
            ->name('master-data.restore');
    });
});

// =========================================================================
// AUTH ROUTES (dari Laravel Breeze/Jetstream) - Sudah ada di auth.php
// =========================================================================
/**
 * Recommended concise route definitions (example):
 *
 * - Uses explicit `auth` + `verified` middleware then `role` checks.
 * - Resource routes for CRUD and separate POST routes for workflow transitions.
 * - If models use custom primary keys, add `getRouteKeyName()` in the model.
 *
 * Example (active concise layout is enabled below):
 */

Route::middleware(['auth', 'verified'])->group(function () {
    // CRUD (index, show, store)
    Route::resource('incidents', IncidentController::class)
        ->only(['index', 'show', 'store', 'create'])
        ->middleware('role:operator|engineer|supervisor');

    // Workflow endpoints, grouped by role
    Route::prefix('incidents/{incident}')->group(function () {
        Route::middleware('role:engineer')->group(function () {
            Route::get('investigate', [IncidentWorkflowController::class, 'investigate'])->name('incidents.investigate');
            Route::patch('investigate', [IncidentWorkflowController::class, 'saveInvestigationDraft'])->name('incidents.investigate-draft');
            Route::get('repair', [IncidentWorkflowController::class, 'repair'])->name('incidents.repair');
            Route::patch('repair', [IncidentWorkflowController::class, 'saveRepairDraft'])->name('incidents.repair-draft');
            Route::get('request-closing', [IncidentWorkflowController::class, 'requestClosingPage'])->name('incidents.request-closing-form');
            Route::post('assign', [IncidentWorkflowController::class, 'assign'])->name('incidents.assign');
            Route::post('propose', [IncidentWorkflowController::class, 'propose'])->name('incidents.propose');
            Route::post('complete-repair', [IncidentWorkflowController::class, 'completeRepair'])->name('incidents.complete-repair');
            Route::post('request-closing', [IncidentWorkflowController::class, 'requestClosing'])->name('incidents.request-closing');
        });

        Route::middleware('role:supervisor')->group(function () {
            Route::get('review', [IncidentWorkflowController::class, 'review'])->name('incidents.review');
            Route::get('close', [IncidentWorkflowController::class, 'closePage'])->name('incidents.close-form');
            Route::post('approve', [IncidentWorkflowController::class, 'approve'])->name('incidents.approve');
            Route::post('reject', [IncidentWorkflowController::class, 'reject'])->name('incidents.reject');
            Route::post('close', [IncidentWorkflowController::class, 'close'])->name('incidents.close');
        });

        Route::middleware('role:operator')->group(function () {
            Route::get('verify', [IncidentWorkflowController::class, 'verifyPage'])->name('incidents.verify-form');
            Route::post('verify', [IncidentWorkflowController::class, 'verify'])->name('incidents.verify');
        });
    });
});
//  *
//  * Note: if `Incident` uses `protected $primaryKey = 'incident_id'`, add this to the model:
//  *
//  * public function getRouteKeyName(): string
//  * {
//  *     return 'incident_id';
//  * }
//  */

require __DIR__ . '/auth.php';