<?php

namespace App\Http\Controllers;

use App\Services\AiChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    const SESSION_KEY = 'ai_chat_history';
    const MAX_TURNS   = 12;

    public function send(Request $request, AiChatService $ai): JsonResponse
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'min:1', 'max:1000'],
        ]);

        $history = collect($request->session()->get(self::SESSION_KEY, []))
            ->take(-self::MAX_TURNS * 2)
            ->values()
            ->all();

        $reply = $ai->reply($history, $data['message']);

        $history[] = ['role' => 'user',      'content' => $data['message']];
        $history[] = ['role' => 'assistant', 'content' => $reply];

        $request->session()->put(
            self::SESSION_KEY,
            array_slice($history, -self::MAX_TURNS * 2)
        );

        return response()->json([
            'reply' => $reply,
        ]);
    }

    public function reset(Request $request): JsonResponse
    {
        $request->session()->forget(self::SESSION_KEY);
        return response()->json(['ok' => true]);
    }

    public function history(Request $request): JsonResponse
    {
        return response()->json([
            'history' => $request->session()->get(self::SESSION_KEY, []),
        ]);
    }
}
