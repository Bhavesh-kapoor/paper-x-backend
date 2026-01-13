<?php

use App\Http\Controllers\api\AuthController;
use App\Http\Controllers\api\UserController;
use App\Http\Controllers\api\MaterialController;
use App\Http\Controllers\api\DealerController;
use App\Http\Controllers\api\OpportunityController;
use App\Http\Controllers\api\SessionController;
use App\Http\Controllers\api\ChatController;
use App\Http\Controllers\api\QuotationController;
use App\Http\Controllers\api\NotificationController;
use App\Http\Controllers\api\RoleController;
use App\Http\Controllers\api\DashboardController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');



Route::prefix('v1')->group(function () {

    #auth routes
    Route::post('auth/otp/request', [AuthController::class, 'loginWithOtp'])->name('auth.otp.request');
    Route::post('auth/otp/verify', [AuthController::class, 'loginWithOtp'])->name('auth.otp.verify');

    #user  -  get and update profile
    Route::middleware(['token.exists', 'auth:sanctum'])->group(function () {
        Route::get('/user/profile', [UserController::class, 'getProfile']);
        Route::post('/user/profile', [UserController::class, 'updateProfile']);
    });

    #materials
    Route::get('/materials', [MaterialController::class, 'getMaterials']);
    
    #reference data for profile completion
    Route::get('/machines', [\App\Http\Controllers\api\ReferenceDataController::class, 'getMachines'])->name('reference.machines');
    Route::get('/material-finishes', [\App\Http\Controllers\api\ReferenceDataController::class, 'getMaterialFinishes'])->name('reference.material-finishes');
    Route::get('/material-mills', [\App\Http\Controllers\api\ReferenceDataController::class, 'getMaterialMills'])->name('reference.material-mills');
    Route::get('/material-thickness-types', [\App\Http\Controllers\api\ReferenceDataController::class, 'getMaterialThicknessTypes'])->name('reference.material-thickness-types');
    Route::get('/brands', [\App\Http\Controllers\api\ReferenceDataController::class, 'getBrands'])->name('reference.brands');
    Route::get('/brand-types', [\App\Http\Controllers\api\ReferenceDataController::class, 'getBrandTypes'])->name('reference.brand-types');
    Route::get('/materials/{id}/details', [\App\Http\Controllers\api\ReferenceDataController::class, 'getMaterialDetails'])->name('reference.material-details');
    
    #dealer profile completion - manual additions
    Route::middleware(['token.exists', 'auth:sanctum'])->group(function () {
        Route::post('/dealer/mill/add', [\App\Http\Controllers\api\ReferenceDataController::class, 'addMillBrand'])->name('dealer.mill.add');
        Route::post('/dealer/finish/add', [\App\Http\Controllers\api\ReferenceDataController::class, 'addFinish'])->name('dealer.finish.add');
    });

    #dealer routes
    Route::middleware(['token.exists', 'auth:sanctum'])->prefix('dealer')->group(function () {
        // Profile completion
        Route::post('/profile/complete', [DealerController::class, 'completeProfile'])->name('dealer.profile.complete');
        
        // Dashboard
        Route::get('/dashboard', [DealerController::class, 'getDashboard'])->name('dealer.dashboard');
        
        // Opportunities
        Route::get('/opportunities', [OpportunityController::class, 'getOpportunities'])->name('dealer.opportunities');
        Route::get('/opportunity/{inquiry_id}', [OpportunityController::class, 'getOpportunityDetails'])->name('dealer.opportunity.details');
        Route::post('/opportunity/{id}/accept', [OpportunityController::class, 'acceptOpportunity'])->name('dealer.opportunity.accept');
        Route::post('/opportunity/{id}/decline', [OpportunityController::class, 'declineOpportunity'])->name('dealer.opportunity.decline');
        
        // Sessions
        Route::get('/session/{session_id}', [SessionController::class, 'getSession'])->name('dealer.session.details');
        Route::get('/history', [SessionController::class, 'getHistory'])->name('dealer.history');
        
        // Chat
        Route::get('/chat/{session_id}', [ChatController::class, 'getMessages'])->name('dealer.chat.messages');
        Route::post('/chat/{session_id}/message', [ChatController::class, 'sendMessage'])->name('dealer.chat.send');
        
        // Quotations
        Route::post('/quote/submit/{inquiry_id}', [QuotationController::class, 'submitQuote'])->name('dealer.quote.submit');
        
        // Notifications
        Route::get('/notifications', [NotificationController::class, 'getNotifications'])->name('dealer.notifications');
        Route::post('/notification/{id}/read', [NotificationController::class, 'markAsRead'])->name('dealer.notification.read');
        Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('dealer.notifications.read-all');
    });

    #machine dealer routes
    Route::middleware(['token.exists', 'auth:sanctum'])->prefix('machine-dealer')->group(function () {
        Route::post('/profile/complete', [\App\Http\Controllers\api\MachineDealerController::class, 'completeProfile'])->name('machine-dealer.profile.complete');
        Route::get('/dashboard', [\App\Http\Controllers\api\MachineDealerController::class, 'getDashboard'])->name('machine-dealer.dashboard');
        Route::post('/machine/post', [\App\Http\Controllers\api\MachineDealerController::class, 'postMachine'])->name('machine-dealer.machine.post');
        Route::get('/listings', [\App\Http\Controllers\api\MachineDealerController::class, 'getActiveListings'])->name('machine-dealer.listings');
        Route::get('/requirements', [\App\Http\Controllers\api\MachineDealerController::class, 'getActiveRequirements'])->name('machine-dealer.requirements');
    });

    #converter routes
    Route::middleware(['token.exists', 'auth:sanctum'])->prefix('converter')->group(function () {
        Route::post('/profile/complete', [\App\Http\Controllers\api\ConverterController::class, 'completeProfile'])->name('converter.profile.complete');
        Route::get('/dashboard', [\App\Http\Controllers\api\ConverterController::class, 'getDashboard'])->name('converter.dashboard');
    });

    #brand routes
    Route::middleware(['token.exists', 'auth:sanctum'])->prefix('brand')->group(function () {
        Route::post('/profile/complete', [\App\Http\Controllers\api\BrandController::class, 'completeProfile'])->name('brand.profile.complete');
        Route::get('/dashboard', [\App\Http\Controllers\api\BrandController::class, 'getDashboard'])->name('brand.dashboard');
    });

    #role switching (common for all roles)
    Route::middleware(['token.exists', 'auth:sanctum'])->prefix('user')->group(function () {
        Route::post('/switch-role', [RoleController::class, 'switchRole'])->name('user.switch-role');
    });

    #unified dashboard API for all roles
    Route::middleware(['token.exists', 'auth:sanctum'])->get('/dashboard', [DashboardController::class, 'getDashboard'])->name('dashboard');

});