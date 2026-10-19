<?php

use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BackupController;
use App\Http\Controllers\Api\ClassroomController;
use App\Http\Controllers\Api\FinanceTransactionController;
use App\Http\Controllers\Api\GalleryAlbumController;
use App\Http\Controllers\Api\GuruAttendanceImportController;
use App\Http\Controllers\Api\HomepageSettingController;
use App\Http\Controllers\Api\LandingSectionController;
use App\Http\Controllers\Api\NewsCategoryController;
use App\Http\Controllers\Api\NewsController;
use App\Http\Controllers\Api\OrganizationMemberController;
use App\Http\Controllers\Api\PermissionController;
use App\Http\Controllers\Api\PPDBRegistrationController;
use App\Http\Controllers\Api\PPDBWaveController;
use App\Http\Controllers\Api\ScheduleController;
use App\Http\Controllers\Api\SchoolPrincipalController;
use App\Http\Controllers\Api\ScoreController;
use App\Http\Controllers\Api\SiteSettingController;
use App\Http\Controllers\Api\SppController;
use App\Http\Controllers\Api\StructureMemberController;
use App\Http\Controllers\Api\StudentController;
use App\Http\Controllers\Api\StudentImportController;
use App\Http\Controllers\Api\SubjectController;
use App\Http\Controllers\Api\TeacherAttendanceController;
use App\Http\Controllers\Api\UserController;
use App\Models\LandingSection;
use App\Models\SchoolPrincipal;
use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:60,1');

Route::get('/site-settings/public', [SiteSettingController::class, 'publicIndex']);
Route::get('/homepage-settings/public', [HomepageSettingController::class, 'publicIndex']);
Route::get('/school-principals/current', fn () => SchoolPrincipal::where('is_current', true)->orderBy('id')->get());
Route::get('/landing-sections/public', fn () => LandingSection::where('is_visible', true)->orderBy('order')->get());
Route::get('/news/public', [NewsController::class, 'publicIndex']);
Route::get('/news/public/{id}', [NewsController::class, 'publicShow']);
Route::get('/news-categories/public', [NewsCategoryController::class, 'publicIndex']);
Route::get('/gallery/public', [GalleryAlbumController::class, 'publicIndex']);
Route::get('/gallery/public/{id}', [GalleryAlbumController::class, 'publicShow']);
Route::get('/ppdb-waves/public', [PPDBWaveController::class, 'publicIndex']);
Route::get('/ppdb-waves/public/{id}', [PPDBWaveController::class, 'publicShow']);
Route::post('/ppdb-registrations', [PPDBRegistrationController::class, 'publicStore'])->middleware('throttle:10,1');
Route::get('/organization-members/public', [OrganizationMemberController::class, 'publicIndex']);
Route::get('/structure/{unit}', [StructureMemberController::class, 'publicShow']);

Route::middleware(['auth:sanctum', 'active'])->group(function () {
    Route::get('/user', [AuthController::class, 'me']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/permissions/catalog', [PermissionController::class, 'catalog'])
        ->middleware('permission:permissions.view,permissions.manage');

    Route::middleware('permission:students.import')->group(function () {
        Route::get('/students/template', [StudentImportController::class, 'template']);
        Route::post('/students/import/preview', [StudentImportController::class, 'preview']);
        Route::post('/students/import', [StudentImportController::class, 'import']);
    });

    Route::post('/backups', [BackupController::class, 'store'])->middleware('permission:system.backup.create');
    Route::get('/backups/download/{filename}', [BackupController::class, 'download'])
        ->where('filename', '[A-Za-z0-9._-]+')
        ->middleware('permission:system.backup.download');

    Route::post('/guru/attendance/import/preview', [GuruAttendanceImportController::class, 'preview'])->middleware('permission:attendance.import');
    Route::post('/guru/attendance/import', [GuruAttendanceImportController::class, 'import'])->middleware('permission:attendance.import');

    Route::apiResource('classrooms', ClassroomController::class)
        ->only(['index', 'show'])
        ->middleware('permission:classrooms.view');
    Route::apiResource('subjects', SubjectController::class)
        ->only(['index', 'show'])
        ->middleware('permission:subjects.view');
    Route::apiResource('students', StudentController::class)
        ->only(['index', 'show'])
        ->middleware('permission:students.view');

    Route::apiResource('schedules', ScheduleController::class)
        ->only(['index', 'show'])
        ->middleware('permission:schedules.view');
    Route::prefix('schedules/{schedule}')->group(function () {
        Route::get('/attendances', [AttendanceController::class, 'index'])->middleware('permission:attendance.view');
        Route::get('/sheet', [AttendanceController::class, 'sheet'])->middleware('permission:attendance.view');
        Route::post('/attendances', [AttendanceController::class, 'store'])->middleware('permission:attendance.create');
    });
    Route::get('/teacher-attendances', [TeacherAttendanceController::class, 'index'])->middleware('permission:attendance.teacher.view');
    Route::post('/teacher-attendances/check-in', [TeacherAttendanceController::class, 'checkIn'])->middleware('permission:attendance.teacher.checkin');
    Route::post('/teacher-attendances/check-out', [TeacherAttendanceController::class, 'checkOut'])->middleware('permission:attendance.teacher.checkin');
    Route::get('/teachers', fn () => User::where('role', 'guru')->select('id', 'name', 'email')->get())
        ->middleware('permission:teachers.view');

    Route::get('/scores/sheet', [ScoreController::class, 'sheet'])->middleware('permission:scores.view');
    Route::post('/scores', [ScoreController::class, 'store'])->middleware('permission:scores.update');
    Route::get('/wali/scores', [ScoreController::class, 'waliScores'])->middleware(['role:wali_murid', 'permission:scores.view']);

    Route::prefix('finance')->group(function () {
        Route::get('/dashboard', [FinanceTransactionController::class, 'dashboard'])->middleware('permission:finance.view');
        Route::get('/report', [FinanceTransactionController::class, 'report'])->middleware('permission:reports.finance.view');
        Route::get('/categories', [FinanceTransactionController::class, 'categories'])->middleware('permission:finance.categories.view');
        Route::post('/categories', [FinanceTransactionController::class, 'storeCategory'])->middleware('permission:finance.categories.create');
        Route::get('/transactions', [FinanceTransactionController::class, 'index'])->middleware('permission:finance.transactions.view');
        Route::post('/transactions', [FinanceTransactionController::class, 'store'])->middleware('permission:finance.transactions.create');
        Route::get('/transactions/{financeTransaction}', [FinanceTransactionController::class, 'show'])->middleware('permission:finance.transactions.view');
        Route::put('/transactions/{financeTransaction}', [FinanceTransactionController::class, 'update'])->middleware('permission:finance.transactions.update');
        Route::delete('/transactions/{financeTransaction}', [FinanceTransactionController::class, 'destroy'])->middleware('permission:finance.transactions.delete');

        Route::get('/spp/students', [SppController::class, 'students'])->middleware('permission:spp.view');
        Route::get('/spp/invoices', [SppController::class, 'index'])->middleware('permission:spp.view');
        Route::post('/spp/invoices', [SppController::class, 'store'])->middleware('permission:spp.create');
        Route::post('/spp/invoices/generate', [SppController::class, 'generate'])->middleware('permission:spp.create');
        Route::get('/spp/invoices/{sppInvoice}', [SppController::class, 'show'])->middleware('permission:spp.view');
        Route::post('/spp/invoices/{sppInvoice}/payments', [SppController::class, 'pay'])->middleware('permission:spp.update');
        Route::get('/spp/payments', [SppController::class, 'payments'])->middleware('permission:spp.view');
        Route::delete('/spp/payments/{sppPayment}', [SppController::class, 'destroyPayment'])->middleware('permission:spp.delete');
        Route::get('/spp/outstanding', [SppController::class, 'outstanding'])->middleware('permission:spp.view');
        Route::get('/spp/report', [SppController::class, 'report'])->middleware('permission:reports.finance.view');
    });

    Route::get('/users', [UserController::class, 'index'])->middleware('permission:users.view');
    Route::post('/users', [UserController::class, 'store'])->middleware('permission:users.create');
    Route::put('/users/{id}', [UserController::class, 'update'])->middleware('permission:users.update');
    Route::delete('/users/{id}', [UserController::class, 'destroy'])->middleware('permission:users.delete');

    Route::get('/users/{id}/permissions', [PermissionController::class, 'show'])->middleware('permission:permissions.view,permissions.manage');
    Route::put('/users/{id}/permissions', [PermissionController::class, 'update'])->middleware('permission:permissions.manage');

    Route::apiResource('classrooms', ClassroomController::class)
        ->only(['store', 'update', 'destroy'])
        ->middlewareFor(['store'], 'permission:classrooms.create')
        ->middlewareFor(['update'], 'permission:classrooms.update')
        ->middlewareFor(['destroy'], 'permission:classrooms.delete');
    Route::apiResource('subjects', SubjectController::class)
        ->only(['store', 'update', 'destroy'])
        ->middlewareFor(['store'], 'permission:subjects.create')
        ->middlewareFor(['update'], 'permission:subjects.update')
        ->middlewareFor(['destroy'], 'permission:subjects.delete');
    Route::apiResource('students', StudentController::class)
        ->only(['store', 'update', 'destroy'])
        ->middlewareFor(['store'], 'permission:students.create')
        ->middlewareFor(['update'], 'permission:students.update')
        ->middlewareFor(['destroy'], 'permission:students.delete');
    Route::apiResource('schedules', ScheduleController::class)
        ->only(['store', 'update', 'destroy'])
        ->middlewareFor(['store'], 'permission:schedules.create')
        ->middlewareFor(['update'], 'permission:schedules.update')
        ->middlewareFor(['destroy'], 'permission:schedules.delete');

    Route::get('/site-settings', [SiteSettingController::class, 'index'])->middleware('permission:settings.site.view');
    Route::put('/site-settings', [SiteSettingController::class, 'update'])->middleware('permission:settings.site.manage');
    Route::post('/site-settings/upload', [SiteSettingController::class, 'upload'])->middleware('permission:settings.site.manage');

    Route::apiResource('school-principals', SchoolPrincipalController::class)
        ->middlewareFor(['index', 'show'], 'permission:content.principal.view')
        ->middlewareFor(['store'], 'permission:content.principal.create')
        ->middlewareFor(['update'], 'permission:content.principal.update')
        ->middlewareFor(['destroy'], 'permission:content.principal.delete');

    Route::apiResource('landing-sections', LandingSectionController::class)
        ->middlewareFor(['index', 'show'], 'permission:settings.landing.view')
        ->middlewareFor(['store'], 'permission:settings.landing.manage')
        ->middlewareFor(['update'], 'permission:settings.landing.manage')
        ->middlewareFor(['destroy'], 'permission:settings.landing.manage');

    Route::apiResource('news', NewsController::class)
        ->middlewareFor(['index', 'show'], 'permission:content.news.view')
        ->middlewareFor(['store'], 'permission:content.news.create')
        ->middlewareFor(['update'], 'permission:content.news.update')
        ->middlewareFor(['destroy'], 'permission:content.news.delete');

    Route::apiResource('news-categories', NewsCategoryController::class)
        ->middlewareFor(['index', 'show'], 'permission:content.news_categories.view')
        ->middlewareFor(['store'], 'permission:content.news_categories.create')
        ->middlewareFor(['update'], 'permission:content.news_categories.update')
        ->middlewareFor(['destroy'], 'permission:content.news_categories.delete');

    Route::apiResource('gallery-albums', GalleryAlbumController::class)
        ->middlewareFor(['index', 'show'], 'permission:content.gallery.view')
        ->middlewareFor(['store'], 'permission:content.gallery.create')
        ->middlewareFor(['update'], 'permission:content.gallery.update')
        ->middlewareFor(['destroy'], 'permission:content.gallery.delete');
    Route::post('/gallery-albums/{galleryAlbum}/items', [GalleryAlbumController::class, 'storeItem'])->middleware('permission:content.gallery.create');
    Route::put('/gallery-albums/{galleryAlbum}/items/{item}', [GalleryAlbumController::class, 'updateItem'])->middleware('permission:content.gallery.update');
    Route::delete('/gallery-albums/{galleryAlbum}/items/{item}', [GalleryAlbumController::class, 'destroyItem'])->middleware('permission:content.gallery.delete');

    Route::apiResource('ppdb-waves', PPDBWaveController::class)
        ->parameters(['ppdb-waves' => 'ppdb_wave'])
        ->middlewareFor(['index', 'show'], 'permission:ppdb.waves.view')
        ->middlewareFor(['store'], 'permission:ppdb.waves.create')
        ->middlewareFor(['update'], 'permission:ppdb.waves.update')
        ->middlewareFor(['destroy'], 'permission:ppdb.waves.delete');

    Route::apiResource('ppdb-registrations', PPDBRegistrationController::class)
        ->only(['index', 'show', 'update', 'destroy'])
        ->middlewareFor(['index', 'show'], 'permission:ppdb.registrations.view')
        ->middlewareFor(['update'], 'permission:ppdb.registrations.update')
        ->middlewareFor(['destroy'], 'permission:ppdb.registrations.delete');

    Route::get('/homepage-settings', [HomepageSettingController::class, 'index'])->middleware('permission:settings.homepage.view');
    Route::put('/homepage-settings', [HomepageSettingController::class, 'update'])->middleware('permission:settings.homepage.manage');
    Route::post('/homepage-settings/upload-logo', [HomepageSettingController::class, 'uploadLogo'])
        ->middleware(['permission:settings.homepage.manage', 'throttle:20,1']);
    Route::delete('/homepage-settings/reset-logo', [HomepageSettingController::class, 'resetLogo'])->middleware('permission:settings.homepage.manage');
    Route::post('/homepage-settings/upload-media', [HomepageSettingController::class, 'uploadMedia'])
        ->middleware(['permission:settings.homepage.manage', 'throttle:20,1']);
    Route::put('/homepage-settings/update-media', [HomepageSettingController::class, 'updateMedia'])->middleware('permission:settings.homepage.manage');
    Route::delete('/homepage-settings/media/{id}', [HomepageSettingController::class, 'destroyMedia'])->middleware('permission:settings.homepage.manage');

    Route::get('/organization-members', [OrganizationMemberController::class, 'index'])
        ->middleware(['permission:content.structure.view', 'throttle:60,1']);
    Route::post('/organization-members', [OrganizationMemberController::class, 'store'])
        ->middleware(['permission:content.structure.create', 'throttle:60,1']);
    Route::post('/organization-members/upload-photo', [OrganizationMemberController::class, 'uploadPhoto'])
        ->middleware(['permission:content.structure.create,content.structure.update', 'throttle:20,1']);
    Route::put('/organization-members/{organization_member}', [OrganizationMemberController::class, 'update'])
        ->middleware(['permission:content.structure.update', 'throttle:60,1']);
    Route::delete('/organization-members/{organization_member}', [OrganizationMemberController::class, 'destroy'])
        ->middleware(['permission:content.structure.delete', 'throttle:60,1']);

    Route::get('/structure-members', [StructureMemberController::class, 'index'])
        ->middleware(['permission:content.structure.view', 'throttle:60,1']);
    Route::post('/structure-members', [StructureMemberController::class, 'store'])
        ->middleware(['permission:content.structure.create', 'throttle:60,1']);
    Route::post('/structure-members/upload-photo', [StructureMemberController::class, 'uploadPhoto'])
        ->middleware(['permission:content.structure.create,content.structure.update', 'throttle:20,1']);
    Route::post('/structure-members/{organization_member}/move', [StructureMemberController::class, 'move'])
        ->middleware(['permission:content.structure.update', 'throttle:60,1']);
    Route::put('/structure-members/{organization_member}', [StructureMemberController::class, 'update'])
        ->middleware(['permission:content.structure.update', 'throttle:60,1']);
    Route::delete('/structure-members/{organization_member}', [StructureMemberController::class, 'destroy'])
        ->middleware(['permission:content.structure.delete', 'throttle:60,1']);
});
