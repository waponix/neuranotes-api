<?php
namespace App\Http\Controllers;

use App\Http\Resource\ChatResource;
use Illuminate\Http\Request;
use App\Http\Resource\Validator\ChatValidator;

final class ChatController extends Controller
{
     public function chat(
          Request $request,
          ChatResource $chatResource,
     )
     {
          return $chatResource
               ->useValidator(ChatValidator::class)
               ->handleChatRequest($request);
     }

     public function convo(Request $request)
     {

     }
}