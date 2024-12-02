<?php
namespace App\Http\Resource;

use App\Http\Api\BasicResource;
use App\Http\Api\Interface\OutputBuilder;
use App\Http\Api\Interface\ResourceValidator;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Auth\AuthenticationException;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;

final class AuthResource extends BasicResource
{
    protected function loginRequest(
        Request $request, 
        OutputBuilder $outputBuilder,
        ResourceValidator $validator,
    ): static
    {
        $validator->validate($request);

        if ($validator->errors() !== null) {
            $outputBuilder
                ->setStatus('invalid.arguments')
                ->setCode(Response::HTTP_BAD_REQUEST)
                ->setError($validator->errors());
            return $this;
        }

        $credentials = $request->only('email', 'password');
        
        if (!$token = JWTAuth::attempt($credentials)) {
            $outputBuilder
                ->setStatus('invalid.credentials')
                ->setCode(Response::HTTP_UNAUTHORIZED)
                ->setError('invalid credentials');
            return $this;
        }

        $outputBuilder->setData(['token' => $token]);
        return $this;
    }

    protected function registerRequest(
        Request $request, 
        OutputBuilder $outputBuilder,
        ResourceValidator $validator,
    ): static
    {
        $validator->validate($request);

        if ($validator->errors() !== null) {
            $outputBuilder
                ->setStatus('invalid.arguments')
                ->setCode(Response::HTTP_BAD_REQUEST)
                ->setError($validator->errors());
            return $this;
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $token = JWTAuth::fromUser($user);

        $outputBuilder->setData(['token' => $token]);

        return $this;
    }

    protected function refreshRequest(
        Request $request,
        OutputBuilder $outputBuilder,
    ): static
    {
        if ($request->bearerToken() === null) {
            throw new AuthenticationException();
        }

        $outputBuilder->setCode(Response::HTTP_CREATED);

        try {
            JWTAuth::parseToken()->authenticate();
            $outputBuilder->setData(['token' => JWTAuth::getToken()->get()]);
        } catch (TokenExpiredException $e) {
            $token = JWTAuth::parseToken()->refresh();
            $outputBuilder->setData(['token' => $token]);
        } catch (JWTException $e) {
            throw new AuthenticationException();
        }

        return $this;
    }

    protected function profileRequest(
        Request $request,
        OutputBuilder $outputBuilder,
    ): static
    {
        $outputBuilder->setData(['profile' => auth()->user()]);
        return $this;
    }
}