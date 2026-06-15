<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\ExchangeRateUnavailableException;
use App\Exceptions\PaymentRequestConflictException;
use App\Http\Controllers\Controller;
use App\Http\Requests\PaymentRequest\ListPaymentRequestsRequest;
use App\Http\Requests\PaymentRequest\RejectPaymentRequestRequest;
use App\Http\Requests\PaymentRequest\StorePaymentRequestRequest;
use App\Http\Resources\PaymentRequestResource;
use App\Models\PaymentRequest;
use App\Models\User;
use App\Services\PaymentRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PaymentRequestController extends Controller
{
    public function __construct(
        private readonly PaymentRequestService $paymentRequestService,
    ) {
    }

    public function index(ListPaymentRequestsRequest $request)
    {
        $this->authorize('viewAny', PaymentRequest::class);

        /** @var User $user */
        $user = $request->user();

        $query = PaymentRequest::query()
            ->with(['user', 'reviewer'])
            ->latest();

        if ($user->isEmployee()) {
            $query->where('user_id', $user->id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        $requests = PaymentRequestResource::collection($query->get());
        $stats = (clone $query)->selectRaw("
            count(case when status = 'pending' then 1 end) as pending,
            count(case when status = 'approved' then 1 end) as approved,
            count(case when status = 'rejected' then 1 end) as rejected
        ")->first();

        return Inertia::render('Dashboard', [
            'paymentRequests' => $requests,
            'stats' => [
                'pending' => $stats->pending,
                'approved' => $stats->approved,
                'rejected' => $stats->rejected,
            ]
        ]);
    }

    public function store(StorePaymentRequestRequest $request): JsonResponse
    {
        $this->authorize('create', PaymentRequest::class);

        try {
            $paymentRequest = $this->paymentRequestService->create(
                $request->user(),
                $request->validated()
            );
        } catch (ExchangeRateUnavailableException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 503);
        }

        $paymentRequest->load(['user', 'reviewer']);

        return response()->json([
            'message' => 'Payment request created successfully.',
            'data' => PaymentRequestResource::make($paymentRequest),
        ], 201);
    }

    public function show(Request $request, PaymentRequest $paymentRequest): JsonResponse
    {
        $this->authorize('view', $paymentRequest);

        $paymentRequest->load(['user', 'reviewer']);

        return response()->json([
            'data' => PaymentRequestResource::make($paymentRequest),
        ]);
    }

    public function approve(Request $request, PaymentRequest $paymentRequest): JsonResponse
    {
        $this->authorize('approve', $paymentRequest);

        try {
            $paymentRequest = $this->paymentRequestService->approve(
                $paymentRequest,
                $request->user()
            );
        } catch (PaymentRequestConflictException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 409);
        }

        return response()->json([
            'message' => 'Payment request approved successfully.',
            'data' => PaymentRequestResource::make($paymentRequest),
        ]);
    }

    public function reject(RejectPaymentRequestRequest $request, PaymentRequest $paymentRequest): JsonResponse
    {
        $this->authorize('reject', $paymentRequest);

        try {
            $paymentRequest = $this->paymentRequestService->reject(
                $paymentRequest,
                $request->user(),
                $request->string('rejection_reason')->toString()
            );
        } catch (PaymentRequestConflictException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 409);
        }

        return response()->json([
            'message' => 'Payment request rejected successfully.',
            'data' => PaymentRequestResource::make($paymentRequest),
        ]);
    }
}
