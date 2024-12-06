<?php
namespace App\Http\Controllers;

use App\Http\Resource\AuthResource;
use App\Http\Resource\Validator\Auth\RegisterValidator;
use App\Http\Resource\Validator\Auth\LoginValidator;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function register(
        Request $request,
        AuthResource $authResource,
    )
    {
        return $authResource
            ->useValidator(RegisterValidator::class)
            ->handleRegisterRequest($request);
    }

    public function login(
        Request $request, 
        AuthResource $authResource,
    )
    {
        return $authResource
            ->useValidator(LoginValidator::class)
            ->handleLoginRequest($request);
    }

    public function refresh(
        Request $request,
        AuthResource $authResource,
    )
    {
        return $authResource->handleRefreshRequest($request);
    }

    public function profile(
        Request $request, 
        AuthResource $authResource,
    )
    {
        return $authResource->handleProfileRequest($request);
    }
}
