<?php

namespace App\Http\Controllers;

use App\Ai\Agents\ResumeBuilderAgent;
use App\Data\ResumeScoreContextData;
use App\Models\Resume;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ResumeAssistantController extends Controller
{
    public function message(Request $request, Resume $resume): JsonResponse
    {
        $this->authorize('update', $resume);

        $validated = $request->validate([
            'message' => ['required', 'string'],
        ]);

        try {
            $response = $this->promptAgent($resume, $validated['message']);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'AI service error. Please check your connection and try again.',
            ], 422);
        }

        return response()->json([
            'message' => [
                'role' => 'assistant',
                'content' => (string) $response,
            ],
        ]);
    }

    private function promptAgent(Resume $resume, string $prompt): object
    {
        $context = ResumeScoreContextData::fromResume($resume, [])->resumeContext();
        $agent = new ResumeBuilderAgent($resume, $context);
        $user = $resume->user;

        if ($resume->ai_conversation_id) {
            return $agent->continue($resume->ai_conversation_id, as: $user)
                ->prompt($prompt, model: 'gpt-4o');
        }

        $response = $agent->forUser($user)->prompt($prompt, model: 'gpt-4o');

        $resume->update(['ai_conversation_id' => $response->conversationId]);

        return $response;
    }
}
