<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInquiryRequest;
use App\Models\Inquiry;
use App\Models\InquiryItem;
use App\Enums\InquiryStatus;
use App\Services\MatchmakingService;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Gate;

class InquiryController extends Controller
{
    public function __construct(
        protected MatchmakingService $matchmakingService
    ) {
    }

    /**
     * Create a new inquiry (DRAFT status)
     */
    public function store(StoreInquiryRequest $request)
    {
        try {
            $user = $request->user();
            $data = $request->validated();
            
            // Check if user has brand or converter profile
            $poster = $user->brand ?? $user->converter;
            if (!$poster) {
                return Response::error('Only brands or converters can create inquiries. Please complete your brand or converter profile first.', null, HttpResponse::HTTP_FORBIDDEN);
            }
            
            DB::beginTransaction();
            
            // Calculate total quantity from items (sum of all item quantities)
            $totalQuantity = 0;
            $quantityUnit = null;
            if (isset($data['items']) && is_array($data['items']) && count($data['items']) > 0) {
                foreach ($data['items'] as $itemData) {
                    $totalQuantity += (float) ($itemData['quantity'] ?? 0);
                    // Use the first item's unit, or default to 'pieces'
                    if (!$quantityUnit && isset($itemData['quantity_unit'])) {
                        $quantityUnit = $itemData['quantity_unit'];
                    }
                }
            }
            
            // Default values if no items provided
            if ($totalQuantity === 0) {
                $totalQuantity = 0;
                $quantityUnit = $quantityUnit ?? 'pieces';
            }
            
            // Create inquiry
            $inquiry = Inquiry::create([
                'poster_id' => $poster->id,
                'poster_type' => $user->brand ? 'brand' : 'converter',
                'brand_id' => $user->brand?->id,
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'status' => InquiryStatus::DRAFT,
                'urgency' => $data['urgency'] ?? 'normal',
                'quantity' => $totalQuantity,
                'quantity_unit' => $quantityUnit,
                'location' => $data['location'] ?? null,
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
                'timeline' => $data['timeline'] ?? null,
                'deadline' => $data['deadline'] ?? null,
                'is_visible_to_brand' => true,
                'is_visible_to_dealers' => false,
                'hide_brand_identity' => true,
                'hide_exact_location' => true,
            ]);
            
            // Create inquiry items
            if (isset($data['items']) && is_array($data['items'])) {
                foreach ($data['items'] as $itemData) {
                    InquiryItem::create([
                        'inquiry_id' => $inquiry->id,
                        'material_id' => $itemData['material_id'] ?? null,
                        'material_category' => $itemData['material_category'] ?? null,
                        'finish_coating' => $itemData['finish_coating'] ?? null,
                        'thickness_gsm' => $itemData['thickness_gsm'] ?? null,
                        'thickness_mm' => $itemData['thickness_mm'] ?? null,
                        'thickness_unit' => $itemData['thickness_unit'] ?? 'gsm',
                        'thickness_tolerance_percent' => $data['urgency'] === 'urgent' ? 10.0 : 5.0,
                        'thickness_tolerance_absolute' => $data['urgency'] === 'urgent' ? 0.3 : 0.2,
                        'quantity' => $itemData['quantity'],
                        'quantity_unit' => $itemData['quantity_unit'],
                        'additional_specs' => $itemData['additional_specs'] ?? null,
                    ]);
                }
            }
            
            DB::commit();
            
            $inquiry->load('items');
            
            return Response::success('Inquiry created successfully', $inquiry, null, HttpResponse::HTTP_CREATED);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return Response::error('Validation failed while creating inquiry', $e->errors(), HttpResponse::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Illuminate\Database\QueryException $e) {
            DB::rollBack();
            \Log::error('Database error creating inquiry', [
                'user_id' => $user->id ?? null,
                'error' => $e->getMessage(),
                'sql' => $e->getSql() ?? null,
            ]);
            return Response::error('Database error: Failed to create inquiry. Please check your data and try again.', [
                'error_code' => 'DB_ERROR',
                'message' => $e->getMessage(),
            ], HttpResponse::HTTP_INTERNAL_SERVER_ERROR);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error creating inquiry', [
                'user_id' => $user->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return Response::error('Failed to create inquiry: ' . $e->getMessage(), [
                'error_code' => 'INQUIRY_CREATE_ERROR',
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ], HttpResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Post inquiry (trigger matchmaking)
     */
    public function post(Request $request, Inquiry $inquiry)
    {
        try {
            Gate::authorize('post', $inquiry);
            
            $user = $request->user();
            
            // Check wallet balance (posting fee)
            $postingFee = $inquiry->urgency === 'urgent' ? 70 : 50; // Credits
            $wallet = $user->wallet;
            
            if (!$wallet) {
                return Response::error('Wallet not found. Please contact support to create a wallet.', [
                    'error_code' => 'WALLET_NOT_FOUND',
                    'user_id' => $user->id,
                ], HttpResponse::HTTP_PAYMENT_REQUIRED);
            }
            
            if ($wallet->balance < $postingFee) {
                return Response::error('Insufficient wallet balance', [
                    'error_code' => 'INSUFFICIENT_BALANCE',
                    'required_credits' => $postingFee,
                    'current_balance' => $wallet->balance,
                    'shortfall' => $postingFee - $wallet->balance,
                    'message' => "You need {$postingFee} credits to post this inquiry, but you only have {$wallet->balance} credits. Please purchase credits first.",
                ], HttpResponse::HTTP_PAYMENT_REQUIRED);
            }
            
            DB::beginTransaction();
            
            // Deduct posting fee
            // Parameters: amount, description, transaction_type, reference_id, reference_type, metadata
            $wallet->deductCredits(
                $postingFee, 
                'Post requirement fee', 
                'REQUIREMENT_POSTED', // transaction_type (must be from enum: PURCHASE, REQUIREMENT_POSTED, etc.)
                $inquiry->id, // reference_id
                'inquiry' // reference_type
            );
            
            // Update inquiry status to MATCHING (matchmaking starts immediately)
            $inquiry->update([
                'status' => InquiryStatus::MATCHING,
                'posted_at' => now(),
                'matching_started_at' => now(),
                'posting_fee_paid' => true,
                'posting_fee_amount' => $postingFee,
                'is_visible_to_dealers' => true,
            ]);
            
            // Create or update session (only use fields that exist in database)
            $session = $inquiry->session()->firstOrCreate([
                'inquiry_id' => $inquiry->id,
            ], [
                'status' => 'ACTIVE', // Use string value matching database enum
                'locked_at' => now(),
                'expires_at' => now()->addHours(24),
                'discovery_start' => now(),
                'active_session_start' => now(),
            ]);
            
            // Trigger matchmaking
            $matchedDealerIds = $this->matchmakingService->findMatchingDealers($inquiry, 10);
            
            // Session is already in ACTIVE status, no need to update
            
            // Notify matched dealers
            $this->matchmakingService->notifyMatchedDealers($inquiry, $matchedDealerIds);
            
            DB::commit();
            
            $inquiry->refresh();
            $inquiry->load(['session', 'items']);
            
            return Response::success('Inquiry posted successfully', [
                'inquiry' => $inquiry,
                'matched_dealers_count' => count($matchedDealerIds),
            ]);
            
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            DB::rollBack();
            return Response::error('Authorization failed: You do not have permission to post this inquiry', [
                'error_code' => 'AUTHORIZATION_FAILED',
                'inquiry_id' => $inquiry->id,
                'user_id' => $user->id ?? null,
                'message' => $e->getMessage(),
            ], HttpResponse::HTTP_FORBIDDEN);
        } catch (\Illuminate\Database\QueryException $e) {
            DB::rollBack();
            \Log::error('Database error posting inquiry', [
                'inquiry_id' => $inquiry->id,
                'user_id' => $user->id ?? null,
                'error' => $e->getMessage(),
                'sql' => $e->getSql() ?? null,
                'bindings' => $e->getBindings() ?? null,
                'code' => $e->getCode(),
            ]);
            return Response::error('Database error: Failed to post inquiry', [
                'error_code' => 'DB_ERROR',
                'inquiry_id' => $inquiry->id,
                'message' => $e->getMessage(),
                'sql_state' => $e->getCode(),
                'hint' => 'Check the SQL error message above for details about which field or constraint is causing the issue.',
            ], HttpResponse::HTTP_INTERNAL_SERVER_ERROR);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error posting inquiry', [
                'inquiry_id' => $inquiry->id,
                'user_id' => $user->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return Response::error('Failed to post inquiry: ' . $e->getMessage(), [
                'error_code' => 'INQUIRY_POST_ERROR',
                'inquiry_id' => $inquiry->id,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ], HttpResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get inquiry details
     */
    public function show(Request $request, Inquiry $inquiry)
    {
        try {
            Gate::authorize('view', $inquiry);
            
            $user = $request->user();
            
            // Load relationships
            $inquiry->load(['items', 'materials', 'session', 'responses']);
            
            // For dealers, return sanitized view
            if ($user->dealer) {
                $data = $inquiry->getDealerViewData();
            } else {
                $data = $inquiry->toArray();
            }
            
            return Response::success('Inquiry retrieved successfully', $data);
            
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return Response::error('Authorization failed: You do not have permission to view this inquiry', [
                'error_code' => 'AUTHORIZATION_FAILED',
                'inquiry_id' => $inquiry->id,
                'user_id' => $request->user()->id ?? null,
                'message' => $e->getMessage(),
            ], HttpResponse::HTTP_FORBIDDEN);
        } catch (\Exception $e) {
            \Log::error('Error retrieving inquiry', [
                'inquiry_id' => $inquiry->id,
                'user_id' => $request->user()->id ?? null,
                'error' => $e->getMessage(),
            ]);
            return Response::error('Failed to retrieve inquiry: ' . $e->getMessage(), [
                'error_code' => 'INQUIRY_RETRIEVE_ERROR',
                'inquiry_id' => $inquiry->id,
            ], HttpResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get inquiries for dealer (matched only)
     */
    public function dealerInquiries(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user->dealer) {
                return Response::error('Only dealers can access this endpoint', [
                    'error_code' => 'INVALID_USER_ROLE',
                    'user_id' => $user->id,
                    'user_roles' => [
                        'has_brand' => $user->brand ? true : false,
                        'has_converter' => $user->converter ? true : false,
                        'has_dealer' => false,
                        'has_machine_dealer' => $user->machineDealer ? true : false,
                    ],
                    'message' => 'Please complete your dealer profile to access dealer inquiries.',
                ], HttpResponse::HTTP_FORBIDDEN);
            }
            
            $dealerId = $user->dealer->id;
            
            // Get inquiries visible to this dealer
            $inquiries = Inquiry::visibleToDealer($dealerId)
                ->with(['items', 'materials'])
                ->when($request->status, function ($q, $status) {
                    $q->where('status', $status);
                })
                ->when($request->material_category, function ($q, $category) {
                    $q->whereHas('items', function ($query) use ($category) {
                        $query->where('material_category', $category);
                    });
                })
                ->orderBy('posted_at', 'desc')
                ->paginate($request->per_page ?? 15);
            
            // Sanitize for dealer view
            $inquiries->getCollection()->transform(function ($inquiry) {
                return $inquiry->getDealerViewData();
            });
            
            return Response::success('Inquiries retrieved successfully', $inquiries);
            
        } catch (\Exception $e) {
            return Response::error($e->getMessage(), null, HttpResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get responses for an inquiry (brand/converter only)
     */
    public function responses(Request $request, Inquiry $inquiry)
    {
        try {
            Gate::authorize('viewResponses', $inquiry);
            
            $responses = $inquiry->responses()
                ->with(['responder.dealer'])
                ->orderBy('created_at', 'desc')
                ->get();
            
            // Anonymize dealer info until lock
            if ($inquiry->status !== InquiryStatus::LOCKED && $inquiry->status !== InquiryStatus::CHAT_ACTIVE) {
                $responses->transform(function ($response) {
                    if ($response->responder && $response->responder->dealer) {
                        $dealer = $response->responder->dealer;
                        $response->responder->dealer = [
                            'id' => null,
                            'company_name' => null,
                            'location' => $dealer->locations->first()?->city ?? 'Unknown',
                        ];
                    }
                    return $response;
                });
            }
            
            return Response::success('Responses retrieved successfully', $responses);
            
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return Response::error('Authorization failed: You do not have permission to view responses for this inquiry', [
                'error_code' => 'AUTHORIZATION_FAILED',
                'inquiry_id' => $inquiry->id,
                'user_id' => $request->user()->id ?? null,
                'message' => $e->getMessage(),
            ], HttpResponse::HTTP_FORBIDDEN);
        } catch (\Exception $e) {
            \Log::error('Error retrieving inquiry responses', [
                'inquiry_id' => $inquiry->id,
                'user_id' => $request->user()->id ?? null,
                'error' => $e->getMessage(),
            ]);
            return Response::error('Failed to retrieve responses: ' . $e->getMessage(), [
                'error_code' => 'RESPONSES_RETRIEVE_ERROR',
                'inquiry_id' => $inquiry->id,
            ], HttpResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Republish inquiry
     */
    public function republish(Request $request, Inquiry $inquiry)
    {
        try {
            Gate::authorize('republish', $inquiry);
            
            DB::beginTransaction();
            
            // Create new inquiry based on old one
            $newInquiry = $inquiry->replicate();
            
            // For DRAFT inquiries, create a new DRAFT copy
            // For posted inquiries, create a new inquiry with MATCHING status
            if ($inquiry->status === InquiryStatus::DRAFT) {
                $newInquiry->status = InquiryStatus::DRAFT;
            } else {
                // For posted inquiries, republish as MATCHING (will trigger matchmaking again)
                $newInquiry->status = InquiryStatus::MATCHING;
            }
            
            $newInquiry->posted_at = null;
            $newInquiry->matching_started_at = null;
            $newInquiry->locked_at = null;
            $newInquiry->expires_at = null;
            $newInquiry->is_visible_to_dealers = false;
            $newInquiry->republish_count = ($inquiry->republish_count ?? 0) + 1;
            $newInquiry->last_republished_at = now();
            $newInquiry->cooldown_until = null; // Reset cooldown for new inquiry
            $newInquiry->save();
            
            // Copy items
            foreach ($inquiry->items as $item) {
                $newItem = $item->replicate();
                $newItem->inquiry_id = $newInquiry->id;
                $newItem->save();
            }
            
            // Update old inquiry
            $inquiry->update([
                'cooldown_until' => now()->addDays(7), // 7-day cooldown
            ]);
            
            DB::commit();
            
            return Response::success('Inquiry republished successfully', $newInquiry);
            
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            DB::rollBack();
            $user = $request->user();
            $isOwner = false;
            $reason = 'Unknown reason';
            
            // Check ownership
            if ($user->brand && $inquiry->poster_type === 'brand' && $inquiry->poster_id === $user->brand->id) {
                $isOwner = true;
            } elseif ($user->converter && $inquiry->poster_type === 'converter' && $inquiry->poster_id === $user->converter->id) {
                $isOwner = true;
            }
            
            if (!$isOwner) {
                $reason = 'You do not own this inquiry. Only the poster can republish it.';
            } else {
                // Check cooldown
                if ($inquiry->cooldown_until && $inquiry->cooldown_until > now()) {
                    $reason = 'Cooldown period has not expired. You can republish after ' . $inquiry->cooldown_until->format('Y-m-d H:i:s');
                } elseif (($inquiry->republish_count ?? 0) >= 1) {
                    $reason = 'Maximum republish limit reached. You can only republish an inquiry once.';
                } elseif ($inquiry->status === InquiryStatus::DRAFT) {
                    $reason = 'This inquiry is in DRAFT status. Republishing will create a copy. To post it, use POST /api/v1/inquiries/{id}/post instead.';
                } else {
                    $reason = 'Inquiry cannot be republished at this time. Current status: ' . $inquiry->status->value;
                }
            }
            
            return Response::error('Authorization failed: You do not have permission to republish this inquiry', [
                'error_code' => 'AUTHORIZATION_FAILED',
                'inquiry_id' => $inquiry->id,
                'user_id' => $user->id ?? null,
                'inquiry_status' => $inquiry->status->value,
                'is_owner' => $isOwner,
                'cooldown_until' => $inquiry->cooldown_until?->toIso8601String(),
                'republish_count' => $inquiry->republish_count ?? 0,
                'reason' => $reason,
                'message' => $e->getMessage(),
            ], HttpResponse::HTTP_FORBIDDEN);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error republishing inquiry', [
                'inquiry_id' => $inquiry->id,
                'user_id' => $request->user()->id ?? null,
                'error' => $e->getMessage(),
            ]);
            return Response::error('Failed to republish inquiry: ' . $e->getMessage(), [
                'error_code' => 'INQUIRY_REPUBLISH_ERROR',
                'inquiry_id' => $inquiry->id,
            ], HttpResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Save inquiry step (multi-step creation)
     * Screen: Post Requirement Screen (Step 2 of 9)
     */
    public function saveStep(\App\Http\Requests\SaveInquiryStepRequest $request)
    {
        try {
            $user = $request->user();
            
            // Check if user has brand or converter profile
            $poster = $user->brand ?? $user->converter;
            if (!$poster) {
                return Response::error('Only brands or converters can create inquiries. Please complete your brand or converter profile first.', [
                    'error_code' => 'PROFILE_INCOMPLETE',
                    'user_id' => $user->id,
                    'user_roles' => [
                        'has_brand' => $user->brand ? true : false,
                        'has_converter' => $user->converter ? true : false,
                        'has_dealer' => $user->dealer ? true : false,
                        'has_machine_dealer' => $user->machineDealer ? true : false,
                    ],
                    'message' => 'You need to complete either a brand profile or converter profile to create inquiries.',
                ], HttpResponse::HTTP_FORBIDDEN);
            }
            $data = $request->validated();
            $step = $data['step'];
            
            DB::beginTransaction();
            
            $poster = $user->brand ?? $user->converter;
            if (!$poster) {
                return Response::error('Only brands or converters can create inquiries', null, HttpResponse::HTTP_FORBIDDEN);
            }
            
            // Get or create inquiry
            if (isset($data['inquiry_id']) && $data['inquiry_id']) {
                $inquiry = Inquiry::where('id', $data['inquiry_id'])
                    ->where('poster_id', $poster->id)
                    ->where('poster_type', $user->brand ? 'brand' : 'converter')
                    ->where('status', InquiryStatus::DRAFT)
                    ->firstOrFail();
            } else {
                // Create new inquiry with default values
                // Title will be updated in later steps, but we need a default for database constraint
                $defaultTitle = 'Draft Inquiry - ' . now()->format('Y-m-d H:i');
                $inquiry = Inquiry::create([
                    'poster_id' => $poster->id,
                    'poster_type' => $user->brand ? 'brand' : 'converter',
                    'brand_id' => $user->brand?->id,
                    'title' => $data['title'] ?? $defaultTitle, // Use provided title or default
                    'description' => $data['description'] ?? null,
                    'status' => InquiryStatus::DRAFT,
                    'urgency' => $data['urgency'] ?? 'normal',
                    'quantity' => 0, // Will be updated from items
                    'quantity_unit' => 'pieces', // Default, will be updated from items
                    'is_visible_to_brand' => true,
                    'is_visible_to_dealers' => false,
                    'hide_brand_identity' => true,
                    'hide_exact_location' => true,
                ]);
            }
            
            // Step 2: Technical Specifications
            if ($step == 2) {
                // Update inquiry
                $updateData = [
                    'packaging_type' => $data['packaging_type'] ?? null,
                    'thickness' => $data['thickness_gsm'] ?? $data['thickness_mm'] ?? null,
                    'thickness_unit' => $data['thickness_unit'] ?? 'gsm',
                    'size' => $data['size'] ?? null,
                    'quantity' => $data['quantity'] ?? $inquiry->quantity ?? 0,
                    'quantity_unit' => $data['quantity_unit'] ?? $inquiry->quantity_unit ?? 'pieces',
                    'urgency' => $data['urgency'] ?? $inquiry->urgency ?? 'normal',
                    'location' => $data['location'] ?? $inquiry->location ?? null,
                    'latitude' => $data['latitude'] ?? $inquiry->latitude ?? null,
                    'longitude' => $data['longitude'] ?? $inquiry->longitude ?? null,
                ];
                
                // Update title if provided
                if (isset($data['title'])) {
                    $updateData['title'] = $data['title'];
                }
                
                // Update description if provided
                if (isset($data['description'])) {
                    $updateData['description'] = $data['description'];
                }
                
                $inquiry->update($updateData);
                
                // Create or update inquiry item
                $item = $inquiry->items()->firstOrCreate(
                    ['inquiry_id' => $inquiry->id],
                    [
                        'material_category' => $data['material_category'] ?? null,
                        'thickness_gsm' => $data['thickness_gsm'] ?? null,
                        'thickness_mm' => $data['thickness_mm'] ?? null,
                        'thickness_unit' => $data['thickness_unit'] ?? 'gsm',
                        'quantity' => $data['quantity'],
                        'quantity_unit' => $data['quantity_unit'],
                        'thickness_tolerance_percent' => ($data['urgency'] ?? 'normal') === 'urgent' ? 10.0 : 5.0,
                        'thickness_tolerance_absolute' => ($data['urgency'] ?? 'normal') === 'urgent' ? 0.3 : 0.2,
                    ]
                );
                
                if ($item->wasRecentlyCreated === false) {
                    $item->update([
                        'material_category' => $data['material_category'] ?? null,
                        'thickness_gsm' => $data['thickness_gsm'] ?? null,
                        'thickness_mm' => $data['thickness_mm'] ?? null,
                        'thickness_unit' => $data['thickness_unit'] ?? 'gsm',
                        'quantity' => $data['quantity'],
                        'quantity_unit' => $data['quantity_unit'],
                    ]);
                }
            }
            
            DB::commit();
            
            $inquiry->load('items');
            
            return Response::success('Step saved successfully', [
                'inquiry_id' => $inquiry->id,
                'step' => $step,
                'inquiry' => $inquiry,
            ]);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return Response::error('Validation failed while saving inquiry step', $e->errors(), HttpResponse::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            return Response::error('Inquiry not found', [
                'error_code' => 'INQUIRY_NOT_FOUND',
                'inquiry_id' => $request->validated()['inquiry_id'] ?? null,
                'message' => 'The inquiry you are trying to update does not exist or you do not have access to it.',
            ], HttpResponse::HTTP_NOT_FOUND);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error saving inquiry step', [
                'user_id' => $user->id ?? null,
                'step' => $request->validated()['step'] ?? null,
                'error' => $e->getMessage(),
            ]);
            return Response::error('Failed to save inquiry step: ' . $e->getMessage(), [
                'error_code' => 'INQUIRY_STEP_SAVE_ERROR',
                'step' => $request->validated()['step'] ?? null,
            ], HttpResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Calculate posting fee before posting
     * Screen: Payment Confirmation Screen
     */
    public function calculatePostingFee(\App\Http\Requests\CalculatePostingFeeRequest $request)
    {
        try {
            $user = $request->user();
            $inquiry = Inquiry::findOrFail($request->inquiry_id);
            
            Gate::authorize('post', $inquiry);
            
            // Calculate fees
            $standardFee = 50; // Credits
            $urgencyAddon = $inquiry->urgency === 'urgent' ? 20 : 0;
            $totalFee = $standardFee + $urgencyAddon;
            
            // Get wallet balance
            $wallet = $user->wallet;
            $availableBalance = $wallet ? $wallet->balance : 0;
            $sufficientCredits = $availableBalance >= $totalFee;
            
            return Response::success('Posting fee calculated', [
                'standard_fee' => $standardFee,
                'urgency_addon' => $urgencyAddon,
                'total_fee' => $totalFee,
                'wallet_balance' => $availableBalance,
                'sufficient_credits' => $sufficientCredits,
                'inquiry' => [
                    'id' => $inquiry->id,
                    'title' => $inquiry->title,
                    'urgency' => $inquiry->urgency,
                ],
            ]);
            
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return Response::error('Authorization failed: You do not have permission to calculate posting fee for this inquiry', [
                'error_code' => 'AUTHORIZATION_FAILED',
                'inquiry_id' => $request->inquiry_id,
                'user_id' => $user->id ?? null,
                'message' => $e->getMessage(),
            ], HttpResponse::HTTP_FORBIDDEN);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return Response::error('Inquiry not found', [
                'error_code' => 'INQUIRY_NOT_FOUND',
                'inquiry_id' => $request->inquiry_id,
                'message' => 'The inquiry you are looking for does not exist.',
            ], HttpResponse::HTTP_NOT_FOUND);
        } catch (\Exception $e) {
            \Log::error('Error calculating posting fee', [
                'inquiry_id' => $request->inquiry_id,
                'user_id' => $user->id ?? null,
                'error' => $e->getMessage(),
            ]);
            return Response::error('Failed to calculate posting fee: ' . $e->getMessage(), [
                'error_code' => 'FEE_CALCULATION_ERROR',
                'inquiry_id' => $request->inquiry_id,
            ], HttpResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get posting success details with matchmaking status
     * Screen: Posting Success Screen
     */
    public function getPostingStatus(Request $request, Inquiry $inquiry)
    {
        try {
            Gate::authorize('view', $inquiry);
            
            $session = $inquiry->session;
            $matchmakingLogs = $inquiry->matchmakingLogs()->where('is_visible', true)->get();
            
            // Calculate matchmaking progress
            $totalDealersScanned = \App\Models\Dealer::where('status', 'active')->count();
            $matchedDealers = $matchmakingLogs->count();
            $progressPercent = $totalDealersScanned > 0 ? ($matchedDealers / $totalDealersScanned) * 100 : 0;
            
            return Response::success('Posting status retrieved', [
                'inquiry' => [
                    'id' => $inquiry->id,
                    'title' => $inquiry->title,
                    'status' => $inquiry->status->value,
                    'posted_at' => $inquiry->posted_at,
                    'items' => $inquiry->items->map(function ($item) {
                        return [
                            'material_category' => $item->material_category,
                            'quantity' => $item->quantity,
                            'quantity_unit' => $item->quantity_unit,
                        ];
                    }),
                ],
                'matchmaking' => [
                    'status' => $session ? $session->status->value : 'FINDING',
                    'matched_dealers_count' => $matchedDealers,
                    'total_dealers_scanned' => $totalDealersScanned,
                    'progress_percent' => round($progressPercent, 1),
                    'is_finding_matches' => $session && $session->status === \App\Enums\SessionStatus::MATCHING,
                ],
                'session_id' => $session ? $session->id : null,
            ]);
            
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return Response::error('Authorization failed: You do not have permission to view posting status for this inquiry', [
                'error_code' => 'AUTHORIZATION_FAILED',
                'inquiry_id' => $inquiry->id,
                'user_id' => $request->user()->id ?? null,
                'message' => $e->getMessage(),
            ], HttpResponse::HTTP_FORBIDDEN);
        } catch (\Exception $e) {
            \Log::error('Error retrieving posting status', [
                'inquiry_id' => $inquiry->id,
                'user_id' => $request->user()->id ?? null,
                'error' => $e->getMessage(),
            ]);
            return Response::error('Failed to retrieve posting status: ' . $e->getMessage(), [
                'error_code' => 'POSTING_STATUS_ERROR',
                'inquiry_id' => $inquiry->id,
            ], HttpResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get matchmaking responses with filters
     * Screen: Matchmaking Responses Screen
     */
    public function getMatchmakingResponses(Request $request, Inquiry $inquiry)
    {
        try {
            Gate::authorize('viewResponses', $inquiry);
            
            $filter = $request->input('filter', 'all'); // all, exact_match, slight_variation, nearest
            $responses = $inquiry->responses()
                ->with(['responder.dealer.locations'])
                ->get();
            
            // Get matchmaking logs for scoring
            $matchmakingLogs = $inquiry->matchmakingLogs()
                ->whereIn('dealer_id', $responses->pluck('responder.dealer.id')->filter())
                ->get()
                ->keyBy('dealer_id');
            
            // Enhance responses with matchmaking data
            $enhancedResponses = $responses->map(function ($response) use ($matchmakingLogs, $inquiry) {
                $dealer = $response->responder->dealer ?? null;
                $log = $dealer ? $matchmakingLogs->get($dealer->id) : null;
                
                // Calculate distance
                $distance = null;
                if ($inquiry->latitude && $inquiry->longitude && $dealer) {
                    $dealerLocation = $dealer->locations->first();
                    if ($dealerLocation && $dealerLocation->latitude && $dealerLocation->longitude) {
                        $distance = $this->calculateDistance(
                            $inquiry->latitude,
                            $inquiry->longitude,
                            $dealerLocation->latitude,
                            $dealerLocation->longitude
                        );
                    }
                }
                
                // Determine match type
                $matchType = 'exact_match';
                if ($log) {
                    if (!$log->material_match || !$log->thickness_match) {
                        $matchType = 'slight_variation';
                    }
                }
                
                return [
                    'id' => $response->id,
                    'match_type' => $matchType,
                    'distance_km' => $distance,
                    'dealer' => [
                        'id' => $inquiry->status === InquiryStatus::LOCKED ? ($dealer->id ?? null) : null,
                        'company_name' => $inquiry->status === InquiryStatus::LOCKED ? ($dealer->company_name ?? null) : null,
                        'location' => $dealer ? ($dealer->locations->first()?->city . ', ' . $dealer->locations->first()?->state) : 'Unknown',
                    ],
                    'quantity_offered' => $response->quantity_offered,
                    'quoted_price' => $response->quoted_price,
                    'price_status' => $response->price_status,
                    'additional_details' => $response->additional_details,
                    'responded_at' => $response->created_at,
                    'is_shortlisted' => $response->session && $response->session->participants()
                        ->where('participant_type', 'dealer')
                        ->where('participant_id', $dealer->id ?? 0)
                        ->where('is_selected', true)
                        ->exists(),
                ];
            });
            
            // Apply filters
            if ($filter === 'exact_match') {
                $enhancedResponses = $enhancedResponses->where('match_type', 'exact_match');
            } elseif ($filter === 'slight_variation') {
                $enhancedResponses = $enhancedResponses->where('match_type', 'slight_variation');
            } elseif ($filter === 'nearest') {
                $enhancedResponses = $enhancedResponses->sortBy('distance_km');
            }
            
            // Sort by distance if nearest filter
            if ($filter === 'nearest') {
                $enhancedResponses = $enhancedResponses->values();
            } else {
                $enhancedResponses = $enhancedResponses->sortByDesc('responded_at')->values();
            }
            
            // Get countdown if session exists
            $countdown = null;
            $session = $inquiry->session;
            if ($session && $session->expires_at) {
                $secondsLeft = max(0, now()->diffInSeconds($session->expires_at, false));
                $countdown = [
                    'hours' => floor($secondsLeft / 3600),
                    'minutes' => floor(($secondsLeft % 3600) / 60),
                    'seconds' => $secondsLeft % 60,
                ];
            }
            
            return Response::success('Matchmaking responses retrieved', [
                'inquiry' => [
                    'id' => $inquiry->id,
                    'title' => $inquiry->title,
                    'items' => $inquiry->items->map(function ($item) {
                        return [
                            'material_category' => $item->material_category,
                            'quantity' => $item->quantity,
                            'quantity_unit' => $item->quantity_unit,
                        ];
                    }),
                ],
                'countdown' => $countdown,
                'responses_count' => $enhancedResponses->count(),
                'responses' => $enhancedResponses,
                'filter' => $filter,
            ]);
            
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return Response::error('Authorization failed: You do not have permission to view matchmaking responses for this inquiry', [
                'error_code' => 'AUTHORIZATION_FAILED',
                'inquiry_id' => $inquiry->id,
                'user_id' => $request->user()->id ?? null,
                'message' => $e->getMessage(),
            ], HttpResponse::HTTP_FORBIDDEN);
        } catch (\Exception $e) {
            \Log::error('Error retrieving matchmaking responses', [
                'inquiry_id' => $inquiry->id,
                'user_id' => $request->user()->id ?? null,
                'error' => $e->getMessage(),
            ]);
            return Response::error('Failed to retrieve matchmaking responses: ' . $e->getMessage(), [
                'error_code' => 'MATCHMAKING_RESPONSES_ERROR',
                'inquiry_id' => $inquiry->id,
            ], HttpResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Shortlist or reject a response
     * Screen: Matchmaking Responses Screen (Shortlist/Reject buttons)
     */
    public function shortlistResponse(\App\Http\Requests\ShortlistResponseRequest $request, \App\Models\Response $response)
    {
        try {
            $inquiry = $response->inquiry;
            Gate::authorize('viewResponses', $inquiry);
            
            // Can only shortlist if session is not locked yet
            if ($inquiry->status === InquiryStatus::LOCKED) {
                return Response::error('Session already locked', [
                    'error_code' => 'SESSION_LOCKED',
                    'inquiry_id' => $inquiry->id,
                    'session_id' => $inquiry->session?->id,
                    'message' => 'This session has already been locked. You cannot modify responses anymore.',
                ], HttpResponse::HTTP_BAD_REQUEST);
            }
            
            $action = $request->validated()['action'];
            $dealer = $response->responder->dealer;
            
            if (!$dealer) {
                return Response::error('Dealer not found', null, HttpResponse::HTTP_NOT_FOUND);
            }
            
            // Update matchmaking log
            $matchmakingLog = $inquiry->matchmakingLogs()
                ->where('dealer_id', $dealer->id)
                ->first();
            
            if ($matchmakingLog) {
                if ($action === 'shortlist') {
                    $matchmakingLog->update(['is_selected' => true, 'selected_at' => now()]);
                } else {
                    $matchmakingLog->update(['is_selected' => false]);
                }
            }
            
            return Response::success("Response {$action}ed successfully", [
                'response_id' => $response->id,
                'action' => $action,
                'is_shortlisted' => $action === 'shortlist',
            ]);
            
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return Response::error('Authorization failed: You do not have permission to shortlist this response', [
                'error_code' => 'AUTHORIZATION_FAILED',
                'response_id' => $response->id,
                'inquiry_id' => $inquiry->id ?? null,
                'user_id' => $request->user()->id ?? null,
                'message' => $e->getMessage(),
            ], HttpResponse::HTTP_FORBIDDEN);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return Response::error('Response or dealer not found', [
                'error_code' => 'RESOURCE_NOT_FOUND',
                'response_id' => $response->id,
                'message' => 'The response or associated dealer could not be found.',
            ], HttpResponse::HTTP_NOT_FOUND);
        } catch (\Exception $e) {
            \Log::error('Error shortlisting response', [
                'response_id' => $response->id,
                'inquiry_id' => $inquiry->id ?? null,
                'user_id' => $request->user()->id ?? null,
                'error' => $e->getMessage(),
            ]);
            return Response::error('Failed to shortlist response: ' . $e->getMessage(), [
                'error_code' => 'SHORTLIST_ERROR',
                'response_id' => $response->id,
            ], HttpResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Helper: Calculate distance between two coordinates
     */
    private function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371; // km
        
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        
        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);
        
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        
        return round($earthRadius * $c, 1);
    }
}
