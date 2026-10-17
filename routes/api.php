<?php

use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BackupController;
use App\Http\Controllers\Api\ClassroomController;
use App\Http\Controllers\Api\GalleryAlbumController;
use App\Http\Controllers\Api\GuruAttendanceImportController;
use App\Http\Controllers\Api\HomepageSettingController;
use App\Http\Controllers\Api\LandingSectionController;
use App\Http\Controllers\Api\NewsCategoryController;
use App\Http\Controllers\Api\NewsController;
use App\Http\Controllers\Api\OrganizationMemberController;
use App\Http\Controllers\Api\PPDBRegistrationController;
use App\Http\Controllers\Api\PPDBWaveController;
use App\Http\Controllers\Api\ScheduleController;
use App\Http\Controllers\Api\SchoolPrincipalController;
use App\Http\Controllers\Api\ScoreController;
use App\Http\Controllers\Api\SiteSettingController;
use App\Http\Controllers\Api\StudentController;
use App\Http\Controllers\Api\StudentImportController;
use App\Http\Controllers\Api\SubjectController;
use App\Http\Controllers\Api\TeacherAttendanceController;
use App\Http\Controllers\Api\UserController;
use App\Models\LandingSection;
use App\Models\SchoolPrincipal;
use App\Models\User;
use Illuminate\Http\Request;
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

Route::middleware(['auth:sanctum', 'active'])->group(function () {
    Route::get('/user', fn (Request $request) => $request->user());
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::middleware('role:admin')->group(function () {
        Route::get('/students/template', [StudentImportController::class, 'template']);
        Route::post('/students/import/preview', [StudentImportController::class, 'preview']);
        Route::post('/students/import', [StudentImportController::class, 'import']);

        Route::post('/backups', [BackupController::class, 'store']);
        Route::get('/backups/download/{filename}', [BackupController::class, 'download'])->where('filename', '[A-Za-z0-9._-]+');
    });

    Route::post('/guru/attendance/import/preview', [GuruAttendanceImportController::class, 'preview']);
    Route::post('/guru/attendance/import', [GuruAttendanceImportController::class, 'import']);

    Route::apiResource('classrooms', ClassroomController::class)->only(['index', 'show']);
    Route::apiResource('subjects', SubjectController::class)->only(['index', 'show']);
    Route::apiResource('students', StudentController::class)->only(['index', 'show']);

    Route::apiResource('schedules', ScheduleController::class)->only(['index', 'show']);
    Route::prefix('schedules/{schedule}')->group(function () {
        Route::get('/attendances', [AttendanceController::class, 'index']);
        Route::get('/sheet', [AttendanceController::class, 'sheet']);
        Route::post('/attendances', [AttendanceController::class, 'store']);
    });
    Route::get('/teacher-attendances', [TeacherAttendanceController::class, 'index']);
    Route::post('/teacher-attendances/check-in', [TeacherAttendanceController::class, 'checkIn']);
    Route::post('/teacher-attendances/check-out', [TeacherAttendanceController::class, 'checkOut']);
    Route::get('/teachers', fn () => User::where('role', 'guru')->select('id', 'name', 'email')->get());
    Route::get('/scores/sheet', [ScoreController::class, 'sheet']);
    Route::post('/scores', [ScoreController::class, 'store']);
    Route::get('/wali/scores', [ScoreController::class, 'waliScores']);

    Route::middleware('role:admin')->group(function () {
        Route::get('/users', [UserController::class, 'index']);
        Route::apiResource('classrooms', ClassroomController::class)->only(['store', 'update', 'destroy']);
        Route::apiResource('subjects', SubjectController::class)->only(['store', 'update', 'destroy']);
        Route::apiResource('students', StudentController::class)->only(['store', 'update', 'destroy']);
        Route::apiResource('schedules', ScheduleController::class)->only(['store', 'update', 'destroy']);
        Route::post('/users', [UserController::class, 'store']);
        Route::put('/users/{id}', [UserController::class, 'update']);
        Route::delete('/users/{id}', [UserController::class, 'destroy']);

        Route::get('/site-settings', [SiteSettingController::class, 'index']);
        Route::put('/site-settings', [SiteSettingController::class, 'update']);
        Route::post('/site-settings/upload', [SiteSettingController::class, 'upload']);
        Route::apiResource('school-principals', SchoolPrincipalController::class);
        Route::apiResource('landing-sections', LandingSectionController::class);
        Route::apiResource('news', NewsController::class);
        Route::apiResource('news-categories', NewsCategoryController::class);
        Route::apiResource('gallery-albums', GalleryAlbumController::class);
        Route::post('/gallery-albums/{galleryAlbum}/items', [GalleryAlbumController::class, 'storeItem']);
        Route::put('/gallery-albums/{galleryAlbum}/items/{item}', [GalleryAlbumController::class, 'updateItem']);
        Route::delete('/gallery-albums/{galleryAlbum}/items/{item}', [GalleryAlbumController::class, 'destroyItem']);
        Route::apiResource('ppdb-waves', PPDBWaveController::class)->parameters(['ppdb-waves' => 'ppdb_wave']);
        Route::apiResource('ppdb-registrations', PPDBRegistrationController::class)->only(['index', 'show', 'update', 'destroy']);

        Route::get('/homepage-settings', [HomepageSettingController::class, 'index']);
        Route::put('/homepage-settings', [HomepageSettingController::class, 'update']);
        Route::post('/homepage-settings/upload-logo', [HomepageSettingController::class, 'uploadLogo'])->middleware('throttle:20,1');
        Route::delete('/homepage-settings/reset-logo', [HomepageSettingController::class, 'resetLogo']);
        Route::post('/homepage-settings/upload-media', [HomepageSettingController::class, 'uploadMedia'])->middleware('throttle:20,1');
        Route::put('/homepage-settings/update-media', [HomepageSettingController::class, 'updateMedia']);
        Route::delete('/homepage-settings/media/{id}', [HomepageSettingController::class, 'destroyMedia']);

        Route::get('/organization-members', [OrganizationMemberController::class, 'index']);
        Route::post('/organization-members', [OrganizationMemberController::class, 'store']);
        Route::post('/organization-members/upload-photo', [OrganizationMemberController::class, 'uploadPhoto'])->middleware('throttle:20,1');
        Route::put('/organization-members/{organization_member}', [OrganizationMemberController::class, 'update']);
        Route::delete('/organization-members/{organization_member}', [OrganizationMemberController::class, 'destroy']);
    });
});
