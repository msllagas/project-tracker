<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class RegisteredUserController extends Controller
{
    /**
     * Register a new user and log them in.
     */
    public function store(RegisterRequest $request): JsonResponse
    {
        $user = User::create($request->validated());

        Auth::guard('web')->login($user);

        $request->session()->regenerate();

        return (new UserResource($user))
            ->response()
            ->setStatusCode(201);
    }
}
