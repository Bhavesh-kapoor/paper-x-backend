<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Enums\InquiryIntent;
use App\Enums\InquiryType;
use App\Models\Inquiry;
use App\Services\SessionService;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Response;

class SessionController extends Controller
{
    public function __construct(
        protected SessionService $sessionService
    ) {
    }

    public function getSession(int $sessionId)
    {
        try {
            $user = request()->user();
            
            // Ensure user's role relationships are loaded for policy checks
            if (!$user->relationLoaded('dealer')) {
                $user->load('dealer');
            }
            if (!$user->relationLoaded('brand')) {
                $user->load('brand');
            }
            if (!$user->relationLoaded('converter')) {
                $user->load('converter');
            }
            if (!$user->relationLoaded('machineDealer')) {
                $user->load('machineDealer');
            }
            
            $session = \App\Models\MatchingSession::with([
                'inquiry', // Load full inquiry with poster_id and poster_type
                'inquiry.items', 
                'inquiry.matchmakingLogs', // Load matchmaking logs for policy check
                'participants.participant', 
                'chatThread'
            ])
                ->findOrFail($sessionId);
            
            // Ensure inquiry is loaded before policy check
            if (!$session->relationLoaded('inquiry')) {
                $session->load('inquiry');
            }
            
            // Double-check: If dealer can see this in active list, they should see details
            // This is a safety net in case policy has issues
            $inquiry = $session->inquiry;
            $isPoster = false;
            if ($inquiry) {
                $pt = $inquiry->poster_type;
                $pid = $inquiry->poster_id;
                if ($user->dealer && (int) $pid === (int) $user->dealer->id && $pt === 'dealer') {
                    $isPoster = true;
                } elseif ($user->converter && (int) $pid === (int) $user->converter->id && $pt === 'converter') {
                    $isPoster = true;
                } elseif ($user->brand && (int) $pid === (int) $user->brand->id && $pt === 'brand') {
                    $isPoster = true;
                } elseif ($user->machineDealer && (int) $pid === (int) $user->machineDealer->id && $pt === 'machine_dealer') {
                    $isPoster = true;
                }
            }
            if (!$isPoster) {
                $hasLog = $inquiry && \App\Models\MatchmakingLog::where('inquiry_id', $inquiry->id)
                    ->where(function ($q) use ($user) {
                        if ($user->dealer) {
                            $q->where('dealer_id', $user->dealer->id);
                        } elseif ($user->converter) {
                            $q->where('converter_id', $user->converter->id);
                        } elseif ($user->machineDealer) {
                            $q->where('machine_dealer_id', $user->machineDealer->id);
                        } else {
                            $q->whereRaw('0=1');
                        }
                    })->exists();
                if (!$hasLog) {
                    \Illuminate\Support\Facades\Gate::authorize('view', $session);
                }
            }

            $inquiry = $session->inquiry;
            $posterType = $inquiry->poster_type;
            $posterId = $inquiry->poster_id;

            $isOwner = false;
            if ($posterType === 'dealer' && $user->dealer && (int) $posterId === (int) $user->dealer->id) {
                $isOwner = true;
            } elseif ($posterType === 'converter' && $user->converter && (int) $posterId === (int) $user->converter->id) {
                $isOwner = true;
            } elseif ($posterType === 'brand' && $user->brand && (int) $posterId === (int) $user->brand->id) {
                $isOwner = true;
            } elseif ($posterType === 'machine_dealer' && $user->machineDealer && (int) $posterId === (int) $user->machineDealer->id) {
                $isOwner = true;
            }

            $posterLabel = 'A dealer';
            if (!$isOwner && $posterType) {
                $posterLabel = match ($posterType) {
                    'dealer' => 'A dealer',
                    'converter' => 'A converter',
                    'brand' => 'A brand',
                    'machine_dealer' => 'A machine dealer',
                    default => 'A dealer',
                };
            }

            // Get selected partners (for locked session)
            $selectedPartners = [];
            if ($session->status === \App\Enums\SessionStatus::LOCKED || $session->status === \App\Enums\SessionStatus::CHAT_ACTIVE) {
                $selectedPartners = $session->participants()
                    ->where('is_selected', true)
                    ->where('role', 'responder')
                    ->with('participant')
                    ->get()
                    ->map(function ($participant) {
                        $entity = $participant->participant;
                        if (!$entity) {
                            return null;
                        }

                        if ($entity instanceof \App\Models\Dealer) {
                            $user = $entity->user;
                            return [
                                'id' => $entity->id,
                                'company_name' => $user->company_name ?? $user->name ?? 'Unknown',
                                'location' => $entity->locations->first()?->city ?? $user->city ?? 'Unknown',
                            ];
                        }

                        if ($entity instanceof \App\Models\Converter) {
                            $user = $entity->user;
                            return [
                                'id' => $entity->id,
                                'company_name' => $user->company_name ?? $user->name ?? 'Unknown',
                                'location' => $entity->factory_city ?? $user->city ?? 'Unknown',
                            ];
                        }

                        if ($entity instanceof \App\Models\Brand) {
                            return [
                                'id' => $entity->id,
                                'company_name' => $entity->brand_name ?? $entity->company_name ?? 'Brand',
                                'location' => $entity->city ?? 'Unknown',
                            ];
                        }

                        return null;
                    })
                    ->filter()
                    ->values();
            }
            
            $intent = $inquiry->intent?->value ?? $inquiry->intent ?? 'buy';

            $myResponderStatus = null;
            if (!$isOwner) {
                $myLog = \App\Models\MatchmakingLog::where('inquiry_id', $inquiry->id)
                    ->where(function ($q) use ($user) {
                        if ($user->dealer) {
                            $q->where('dealer_id', $user->dealer->id);
                        } elseif ($user->converter) {
                            $q->where('converter_id', $user->converter->id);
                        } elseif ($user->machineDealer) {
                            $q->where('machine_dealer_id', $user->machineDealer->id);
                        } else {
                            $q->whereRaw('0=1');
                        }
                    })->first();
                if ($myLog) {
                    $myResponderStatus = [
                        'expressed_interest' => (bool) $myLog->responded_at,
                        'shortlisted' => (bool) $myLog->is_selected,
                        'declined' => (bool) $myLog->declined_at,
                    ];
                }
            }

            // Format session data
            $sessionData = [
                'id' => $session->id,
                'project_id' => 'PRJ-' . str_pad($session->id, 4, '0', STR_PAD_LEFT),
                'status' => $session->status->value,
                'inquiry' => [
                    'id' => $inquiry->id,
                    'title' => $inquiry->title,
                    'items' => $inquiry->items,
                    'intent' => $intent,
                ],
                'selected_partners_count' => count($selectedPartners),
                'selected_partners' => $selectedPartners,
                'chat_enabled' => $session->chat_enabled,
                'chat_thread_id' => $session->chatThread?->id,
                'expires_at' => $session->expires_at,
                'locked_at' => $session->locked_at,
                'is_owner' => $isOwner,
                'poster_label' => $posterLabel,
                'intent' => $intent,
                'my_responder_status' => $myResponderStatus,
            ];

            return Response::success('Session details retrieved', $sessionData);
            
        } catch (\Exception $e) {
            return Response::error(
                $e->getMessage(),
                null,
                method_exists($e, 'getStatusCode') ? $e->getStatusCode() : HttpResponse::HTTP_BAD_REQUEST
            );
        }
    }

    /**
     * Get poster detail (owner only).
     * Returns data for the poster's detail screen: requirement summary and counts only. No response list, no names.
     * GET /sessions/{session}/poster-detail
     */
    public function getPosterDetail(int $session)
    {
        try {
            $user = request()->user();
            foreach (['dealer', 'brand', 'converter', 'machineDealer'] as $rel) {
                if (!$user->relationLoaded($rel)) {
                    $user->load($rel);
                }
            }

            $sessionModel = \App\Models\MatchingSession::with(['inquiry', 'inquiry.items'])
                ->findOrFail($session);

            $inquiry = $sessionModel->inquiry;
            if (!$inquiry) {
                return Response::error('Inquiry not found', null, HttpResponse::HTTP_NOT_FOUND);
            }

            $posterType = $inquiry->poster_type;
            $posterId = $inquiry->poster_id;
            $isOwner = false;
            if ($posterType === 'dealer' && $user->dealer && (int) $posterId === (int) $user->dealer->id) {
                $isOwner = true;
            } elseif ($posterType === 'converter' && $user->converter && (int) $posterId === (int) $user->converter->id) {
                $isOwner = true;
            } elseif ($posterType === 'brand' && $user->brand && (int) $posterId === (int) $user->brand->id) {
                $isOwner = true;
            } elseif ($posterType === 'machine_dealer' && $user->machineDealer && (int) $posterId === (int) $user->machineDealer->id) {
                $isOwner = true;
            }

            if (!$isOwner) {
                return Response::error('Only the poster can view this detail', null, HttpResponse::HTTP_FORBIDDEN);
            }

            $intent = $inquiry->intent && is_object($inquiry->intent) ? $inquiry->intent->value : ($inquiry->intent ?? 'buy');

            $reached_count = \App\Models\MatchmakingLog::where('inquiry_id', $inquiry->id)
                ->where('is_visible', true)
                ->count();
            $responded_count = \App\Models\MatchmakingLog::where('inquiry_id', $inquiry->id)
                ->whereNotNull('responded_at')
                ->count();
            $matches_count = $reached_count;
            $responses_count = $responded_count;

            // Default requirement summary (dealer/converter flows)
            $requirement_summary = [
                'title' => $inquiry->title,
                'material' => $inquiry->items->first()->material_category ?? null,
                'quantity' => $inquiry->quantity,
                'quantity_unit' => $inquiry->quantity_unit,
                'urgency' => $inquiry->urgency ?? 'normal',
                'items' => $inquiry->items->map(function ($item) {
                    return [
                        'material_category' => $item->material_category ?? null,
                        'quantity' => $item->quantity ?? null,
                        'quantity_unit' => $item->quantity_unit ?? null,
                    ];
                })->values()->all(),
            ];

            // Brand → Converter: use brand requirement fields instead of items
            if ($posterType === 'brand') {
                $requirement_summary = [
                    'title' => $inquiry->title,
                    'requirement_type' => $inquiry->requirement_type,
                    'packaging_type' => $inquiry->packaging_type,
                    'quantity_range' => $inquiry->quantity_range,
                    'timeline' => $inquiry->timeline,
                    'urgency' => $inquiry->urgency ?? 'normal',
                    'description' => $inquiry->description,
                    'special_needs' => $inquiry->special_needs,
                ];
            }

            $data = [
                'poster_type' => $posterType,
                'intent' => $intent,
                'title' => $inquiry->title,
                'requirement_summary' => $requirement_summary,
                'created_at' => $inquiry->created_at?->toIso8601String(),
                'reached_count' => $reached_count,
                'matches_count' => $matches_count,
                'responses_count' => $responses_count,
            ];

            // Converter jobwork: expose jobwork_mode, jobwork details, and inquiry_type (jobwork_find | jobwork_give)
            $jobwork = $this->jobworkDetails($inquiry);
            if ($jobwork !== null) {
                $data['jobwork_mode'] = $jobwork['mode'];
                $data['jobwork'] = $jobwork;
                $data['inquiry_type'] = $jobwork['mode'] === 'find' ? 'jobwork_find' : 'jobwork_give';
                $jobworkSample = $this->jobworkSampleFields($inquiry);
                if ($jobworkSample !== null) {
                    $data['sample_available'] = $jobworkSample['sample_available'];
                    $data['sample_image_url'] = $jobworkSample['sample_image_url'];
                }
            }

            return Response::success('Poster detail retrieved', $data);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return Response::error('Session not found', null, HttpResponse::HTTP_NOT_FOUND);
        } catch (\Exception $e) {
            return Response::error(
                $e->getMessage(),
                null,
                method_exists($e, 'getStatusCode') ? $e->getStatusCode() : HttpResponse::HTTP_BAD_REQUEST
            );
        }
    }

    /**
     * Get responder detail (non-owner only).
     * Returns data for the responder's detail screen: "someone wants to buy/sell", poster type, requirement summary.
     * GET /sessions/{session}/responder-detail
     */
    public function getResponderDetail(int $session)
    {
        try {
            $user = request()->user();
            foreach (['dealer', 'brand', 'converter', 'machineDealer'] as $rel) {
                if (!$user->relationLoaded($rel)) {
                    $user->load($rel);
                }
            }

            $sessionModel = \App\Models\MatchingSession::with(['inquiry', 'inquiry.items'])
                ->findOrFail($session);

            $inquiry = $sessionModel->inquiry;
            if (!$inquiry) {
                return Response::error('Inquiry not found', null, HttpResponse::HTTP_NOT_FOUND);
            }

            $posterType = $inquiry->poster_type;
            $posterId = $inquiry->poster_id;
            $isOwner = false;
            if ($posterType === 'dealer' && $user->dealer && (int) $posterId === (int) $user->dealer->id) {
                $isOwner = true;
            } elseif ($posterType === 'converter' && $user->converter && (int) $posterId === (int) $user->converter->id) {
                $isOwner = true;
            } elseif ($posterType === 'brand' && $user->brand && (int) $posterId === (int) $user->brand->id) {
                $isOwner = true;
            } elseif ($posterType === 'machine_dealer' && $user->machineDealer && (int) $posterId === (int) $user->machineDealer->id) {
                $isOwner = true;
            }

            if ($isOwner) {
                return Response::error('Use poster-detail for your own post', null, HttpResponse::HTTP_FORBIDDEN);
            }

            $hasLog = \App\Models\MatchmakingLog::where('inquiry_id', $inquiry->id)
                ->where(function ($q) use ($user) {
                    if ($user->dealer) {
                        $q->where('dealer_id', $user->dealer->id);
                    } elseif ($user->converter) {
                        $q->where('converter_id', $user->converter->id);
                    } elseif ($user->machineDealer) {
                        $q->where('machine_dealer_id', $user->machineDealer->id);
                    } else {
                        $q->whereRaw('0=1');
                    }
                })->where('is_visible', true)->exists();

            $myLog = \App\Models\MatchmakingLog::where('inquiry_id', $inquiry->id)
                ->where(function ($q) use ($user) {
                    if ($user->dealer) {
                        $q->where('dealer_id', $user->dealer->id);
                    } elseif ($user->converter) {
                        $q->where('converter_id', $user->converter->id);
                    } elseif ($user->machineDealer) {
                        $q->where('machine_dealer_id', $user->machineDealer->id);
                    } else {
                        $q->whereRaw('0=1');
                    }
                })->first();

            if (!$myLog || !$myLog->is_visible) {
                return Response::error('You do not have access to this requirement', null, HttpResponse::HTTP_FORBIDDEN);
            }

            $intent = $inquiry->intent && is_object($inquiry->intent) ? $inquiry->intent->value : ($inquiry->intent ?? 'buy');
            $posterLabel = match ($posterType) {
                'dealer' => 'A dealer',
                'converter' => 'A converter',
                'brand' => 'A brand',
                'machine_dealer' => 'A machine dealer',
                default => 'Someone',
            };

            // Default requirement summary (dealer/converter flows)
            $requirement_summary = [
                'title' => $inquiry->title,
                'material' => $inquiry->items->first()->material_category ?? null,
                'quantity' => $inquiry->quantity,
                'quantity_unit' => $inquiry->quantity_unit,
                'urgency' => $inquiry->urgency ?? 'normal',
                'items' => $inquiry->items->map(function ($item) {
                    return [
                        'material_category' => $item->material_category ?? null,
                        'quantity' => $item->quantity ?? null,
                        'quantity_unit' => $item->quantity_unit ?? null,
                    ];
                })->values()->all(),
            ];

            // Brand → Converter: use brand requirement fields instead of items
            if ($posterType === 'brand') {
                $requirement_summary = [
                    'title' => $inquiry->title,
                    'requirement_type' => $inquiry->requirement_type,
                    'packaging_type' => $inquiry->packaging_type,
                    'quantity_range' => $inquiry->quantity_range,
                    'timeline' => $inquiry->timeline,
                    'urgency' => $inquiry->urgency ?? 'normal',
                    'description' => $inquiry->description,
                    'special_needs' => $inquiry->special_needs,
                ];
            }

            $data = [
                'inquiry_id' => $inquiry->id,
                'intent' => $intent,
                'poster_label' => $posterLabel,
                'title' => $inquiry->title,
                'requirement_summary' => $requirement_summary,
                'created_at' => $inquiry->created_at?->toIso8601String(),
                'expires_at' => $sessionModel->expires_at?->toIso8601String(),
                'my_responder_status' => [
                    'expressed_interest' => (bool) $myLog->responded_at,
                    'shortlisted' => (bool) $myLog->is_selected,
                    'declined' => (bool) $myLog->declined_at,
                ],
            ];

            // Converter jobwork: expose jobwork_mode, jobwork details, and inquiry_type (jobwork_find | jobwork_give)
            $jobwork = $this->jobworkDetails($inquiry);
            if ($jobwork !== null) {
                $data['jobwork_mode'] = $jobwork['mode'];
                $data['jobwork'] = $jobwork;
                $data['inquiry_type'] = $jobwork['mode'] === 'find' ? 'jobwork_find' : 'jobwork_give';
                $jobworkSample = $this->jobworkSampleFields($inquiry);
                if ($jobworkSample !== null) {
                    $data['sample_available'] = $jobworkSample['sample_available'];
                    $data['sample_image_url'] = $jobworkSample['sample_image_url'];
                }
            }

            return Response::success('Responder detail retrieved', $data);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return Response::error('Session not found', null, HttpResponse::HTTP_NOT_FOUND);
        } catch (\Exception $e) {
            return Response::error(
                $e->getMessage(),
                null,
                method_exists($e, 'getStatusCode') ? $e->getStatusCode() : HttpResponse::HTTP_BAD_REQUEST
            );
        }
    }

    /**
     * For converter jobwork inquiries (JOB type, converter poster), return normalized jobwork details from specs + inquiry.
     * Returns null for non-jobwork inquiries.
     */
    private function jobworkDetails(Inquiry $inquiry): ?array
    {
        if ($inquiry->inquiry_type !== InquiryType::JOB || $inquiry->poster_type !== 'converter') {
            return null;
        }
        $intentVal = $inquiry->intent instanceof InquiryIntent ? $inquiry->intent->value : (string) ($inquiry->intent ?? 'buy');
        $jobworkMode = $intentVal === 'sell' ? 'find' : 'give';
        $specs = $inquiry->specs ?? [];

        $jobworkType = $specs['jobwork_type'] ?? $inquiry->job_type ?? null;
        $base = [
            'mode' => $jobworkMode,
            'jobwork_type' => $jobworkType ? (string) $jobworkType : null,
            'quantity' => $inquiry->quantity,
            'quantity_unit' => $inquiry->quantity_unit ?? null,
            'timeline' => $inquiry->timeline ?? null,
            'location' => $inquiry->location ?? null,
        ];

        if ($jobworkMode === 'find') {
            $base['machinery_available'] = $specs['machinery_available'] ?? null;
            $base['city'] = $specs['city'] ?? null;
            $base['sample_available'] = (bool) ($specs['sample_available'] ?? false);
            $base['special_instructions'] = $specs['special_instructions'] ?? null;
        } else {
            $base['raw_materials'] = $specs['raw_materials'] ?? null;
            $base['size'] = $specs['size'] ?? null;
            $base['size_unit'] = $specs['size_unit'] ?? null;
            $base['thickness'] = $specs['thickness'] ?? null;
            $base['thickness_unit'] = $specs['thickness_unit'] ?? null;
            $base['grade_finish'] = $specs['grade_finish'] ?? null;
            $base['quality_requirements'] = $specs['quality_requirements'] ?? null;
            $base['other_instructions'] = $specs['other_instructions'] ?? null;
        }

        return $base;
    }

    /**
     * For jobwork-find inquiries (JOB type, converter poster), return sample_available and sample_image_url.
     * Returns null for other inquiry types so callers can omit the fields.
     */
    private function jobworkSampleFields(Inquiry $inquiry): ?array
    {
        if ($inquiry->inquiry_type !== InquiryType::JOB || $inquiry->poster_type !== 'converter') {
            return null;
        }
        $specs = $inquiry->specs ?? [];
        $sampleAvailable = (bool) ($specs['sample_available'] ?? false);
        $sampleImage = $specs['sample_image'] ?? null;
        $sampleImageUrl = null;
        if ($sampleImage && is_string($sampleImage) && $sampleImage !== '') {
            $sampleImageUrl = str_starts_with($sampleImage, 'http')
                ? $sampleImage
                : asset(ltrim(str_replace('\\', '/', $sampleImage), '/'));
        }
        return [
            'sample_available' => $sampleAvailable,
            'sample_image_url' => $sampleImageUrl,
        ];
    }

    /**
     * Get session by inquiry id.
     * Use this when you have inquiry id (e.g. from requirements list) but need session detail.
     * GET /sessions/by-inquiry/{inquiry_id}
     */
    public function getSessionByInquiry(int $inquiry_id)
    {
        try {
            $user = request()->user();
            foreach (['dealer', 'brand', 'converter', 'machineDealer'] as $rel) {
                if (!$user->relationLoaded($rel)) {
                    $user->load($rel);
                }
            }

            $session = \App\Models\MatchingSession::with([
                'inquiry', 'inquiry.items', 'inquiry.matchmakingLogs',
                'participants.participant', 'chatThread',
            ])->where('inquiry_id', $inquiry_id)->firstOrFail();

            \Illuminate\Support\Facades\Gate::authorize('view', $session);

            $inquiry = $session->inquiry;
            $posterType = $inquiry->poster_type;
            $posterId = $inquiry->poster_id;

            $isOwner = false;
            if ($posterType === 'dealer' && $user->dealer && (int) $posterId === (int) $user->dealer->id) {
                $isOwner = true;
            } elseif ($posterType === 'converter' && $user->converter && (int) $posterId === (int) $user->converter->id) {
                $isOwner = true;
            } elseif ($posterType === 'brand' && $user->brand && (int) $posterId === (int) $user->brand->id) {
                $isOwner = true;
            } elseif ($posterType === 'machine_dealer' && $user->machineDealer && (int) $posterId === (int) $user->machineDealer->id) {
                $isOwner = true;
            }

            $posterLabel = 'A dealer';
            if (!$isOwner && $posterType) {
                $posterLabel = match ($posterType) {
                    'dealer' => 'A dealer',
                    'converter' => 'A converter',
                    'brand' => 'A brand',
                    'machine_dealer' => 'A machine dealer',
                    default => 'A dealer',
                };
            }

            $selectedPartners = [];
            if ($session->status === \App\Enums\SessionStatus::LOCKED || $session->status === \App\Enums\SessionStatus::CHAT_ACTIVE) {
                $selectedPartners = $session->participants()
                    ->where('is_selected', true)
                    ->where('role', 'responder')
                    ->with('participant')
                    ->get()
                    ->map(function ($participant) {
                        $entity = $participant->participant;
                        if (!$entity) {
                            return null;
                        }

                        if ($entity instanceof \App\Models\Dealer) {
                            $u = $entity->user;
                            return [
                                'id' => $entity->id,
                                'company_name' => $u->company_name ?? $u->name ?? 'Unknown',
                                'location' => $entity->locations->first()?->city ?? $u->city ?? 'Unknown',
                            ];
                        }

                        if ($entity instanceof \App\Models\Converter) {
                            $u = $entity->user;
                            return [
                                'id' => $entity->id,
                                'company_name' => $u->company_name ?? $u->name ?? 'Unknown',
                                'location' => $entity->factory_city ?? $u->city ?? 'Unknown',
                            ];
                        }

                        if ($entity instanceof \App\Models\Brand) {
                            return [
                                'id' => $entity->id,
                                'company_name' => $entity->brand_name ?? $entity->company_name ?? 'Brand',
                                'location' => $entity->city ?? 'Unknown',
                            ];
                        }

                        return null;
                    })
                    ->filter()
                    ->values()
                    ->all();
            }

            $sessionData = [
                'id' => $session->id,
                'inquiry_id' => $inquiry->id,
                'project_id' => 'PRJ-' . str_pad($session->id, 4, '0', STR_PAD_LEFT),
                'status' => $session->status->value,
                'inquiry' => [
                    'id' => $inquiry->id,
                    'title' => $inquiry->title,
                    'items' => $inquiry->items,
                ],
                'selected_partners_count' => count($selectedPartners),
                'selected_partners' => $selectedPartners,
                'chat_enabled' => $session->chat_enabled,
                'chat_thread_id' => $session->chatThread?->id,
                'expires_at' => $session->expires_at,
                'locked_at' => $session->locked_at,
                'is_owner' => $isOwner,
                'poster_label' => $posterLabel,
            ];

            return Response::success('Session retrieved by inquiry', $sessionData);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return Response::error('No session found for this requirement.', null, HttpResponse::HTTP_NOT_FOUND);
        } catch (\Exception $e) {
            return Response::error(
                $e->getMessage(),
                null,
                method_exists($e, 'getStatusCode') ? $e->getStatusCode() : HttpResponse::HTTP_BAD_REQUEST
            );
        }
    }

    /**
     * Get session history
     * Screen: Session History Screen
     */
    public function getHistory()
    {
        try {
            $user = request()->user();
            $filter = request()->input('filter', 'all'); // all, completed, expired
            $search = request()->input('search');

            // Visible scopes: own + matched sessions
            $query = \App\Models\MatchingSession::query();
            $primaryRole = $user->primary_role ?? null;

            if ($primaryRole === 'dealer' && $user->dealer) {
                $query->visibleToDealer($user->dealer->id);
            } elseif ($primaryRole === 'converter' && $user->converter) {
                $query->visibleToConverter($user->converter->id);
            } elseif ($primaryRole === 'brand' && $user->brand) {
                $query->ownSessionsByBrand($user->brand->id);
            } elseif ($primaryRole === 'machine-dealer' && $user->machineDealer) {
                $query->visibleToMachineDealer($user->machineDealer->id);
            } elseif ($user->dealer) {
                $query->visibleToDealer($user->dealer->id);
            } elseif ($user->converter) {
                $query->visibleToConverter($user->converter->id);
            } elseif ($user->brand) {
                $query->ownSessionsByBrand($user->brand->id);
            } elseif ($user->machineDealer) {
                $query->visibleToMachineDealer($user->machineDealer->id);
            } else {
                $query->whereRaw('0 = 1'); // No role found
            }
            $query->with(['inquiry.items', 'participants']);

            // Apply status filter
            if ($filter === 'completed') {
                $query->whereIn('status', [
                    \App\Enums\SessionStatus::DEAL_SUCCESS,
                    \App\Enums\SessionStatus::DEAL_FAILED,
                ]);
            } elseif ($filter === 'expired') {
                $query->where('status', \App\Enums\SessionStatus::EXPIRED);
            }
            
            // Search filter
            if ($search) {
                $query->whereHas('inquiry', function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            }
            
            $sessions = $query->orderBy('created_at', 'desc')
                ->paginate(request()->per_page ?? 15);
            
            // Group by month
            $groupedSessions = $sessions->getCollection()->groupBy(function ($session) {
                return $session->created_at->format('F Y');
            });
            
            // Transform sessions
            $transformedSessions = $groupedSessions->map(function ($monthSessions, $month) {
                return [
                    'month' => $month,
                    'sessions' => $monthSessions->map(function ($session) {
                        $inquiry = $session->inquiry;
                        $selectedPartnersCount = $session->participants()
                            ->where('is_selected', true)
                            ->where('role', 'responder')
                            ->count();
                        
                        $statusLabel = 'COMPLETED';
                        if ($session->status === \App\Enums\SessionStatus::EXPIRED) {
                            $statusLabel = 'EXPIRED';
                        } elseif ($session->status === \App\Enums\SessionStatus::DEAL_FAILED) {
                            $statusLabel = 'FAILED';
                        }
                        
                        return [
                            'id' => $session->id,
                            'inquiry_id' => $inquiry->id,
                            'title' => $inquiry->title,
                            'status' => $session->status->value,
                            'status_label' => $statusLabel,
                            'partners_matched' => $selectedPartnersCount,
                            'quantity' => $inquiry->items->sum('quantity'),
                            'quantity_unit' => $inquiry->items->first()?->quantity_unit ?? 'units',
                            'created_at' => $inquiry->created_at,
                            'can_republish' => $session->canRepublish()->where('id', $session->id)->exists(),
                        ];
                    })->values(),
                ];
            })->values();
            
            return Response::success('Session history retrieved successfully', [
                'sessions' => $transformedSessions,
                'pagination' => [
                    'current_page' => $sessions->currentPage(),
                    'total' => $sessions->total(),
                    'per_page' => $sessions->perPage(),
                    'last_page' => $sessions->lastPage(),
                ],
            ]);
            
        } catch (\Exception $e) {
            return Response::error($e->getMessage(), null, HttpResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Lock session and select responders (dealers or converters depending on poster type)
     */
    public function lock(\App\Http\Requests\LockSessionRequest $request, \App\Models\MatchingSession $session)
    {
        try {
            \Illuminate\Support\Facades\Gate::authorize('lock', $session);
            
            $user = $request->user();
            $validated = $request->validated();
            $selectedDealerIds = $validated['selected_dealer_ids'] ?? [];
            $selectedConverterIds = $validated['selected_converter_ids'] ?? [];
            
            $inquiry = $session->inquiry;
            $posterType = $inquiry->poster_type;

            // Determine responder pool based on poster type
            $responderType = 'dealer';
            $responderIds = $selectedDealerIds;
            if ($posterType === 'brand') {
                $responderType = 'converter';
                $responderIds = $selectedConverterIds;
            }
            
            \Illuminate\Support\Facades\DB::beginTransaction();
            
            // Update session status
            $session->update([
                'status' => \App\Enums\SessionStatus::LOCKED,
                'locked_at' => now(),
                'chat_enabled' => true,
                'full_specs_visible' => true,
                'brand_identity_visible' => true,
            ]);
            
            // Update inquiry status
            $inquiryUpdate = [
                'status' => \App\Enums\InquiryStatus::LOCKED,
                'locked_at' => now(),
            ];
            if ($responderType === 'dealer') {
                $inquiryUpdate['selected_dealers_count'] = count($responderIds);
            }
            $inquiry->update($inquiryUpdate);
            
            // Hide from non-selected responders
            $matchmakingService = app(\App\Services\MatchmakingService::class);
            if ($responderType === 'dealer') {
                $matchmakingService->hideFromNonSelectedDealers($inquiry, $responderIds);
            } else {
                $matchmakingService->hideFromNonSelectedConverters($inquiry, $responderIds);
            }
            
            // Create session participants for selected responders
            foreach ($responderIds as $responderId) {
                \App\Models\SessionParticipant::create([
                    'session_id' => $session->id,
                    'participant_type' => $responderType,
                    'participant_id' => $responderId,
                    'role' => 'responder',
                    'can_see_full_specs' => true,
                    'can_see_exact_location' => true,
                    'can_see_brand_identity' => true,
                    'can_chat' => true,
                    'status' => 'active',
                    'is_selected' => true,
                    'selected_at' => now(),
                    'joined_at' => now(),
                ]);
            }
            
            // Create participant for poster
            $poster = $inquiry->poster;
            \App\Models\SessionParticipant::create([
                'session_id' => $session->id,
                'participant_type' => $inquiry->poster_type,
                'participant_id' => $inquiry->poster_id,
                'role' => 'poster',
                'can_see_full_specs' => true,
                'can_see_exact_location' => true,
                'can_see_brand_identity' => true,
                'can_chat' => true,
                'status' => 'active',
                'joined_at' => now(),
            ]);
            
            // Create chat thread
            \App\Models\ChatThread::create([
                'session_id' => $session->id,
                'thread_type' => count($responderIds) > 1 ? 'one_to_few' : 'one_to_one',
                'is_active' => true,
            ]);
            
            \Illuminate\Support\Facades\DB::commit();
            
            $session->load(['inquiry', 'participants', 'chatThread']);
            
            return Response::success('Session locked successfully', $session);
            
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return Response::error($e->getMessage(), null, HttpResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get active sessions (Sourcing Hub)
     * Screen: Active Sessions (Sourcing Hub) Screen
     */
    public function getActive()
    {
        try {
            $user = request()->user();

            // V2: lazy matching so newly registered users see qualifying inquiries
            if (config('matchmaking.engine_version') === 'v2' && config('matchmaking.auto_match_on_login', true)) {
                app(\App\Domain\MatchEngine\MatchEngineOrchestrator::class)->ensureMatchesForUser($user);
            }

            $filter = request()->input('filter', 'all'); // all, finding_matches, active, locked

            // Visible scopes: users see OWN sessions + sessions where they were MATCHED
            $query = \App\Models\MatchingSession::query();
            $primaryRole = $user->primary_role ?? null;

            if ($primaryRole === 'dealer' && $user->dealer) {
                $query->visibleToDealer($user->dealer->id);
            } elseif ($primaryRole === 'converter' && $user->converter) {
                $query->visibleToConverter($user->converter->id);
            } elseif ($primaryRole === 'brand' && $user->brand) {
                $query->ownSessionsByBrand($user->brand->id);
            } elseif ($primaryRole === 'machine-dealer' && $user->machineDealer) {
                $query->visibleToMachineDealer($user->machineDealer->id);
            } elseif ($user->dealer) {
                $query->visibleToDealer($user->dealer->id);
            } elseif ($user->converter) {
                $query->visibleToConverter($user->converter->id);
            } elseif ($user->brand) {
                $query->ownSessionsByBrand($user->brand->id);
            } elseif ($user->machineDealer) {
                $query->visibleToMachineDealer($user->machineDealer->id);
            } else {
                $query->whereRaw('0 = 1'); // No role found
            }
            $query->with(['inquiry.items', 'participants']);

            // Apply filters
            // Note: Database enum currently only has: ACTIVE, DEAL_WON, DEAL_LOST, EXPIRED, CANCELLED
            // Using ACTIVE for newly created sessions until enum is updated
            if ($filter === 'finding_matches') {
                // Active inquiries: open until 10 people have expressed interest (responded_at)
                $query->whereIn('status', [\App\Enums\SessionStatus::ACTIVE, \App\Enums\SessionStatus::LOCKED])
                    ->whereHas('inquiry', function ($inqQuery) {
                        $inqQuery->whereRaw('(SELECT COUNT(*) FROM matchmaking_logs WHERE matchmaking_logs.inquiry_id = inquiries.id AND matchmaking_logs.responded_at IS NOT NULL) < 10');
                    });
            } elseif ($filter === 'active') {
                // Active (open) sessions: not expired, < 10 people responded
                $query->whereIn('status', [\App\Enums\SessionStatus::ACTIVE, \App\Enums\SessionStatus::LOCKED])
                    ->where(function ($q) {
                        $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
                    })
                    ->whereHas('inquiry', function ($inqQuery) {
                        $inqQuery->whereRaw('(SELECT COUNT(*) FROM matchmaking_logs WHERE matchmaking_logs.inquiry_id = inquiries.id AND matchmaking_logs.responded_at IS NOT NULL) < 10');
                    });
            } elseif ($filter === 'locked') {
                // Locked sessions: >= 10 people responded (expressed interest) or manually locked
                $query->whereIn('status', [\App\Enums\SessionStatus::ACTIVE, \App\Enums\SessionStatus::LOCKED])
                    ->where(function ($q) {
                        $q->whereNotNull('locked_at')
                            ->orWhereHas('inquiry', function ($iq) {
                                $iq->whereRaw('(SELECT COUNT(*) FROM matchmaking_logs WHERE matchmaking_logs.inquiry_id = inquiries.id AND matchmaking_logs.responded_at IS NOT NULL) >= 10');
                            });
                    });
            } else {
                // All active sessions (ACTIVE status, not expired)
                $query->where('status', \App\Enums\SessionStatus::ACTIVE)
                    ->where(function ($q) {
                        $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
                    });
            }
            
            $sessions = $query->orderBy('created_at', 'desc')
                ->paginate(request()->per_page ?? 15);

            $currentUser = $user;

            // Transform sessions for frontend
            $sessions->getCollection()->transform(function ($session) use ($currentUser) {
                $inquiry = $session->inquiry;
                $posterType = $inquiry->poster_type;
                $posterId = $inquiry->poster_id;

                // Is the current user the poster (owner) of this inquiry?
                $isOwner = false;
                if ($posterType === 'dealer' && $currentUser->dealer && (int) $posterId === (int) $currentUser->dealer->id) {
                    $isOwner = true;
                } elseif ($posterType === 'converter' && $currentUser->converter && (int) $posterId === (int) $currentUser->converter->id) {
                    $isOwner = true;
                } elseif ($posterType === 'brand' && $currentUser->brand && (int) $posterId === (int) $currentUser->brand->id) {
                    $isOwner = true;
                } elseif ($posterType === 'machine_dealer' && $currentUser->machineDealer && (int) $posterId === (int) $currentUser->machineDealer->id) {
                    $isOwner = true;
                }

                // Label for "who posted" (for receivers; keep generic for privacy)
                $posterLabel = 'A dealer';
                if (!$isOwner && $posterType) {
                    $posterLabel = match ($posterType) {
                        'dealer' => 'A dealer',
                        'converter' => 'A converter',
                        'brand' => 'A brand',
                        'machine_dealer' => 'A machine dealer',
                        default => 'A dealer',
                    };
                }

                // Calculate countdown
                $countdown = null;
                if ($session->expires_at) {
                    $secondsLeft = max(0, now()->diffInSeconds($session->expires_at, false));
                    if ($secondsLeft > 0) {
                        $days = floor($secondsLeft / 86400);
                        $hours = floor(($secondsLeft % 86400) / 3600);
                        $minutes = floor(($secondsLeft % 3600) / 60);
                        $secs = $secondsLeft % 60;

                        $countdown = [
                            'days' => $days,
                            'hours' => $hours,
                            'minutes' => $minutes,
                            'seconds' => $secs,
                            'formatted' => sprintf('%02d DAYS %02d HOURS %02d MINS %02d SECS', $days, $hours, $minutes, $secs),
                        ];
                    }
                }

                // Keep counts aligned with poster-detail endpoint by deriving from matchmaking logs.
                $matchedDealersCount = \App\Models\MatchmakingLog::where('inquiry_id', $inquiry->id)
                    ->where('is_visible', true)
                    ->count();
                $responsesCount = \App\Models\MatchmakingLog::where('inquiry_id', $inquiry->id)
                    ->whereNotNull('responded_at')
                    ->count();

                // Determine status label based on session and inquiry status
                $statusLabel = 'ACTIVE';
                $inquiryStatus = $inquiry->status;

                if ($inquiryStatus === \App\Enums\InquiryStatus::MATCHING) {
                    $statusLabel = 'FINDING';
                } elseif ($session->locked_at && $session->locked_at <= now()) {
                    $statusLabel = 'LOCKED';
                } elseif ($inquiryStatus === \App\Enums\InquiryStatus::RESPONSES_RECEIVED) {
                    $statusLabel = 'ACTIVE';
                }

                $intent = $inquiry->intent?->value ?? $inquiry->intent ?? 'buy';

                // Jobwork (converter JOB): attach jobwork_mode and inquiry_type for list card badge
                $jobworkMode = null;
                $inquiryType = null;
                $jobworkType = null;
                if ($inquiry->inquiry_type === InquiryType::JOB && $posterType === 'converter') {
                    $jobworkMode = $intent === 'sell' ? 'find' : 'give';
                    $inquiryType = $jobworkMode === 'find' ? 'jobwork_find' : 'jobwork_give';
                    $specs = $inquiry->specs ?? [];
                    $jobworkType = $specs['jobwork_type'] ?? $inquiry->job_type ?? null;
                }

                // Optional brand-specific requirement summary for brand-posted inquiries
                $brandRequirement = null;
                if ($posterType === 'brand') {
                    $brandRequirement = [
                        'requirement_type' => $inquiry->requirement_type,
                        'packaging_type' => $inquiry->packaging_type,
                        'quantity_range' => $inquiry->quantity_range,
                        'timeline' => $inquiry->timeline,
                        'description' => $inquiry->description,
                        'special_needs' => $inquiry->special_needs,
                    ];
                }

                $row = [
                    'id' => $session->id,
                    'inquiry_id' => $inquiry->id,
                    'title' => $inquiry->title,
                    'status' => $session->status->value,
                    'status_label' => $statusLabel,
                    'urgency' => $inquiry->urgency,
                    'intent' => $intent,
                    'created_at' => $inquiry->created_at,
                    'items' => $inquiry->items->map(function ($item) {
                        return [
                            'material_category' => $item->material_category,
                            'quantity' => $item->quantity,
                            'quantity_unit' => $item->quantity_unit,
                        ];
                    }),
                    'brand_requirement' => $brandRequirement,
                    'countdown' => $countdown,
                    'responses_received' => $responsesCount,
                    'matched_dealers_count' => $matchedDealersCount,
                    'matching_progress' => $session->status === \App\Enums\SessionStatus::MATCHING ? [
                        'matched' => $matchedDealersCount,
                        'total' => 15,
                        'status' => 'Scanning suppliers...',
                    ] : null,
                    'is_owner' => $isOwner,
                    'poster_label' => $posterLabel,
                ];
                if ($jobworkMode !== null) {
                    $row['jobwork_mode'] = $jobworkMode;
                    $row['inquiry_type'] = $inquiryType;
                    if ($jobworkType !== null) {
                        $row['jobwork_type'] = is_string($jobworkType) ? $jobworkType : (string) $jobworkType;
                    }
                }
                return $row;
            });
            
            return Response::success('Active sessions retrieved successfully', $sessions);
            
        } catch (\Exception $e) {
            return Response::error($e->getMessage(), null, HttpResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Republish session
     */
    public function republish(\App\Models\MatchingSession $session)
    {
        try {
            \Illuminate\Support\Facades\Gate::authorize('republish', $session);
            
            // Republish the inquiry
            $inquiryController = new \App\Http\Controllers\api\InquiryController(
                app(\App\Services\MatchmakingService::class)
            );
            
            return $inquiryController->republish(request(), $session->inquiry);
            
        } catch (\Exception $e) {
            return Response::error($e->getMessage(), null, HttpResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Mark deal as failed
     */
    public function markDealFailed(\App\Models\MatchingSession $session)
    {
        try {
            \Illuminate\Support\Facades\Gate::authorize('markDealFailed', $session);
            
            \Illuminate\Support\Facades\DB::beginTransaction();
            
            $session->update([
                'status' => \App\Enums\SessionStatus::DEAL_FAILED,
                'cooldown_until' => now()->addDays(7),
            ]);
            
            $session->inquiry->update([
                'status' => \App\Enums\InquiryStatus::DEAL_FAILED,
                'cooldown_until' => now()->addDays(7),
            ]);
            
            // Make chat read-only
            if ($session->chatThread) {
                $session->chatThread->update([
                    'is_read_only' => true,
                    'archived_at' => now(),
                ]);
            }
            
            \Illuminate\Support\Facades\DB::commit();
            
            return Response::success('Deal marked as failed', $session);
            
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return Response::error($e->getMessage(), null, HttpResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}




