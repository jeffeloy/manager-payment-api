<?php

namespace App\Http\Controllers\Web;

use App\Exceptions\ExchangeRateUnavailableException;
use App\Exceptions\PaymentRequestConflictException;
use App\Http\Controllers\Controller;
use App\Http\Requests\PaymentRequest\RejectPaymentRequestRequest;
use App\Http\Requests\PaymentRequest\StorePaymentRequestRequest;
use App\Http\Resources\PaymentRequestResource;
use App\Models\PaymentRequest;
use App\Models\User;
use App\Services\PaymentRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PaymentRequestController extends Controller
{
    public function __construct(
        private readonly PaymentRequestService $paymentRequestService,
    ) {
    }

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', PaymentRequest::class);

        /** @var User $user */
        $user = $request->user();

        $requests = $this->paymentRequestService->listForUser($user);

        return Inertia::render('PaymentRequest/Index', [
            'paymentRequests' => PaymentRequestResource::collection($requests),
            'stats' => $this->paymentRequestService->statsForUser($user),
        ]);
    }

    public function store(StorePaymentRequestRequest $request): RedirectResponse
    {
        $this->authorize('create', PaymentRequest::class);

        try {
            $this->paymentRequestService->create(
                $request->user(),
                $request->validated()
            );
        } catch (ExchangeRateUnavailableException $exception) {
            return back()->withErrors([
                'amount' => $exception->getMessage(),
            ]);
        }

        return to_route('dashboard')->with('success', 'Payment request created successfully.');
    }

    public function approve(Request $request, PaymentRequest $paymentRequest): RedirectResponse
    {
        $this->authorize('approve', $paymentRequest);

        try {
            $this->paymentRequestService->approve(
                $paymentRequest,
                $request->user()
            );
        } catch (PaymentRequestConflictException $exception) {
            return back()->withErrors([
                'action' => $exception->getMessage(),
            ]);
        }

        return to_route('dashboard')->with('success', 'Payment request approved successfully.');
    }

    public function reject(RejectPaymentRequestRequest $request, PaymentRequest $paymentRequest): RedirectResponse
    {
        $this->authorize('reject', $paymentRequest);

        try {
            $this->paymentRequestService->reject(
                $paymentRequest,
                $request->user(),
                $request->string('rejection_reason')->toString()
            );
        } catch (PaymentRequestConflictException $exception) {
            return back()->withErrors([
                'action' => $exception->getMessage(),
            ]);
        }

        return to_route('dashboard')->with('success', 'Payment request rejected successfully.');
    }
}
