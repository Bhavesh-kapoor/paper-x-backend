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
use App\Http\Controllers\api\InquiryController;
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
    
    #converter reference data
    Route::get('/converter-types', [\App\Http\Controllers\api\ReferenceDataController::class, 'getConverterTypes'])->name('reference.converter-types');
    Route::get('/finished-products', [\App\Http\Controllers\api\ReferenceDataController::class, 'getFinishedProducts'])->name('reference.finished-products');
    Route::get('/scrap-types', [\App\Http\Controllers\api\ReferenceDataController::class, 'getScrapTypes'])->name('reference.scrap-types');
    Route::get('/converter-reference-data', [\App\Http\Controllers\api\ReferenceDataController::class, 'getConverterReferenceData'])->name('reference.converter-reference-data');
    
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
        
        // Post requirement (buy/sell)
        Route::post('/requirement/post', [DealerController::class, 'postRequirement'])->name('dealer.requirement.post');
        
        // Get requirements (with filters and pagination)
        Route::get('/requirements', [DealerController::class, 'getRequirements'])->name('dealer.requirements');
        
        // Opportunities
        Route::get('/opportunities', [OpportunityController::class, 'getOpportunities'])->name('dealer.opportunities');
        Route::get('/opportunity/{inquiry_id}', [OpportunityController::class, 'getOpportunityDetails'])->name('dealer.opportunity.details');
        Route::post('/opportunity/{id}/accept', [OpportunityController::class, 'acceptOpportunity'])->name('dealer.opportunity.accept');
        Route::post('/opportunity/{id}/decline', [OpportunityController::class, 'declineOpportunity'])->name('dealer.opportunity.decline');
        
        // Sessions
        Route::get('/session/{session_id}', [SessionController::class, 'getSession'])->name('dealer.session.details');
        Route::get('/history', [SessionController::class, 'getHistory'])->name('dealer.history');
    });
    
    #session routes (new matchmaking system)
    Route::middleware(['token.exists', 'auth:sanctum'])->prefix('sessions')->group(function () {
        // Get active sessions
        Route::get('/active', [SessionController::class, 'getActive'])->name('sessions.active');
        
        // Get session history
        Route::get('/history', [SessionController::class, 'getHistory'])->name('sessions.history');
        
        // Get session by inquiry id (e.g. from requirements list; use session id for detail)
        Route::get('/by-inquiry/{inquiry_id}', [SessionController::class, 'getSessionByInquiry'])->name('sessions.by-inquiry');
        
        // Lock session (select dealers)
        Route::post('/{session}/lock', [SessionController::class, 'lock'])->name('sessions.lock');
        
        // Republish session
        Route::post('/{session}/republish', [SessionController::class, 'republish'])->name('sessions.republish');
        
        // Mark deal as failed
        Route::post('/{session}/deal-failed', [SessionController::class, 'markDealFailed'])->name('sessions.deal-failed');
        
        // Get session details (use session id, not inquiry id)
        Route::get('/{session}', [SessionController::class, 'getSession'])->name('sessions.show');
        
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
        
        // Post requirement (buy/sell)
        Route::post('/requirement/post', [\App\Http\Controllers\api\ConverterController::class, 'postRequirement'])->name('converter.requirement.post');
        // Post machine buy/sell (converter)
        Route::post('/machine/post', [\App\Http\Controllers\api\ConverterController::class, 'postMachine'])->name('converter.machine.post');
        
        // Get brand requirements
        Route::get('/requirements', [\App\Http\Controllers\api\ConverterController::class, 'getRequirements'])->name('converter.requirements');
        
        // Respond to requirement
        Route::post('/requirement/{inquiry_id}/respond', [\App\Http\Controllers\api\ConverterController::class, 'respondToRequirement'])->name('converter.requirement.respond');
    });

    #inquiry routes (new matchmaking system)
    Route::middleware(['token.exists', 'auth:sanctum'])->prefix('inquiries')->group(function () {
        // Create inquiry (DRAFT)
        Route::post('/', [InquiryController::class, 'store'])->name('inquiries.store');
        
        // Save inquiry step (multi-step creation)
        Route::post('/save-step', [InquiryController::class, 'saveStep'])->name('inquiries.save-step');
        
        // Calculate posting fee
        Route::post('/calculate-fee', [InquiryController::class, 'calculatePostingFee'])->name('inquiries.calculate-fee');
        
        // Post inquiry (trigger matchmaking)
        Route::post('/{inquiry}/post', [InquiryController::class, 'post'])->name('inquiries.post');
        
        // Get posting status (matchmaking progress)
        Route::get('/{inquiry}/posting-status', [InquiryController::class, 'getPostingStatus'])->name('inquiries.posting-status');
        
        // Get inquiry details
        Route::get('/{inquiry}', [InquiryController::class, 'show'])->name('inquiries.show');
        
        // Get responses for inquiry (brand/converter only)
        Route::get('/{inquiry}/responses', [InquiryController::class, 'responses'])->name('inquiries.responses');
        
        // Get matchmaking responses with filters
        Route::get('/{inquiry}/matchmaking-responses', [InquiryController::class, 'getMatchmakingResponses'])->name('inquiries.matchmaking-responses');
        
        // Shortlist/Reject response
        Route::post('/responses/{response}/shortlist', [InquiryController::class, 'shortlistResponse'])->name('inquiries.shortlist-response');

        // Responder: express interest or decline (matched dealers/converters/machine_dealers)
        Route::post('/{inquiry}/express-interest', [InquiryController::class, 'expressInterest'])->name('inquiries.express-interest');
        Route::post('/{inquiry}/decline', [InquiryController::class, 'declineInquiry'])->name('inquiries.decline');
        
        // Republish inquiry
        Route::post('/{inquiry}/republish', [InquiryController::class, 'republish'])->name('inquiries.republish');
    });
    
    #dealer inquiry routes (matched inquiries only)
    Route::middleware(['token.exists', 'auth:sanctum'])->prefix('dealer')->group(function () {
        // Get inquiries visible to dealer (matched only)
        Route::get('/inquiries', [InquiryController::class, 'dealerInquiries'])->name('dealer.inquiries');
        
        // Respond to inquiry
        Route::post('/inquiries/{inquiry}/respond', [DealerController::class, 'respondToInquiry'])->name('dealer.inquiries.respond');
    });

    #brand routes
    Route::middleware(['token.exists', 'auth:sanctum'])->prefix('brand')->group(function () {
        Route::post('/profile/complete', [\App\Http\Controllers\api\BrandController::class, 'completeProfile'])->name('brand.profile.complete');
        Route::get('/dashboard', [\App\Http\Controllers\api\BrandController::class, 'getDashboard'])->name('brand.dashboard');
        
        // Post requirement (legacy)
        Route::post('/requirement/post', [\App\Http\Controllers\api\BrandController::class, 'postRequirement'])->name('brand.requirement.post');
        
        // My inquiries (legacy)
        Route::get('/inquiries', [\App\Http\Controllers\api\BrandController::class, 'getMyInquiries'])->name('brand.inquiries');
        
        // Messages (for active inquiries)
        Route::get('/messages/{session_id}', [\App\Http\Controllers\api\BrandController::class, 'getMessages'])->name('brand.messages');
    });

    #role switching (common for all roles)
    Route::middleware(['token.exists', 'auth:sanctum'])->prefix('user')->group(function () {
        Route::post('/switch-role', [RoleController::class, 'switchRole'])->name('user.switch-role');
    });

    #unified dashboard API for all roles
    Route::middleware(['token.exists', 'auth:sanctum'])->get('/dashboard', [DashboardController::class, 'getDashboard'])->name('dashboard');

    #wallet routes
    Route::middleware(['token.exists', 'auth:sanctum'])->prefix('wallet')->group(function () {
        // Get wallet balance
        Route::get('/', [\App\Http\Controllers\api\WalletController::class, 'getWallet'])->name('wallet.get');
        
        // Get credit packs
        Route::get('/credit-packs', [\App\Http\Controllers\api\WalletController::class, 'getCreditPacks'])->name('wallet.credit-packs');
        
        // Calculate custom credits
        Route::post('/calculate', [\App\Http\Controllers\api\WalletController::class, 'calculateCustomCredits'])->name('wallet.calculate');
        
        // Purchase credits
        Route::post('/purchase', [\App\Http\Controllers\api\WalletController::class, 'purchaseCredits'])->name('wallet.purchase');
        
        // Add credits (admin/system)
        Route::post('/add', [\App\Http\Controllers\api\WalletController::class, 'addCredits'])->name('wallet.add');
        
        // Get transaction history
        Route::get('/transactions', [\App\Http\Controllers\api\WalletController::class, 'getTransactions'])->name('wallet.transactions');
        
        // Deduct credits
        Route::post('/deduct', [\App\Http\Controllers\api\WalletController::class, 'deductCredits'])->name('wallet.deduct');
    });

});