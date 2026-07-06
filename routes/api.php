<?php

use App\Http\Controllers\api\AuthController;
use App\Http\Controllers\api\PreRegistrationController;
use App\Http\Controllers\api\UserController;
use App\Http\Controllers\api\MaterialController;
use App\Http\Controllers\api\DealerController;
use App\Http\Controllers\api\OpportunityController;
use App\Http\Controllers\api\SessionController;
use App\Http\Controllers\api\InquiryController;
use App\Http\Controllers\api\ChatController;
use App\Http\Controllers\api\ChatThreadController;
use App\Http\Controllers\api\QuotationController;
use App\Http\Controllers\api\NotificationController;
use App\Http\Controllers\api\RoleController;
use App\Http\Controllers\api\DashboardController;
use App\Http\Controllers\api\RegistrationDetailsController;
use App\Http\Controllers\api\RTDProductController;
use App\Http\Controllers\api\RTDOrderController;
use App\Http\Controllers\api\RtdListingPackController;
use App\Http\Controllers\api\MarketInsightController;
use App\Http\Controllers\api\JobworkController;
use App\Http\Controllers\api\UploadController;
use App\Http\Controllers\api\WalletPaymentController;
use App\Http\Controllers\api\InvoiceController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');



Route::prefix('v1')->group(function () {

    // Public market insights routes
    Route::prefix('insights')->group(function () {
        Route::get('/today', [MarketInsightController::class, 'today'])->name('insights.today');
        Route::get('/history', [MarketInsightController::class, 'history'])->name('insights.history');
        Route::get('/{date}', [MarketInsightController::class, 'showByDate'])
            ->where('date', '\d{4}-\d{2}-\d{2}')
            ->name('insights.by-date');
    });

    #auth routes
    Route::post('auth/otp/request', [AuthController::class, 'loginWithOtp'])->name('auth.otp.request');
    Route::post('auth/otp/verify', [AuthController::class, 'loginWithOtp'])->name('auth.otp.verify');

    #public pre-registration route
    Route::post('pre-registrations', [PreRegistrationController::class, 'store'])->name('pre-registrations.store');

    # Razorpay webhooks (no auth; signature verified in controller)
    Route::post('webhooks/razorpay', [WalletPaymentController::class, 'webhook'])->name('webhooks.razorpay');

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
    
    #dealer profile completion - manual additions & custom material
    Route::middleware(['token.exists', 'auth:sanctum'])->group(function () {
        Route::post('/dealer/mill/add', [\App\Http\Controllers\api\ReferenceDataController::class, 'addMillBrand'])->name('dealer.mill.add');
        Route::post('/dealer/finish/add', [\App\Http\Controllers\api\ReferenceDataController::class, 'addFinish'])->name('dealer.finish.add');
        Route::post('/materials', [MaterialController::class, 'store'])->name('materials.store');
        Route::post('/brands', [\App\Http\Controllers\api\ReferenceDataController::class, 'storeBrand'])->name('brands.store');
        Route::post('/pricing/quote', [\App\Http\Controllers\api\PricingController::class, 'quote'])->name('pricing.quote');

        Route::post('/machines', [\App\Http\Controllers\api\ReferenceDataController::class, 'storeMachine'])->name('machines.store');
        Route::post('/finished-products', [\App\Http\Controllers\api\ReferenceDataController::class, 'storeFinishedProduct'])->name('finished-products.store');
        Route::post('/scrap-types', [\App\Http\Controllers\api\ReferenceDataController::class, 'storeScrapType'])->name('scrap-types.store');
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
        
        // Get poster detail (owner only: counts + requirement summary, no response list)
        Route::get('/{session}/poster-detail', [SessionController::class, 'getPosterDetail'])->name('sessions.poster-detail');
        // Get responder detail (non-owner only: someone wants to buy/sell + requirement summary)
        Route::get('/{session}/responder-detail', [SessionController::class, 'getResponderDetail'])->name('sessions.responder-detail');
        
        // Chat list (must be before /{session} catch-all)
        Route::get('/chat-list', [ChatController::class, 'getChatList'])->name('sessions.chat-list');
        // Chat
        Route::get('/chat/{session_id}', [ChatController::class, 'getMessages'])->name('dealer.chat.messages');
        
        // Get session details (use session id, not inquiry id)
        Route::get('/{session}', [SessionController::class, 'getSession'])->name('sessions.show');
        Route::post('/chat/{session_id}/message', [ChatController::class, 'sendMessage'])->name('dealer.chat.send');
        
        // Quotations
        Route::post('/quote/submit/{inquiry_id}', [QuotationController::class, 'submitQuote'])->name('dealer.quote.submit');
        
    });

    #structured chat routes (thread-native; additive to legacy session chat)
    Route::middleware(['token.exists', 'auth:sanctum'])->prefix('chat-threads')->group(function () {
        Route::get('/', [ChatThreadController::class, 'listAll'])->name('chat-threads.all');
        Route::get('/{thread_id}/messages', [ChatThreadController::class, 'getMessages'])->name('chat-threads.messages');
        Route::post('/{thread_id}/messages', [ChatThreadController::class, 'sendMessage'])->name('chat-threads.send');
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

        // Jobwork: converter-to-converter flows
        Route::post('/jobwork/find', [JobworkController::class, 'postFind'])->name('converter.jobwork.find');
        Route::post('/jobwork/give', [JobworkController::class, 'postGive'])->name('converter.jobwork.give');
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

        // Structured chat (thread-native)
        Route::get('/{inquiry_id}/chat-threads', [ChatThreadController::class, 'listByInquiry'])->name('inquiries.chat-threads');
        Route::post('/{inquiry_id}/chat-threads/open', [ChatThreadController::class, 'open'])->name('inquiries.chat-threads.open');
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
        
        // Post requirement
        Route::post('/requirement/post', [\App\Http\Controllers\api\BrandController::class, 'postRequirement'])->name('brand.requirement.post');
        
        // My inquiries
        Route::get('/inquiries', [\App\Http\Controllers\api\BrandController::class, 'getMyInquiries'])->name('brand.inquiries');
        
        // Messages (for active inquiries)
        Route::get('/messages/{session_id}', [\App\Http\Controllers\api\BrandController::class, 'getMessages'])->name('brand.messages');
    });

    #role switching (common for all roles)
    Route::middleware(['token.exists', 'auth:sanctum'])->prefix('user')->group(function () {
        Route::post('/switch-role', [RoleController::class, 'switchRole'])->name('user.switch-role');
    });

    #notifications routes (canonical for all roles)
    Route::middleware(['token.exists', 'auth:sanctum'])->prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'getNotifications'])->name('notifications.list');
        Route::get('/unread-count', [NotificationController::class, 'getUnreadCount'])->name('notifications.unread-count');
        Route::post('/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
        Route::post('/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');
    });

    #unified dashboard API for all roles
    Route::middleware(['token.exists', 'auth:sanctum'])->get('/dashboard', [DashboardController::class, 'getDashboard'])->name('dashboard');

    #rtd listing packs (converter pays to list products)
    Route::middleware(['token.exists', 'auth:sanctum'])->prefix('rtd/listing-packs')->group(function () {
        Route::get('/', [RtdListingPackController::class, 'index'])->name('rtd.listing-packs.index');
        Route::post('/purchase', [RtdListingPackController::class, 'purchase'])->name('rtd.listing-packs.purchase');
    });
    Route::middleware(['token.exists', 'auth:sanctum'])->get('rtd/entitlement', [RtdListingPackController::class, 'entitlement'])->name('rtd.entitlement');
    #rtd product routes (ready-to-dispatch)
    Route::middleware(['token.exists', 'auth:sanctum'])->prefix('rtd/products')->group(function () {
        Route::post('/', [RTDProductController::class, 'store'])->name('rtd.products.store');
        Route::put('/{id}', [RTDProductController::class, 'update'])->name('rtd.products.update');
        Route::post('/{id}/pause', [RTDProductController::class, 'pause'])->name('rtd.products.pause');
        Route::post('/{id}/resume', [RTDProductController::class, 'resume'])->name('rtd.products.resume');
        Route::get('/my', [RTDProductController::class, 'myProducts'])->name('rtd.products.my');
        Route::get('/catalog', [RTDProductController::class, 'catalog'])->name('rtd.products.catalog');
        Route::get('/{id}', [RTDProductController::class, 'show'])->name('rtd.products.show');
    });

    #rtd order routes (ready-to-dispatch)
    Route::middleware(['token.exists', 'auth:sanctum'])->prefix('rtd/orders')->group(function () {
        Route::post('/', [RTDOrderController::class, 'requestOrder'])->name('rtd.orders.request');
        Route::post('/{id}/accept', [RTDOrderController::class, 'accept'])->name('rtd.orders.accept');
        Route::post('/{id}/decline', [RTDOrderController::class, 'decline'])->name('rtd.orders.decline');
        Route::post('/{id}/confirm-payment', [RTDOrderController::class, 'confirmPayment'])->name('rtd.orders.confirm-payment');
        Route::post('/{id}/payments/razorpay/order', [RTDOrderController::class, 'createRazorpayOrder'])->name('rtd.orders.payments.razorpay.order');
        Route::post('/{id}/payments/razorpay/verify', [RTDOrderController::class, 'verifyRazorpayPayment'])->name('rtd.orders.payments.razorpay.verify');
        Route::post('/{id}/cancel', [RTDOrderController::class, 'cancel'])->name('rtd.orders.cancel');
        Route::get('/my', [RTDOrderController::class, 'myOrders'])->name('rtd.orders.my');
        Route::get('/{id}', [RTDOrderController::class, 'show'])->name('rtd.orders.show');
    });

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

        // Razorpay wallet payments
        Route::post('/payments/razorpay/order', [WalletPaymentController::class, 'createOrder'])
            ->middleware('throttle:10,1')
            ->name('wallet.payments.razorpay.order');
        Route::post('/payments/razorpay/exact-credits-order', [WalletPaymentController::class, 'createExactCreditsOrder'])
            ->middleware('throttle:10,1')
            ->name('wallet.payments.razorpay.exact_credits_order');
        Route::post('/payments/razorpay/verify', [WalletPaymentController::class, 'verify'])
            ->middleware('throttle:30,1')
            ->name('wallet.payments.razorpay.verify');
    });

    #invoices (credit purchases, direct pay, RTD platform fees)
    Route::middleware(['token.exists', 'auth:sanctum'])->prefix('invoices')->group(function () {
        Route::get('/', [InvoiceController::class, 'index'])->name('invoices.index');
        Route::get('/{key}', [InvoiceController::class, 'show'])->name('invoices.show');
    });

    // PDF download via temporary signed URL (opened in the device browser — no bearer token available there).
    Route::get('invoices/{key}/download', [InvoiceController::class, 'download'])->name('invoices.download');

    #upload (single file for product image etc.)
    Route::middleware(['token.exists', 'auth:sanctum'])->post('upload/single', [UploadController::class, 'single'])->name('upload.single');

    #registration details (unified for all roles)
    Route::middleware(['token.exists', 'auth:sanctum'])->get('/registration-details', [RegistrationDetailsController::class, 'show'])->name('registration-details.show');
});