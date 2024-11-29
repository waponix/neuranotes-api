<?php
namespace App\Http\Resource;

use App\Http\Api\BasicResource;
use App\Http\Api\Interface\ResourceValidator;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Auth\AuthenticationException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;

class AuthResource extends BasicResource
{
    protected function loginRequest(Request $request, ResourceValidator $validator): static
    {
        $validator->validate($request);

        if ($validator->errors() !== null) {
            $this->setResponse(['error' => $validator->errors()], 400);
            return $this;
        }

        $credentials = $request->only('email', 'password');
        
        if (!$token = JWTAuth::attempt($credentials)) {
            $this->setResponse(['error' => 'Invalid credentials'], 401);
            return $this;
        }

        $this->setResponse(['token' => $token]);
        return $this;
    }

    protected function registerRequest(Request $request, ResourceValidator $validator): static
    {
        $validator->validate($request);

        if ($validator->errors() !== null) {
            $this->setResponse(['error' => $validator->errors()], 400);
            return $this;
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $token = JWTAuth::fromUser($user);

        $this->setResponse(['token' => $token], 201);

        return $this;
    }

    protected function refreshRequest(Request $request): static
    {
        if ($request->bearerToken() === null) {
            throw new AuthenticationException();
        }

        try {
            JWTAuth::parseToken()->authenticate();
            $this->setResponse(['token' => JWTAuth::getToken()->get()], 201);
        } catch (TokenExpiredException $e) {
            $token = JWTAuth::parseToken()->refresh();
            $this->setResponse(['token' => $token], 201);
        } catch (JWTException $e) {
            throw new AuthenticationException();
        }

        return $this;
    }

    protected function profileRequest(Request $request): static
    {
        $this->setResponse(['profile' => auth()->user()]);
        return $this;
    }
}