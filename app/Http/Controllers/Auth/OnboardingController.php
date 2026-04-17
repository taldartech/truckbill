<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\CompleteOnboardingRequest;
use App\Http\Requests\SendOnboardingOtpRequest;
use App\Http\Requests\StoreLeadRequest;
use App\Http\Requests\VerifyOnboardingOtpRequest;
use App\Models\Lead;
use App\Services\LeadOnboardingService;
use App\Services\OnboardingOtpService;
use App\Services\OnboardingTokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class OnboardingController extends Controller
{
    public function __construct(
        private readonly OnboardingTokenService $tokenService,
        private readonly OnboardingOtpService $otpService,
        private readonly LeadOnboardingService $leadOnboardingService
    ) {}

    public function createWebsiteLead(StoreLeadRequest $request): JsonResponse
    {
        $lead = Lead::create([
            ...$request->validated(),
            'source' => 'website',
            'status' => Lead::STATUS_LEAD_CREATED,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Lead created successfully.',
            'lead_id' => $lead->id,
        ]);
    }

    public function validateToken(string $token): JsonResponse
    {
        $tokenRecord = $this->tokenService->findActiveByPlainToken($token);
        if ($tokenRecord === null || $tokenRecord->lead === null) {
            return response()->json([
                'valid' => false,
                'message' => 'Invalid or used onboarding token.',
            ], 422);
        }

        if (in_array($tokenRecord->lead->status, [Lead::STATUS_REJECTED, Lead::STATUS_ACTIVATED], true)) {
            return response()->json([
                'valid' => false,
                'message' => 'This lead is not eligible for onboarding.',
            ], 422);
        }

        return response()->json([
            'valid' => true,
            'lead' => [
                'name' => $tokenRecord->lead->name,
                'mobile' => $tokenRecord->lead->mobile,
                'email' => $tokenRecord->lead->email,
            ],
        ]);
    }

    public function sendOtp(SendOnboardingOtpRequest $request): JsonResponse
    {
        $tokenRecord = $this->tokenService->findActiveByPlainToken($request->validated('token'));
        if ($tokenRecord === null || $tokenRecord->lead === null) {
            throw ValidationException::withMessages(['token' => 'Invalid or used onboarding token.']);
        }

        if (in_array($tokenRecord->lead->status, [Lead::STATUS_REJECTED, Lead::STATUS_ACTIVATED], true)) {
            throw ValidationException::withMessages(['lead' => 'This lead is not eligible for onboarding.']);
        }

        $this->otpService->send($request->validated('email'));

        return response()->json([
            'message' => 'OTP sent successfully.',
        ]);
    }

    public function verifyOtp(VerifyOnboardingOtpRequest $request): JsonResponse
    {
        $data = $request->validated();
        $this->otpService->verify($data['email'], $data['otp']);

        return response()->json([
            'message' => 'Email OTP verified successfully.',
        ]);
    }

    public function onboard(CompleteOnboardingRequest $request): JsonResponse
    {
        $result = $this->leadOnboardingService->complete($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Onboarding completed successfully.',
        ]);
    }
}
