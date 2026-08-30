<?php

use App\Ai\Agents\InterviewAgent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

// Disposable E2E server only: real application routes/persistence, controlled AI provider.
if (PHP_SAPI !== 'cli-server' || getenv('APP_ENV') !== 'testing'
    || getenv('JOBFLOW_FEEDBACK_E2E') !== '1'
    || getenv('DB_DATABASE') !== '/tmp/jobflow-feedback.sqlite') {
    http_response_code(404);
    exit;
}

$root = dirname(__DIR__, 3);
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$file = realpath($root.'/public'.$path);
if ($path !== '/' && $file && str_starts_with($file, $root.'/public/') && is_file($file)) {
    return false;
}

define('LARAVEL_START', microtime(true));
require $root.'/vendor/autoload.php';
$app = require $root.'/bootstrap/app.php';
$app->booted(function (): void {
    InterviewAgent::fake(function (string $prompt): string {
        $session = request()->route('session');
        if (str_contains($prompt, 'The mock interview is complete.')) {
            $event = ['conversation' => $session->conversation_id, 'kind' => 'evaluation'];
            file_put_contents('/tmp/jobflow-feedback-calls.jsonl', json_encode($event)."\n", FILE_APPEND | LOCK_EX);
            usleep(1500000);
            if (is_file('/tmp/jobflow-feedback-fail-once')) {
                unlink('/tmp/jobflow-feedback-fail-once');
                throw new RuntimeException('Controlled feedback failure');
            }

            return "## Overall Assessment\n\nRelevant answers with clear evidence.\n\n## Strengths\n\n- Clear examples\n- Sound reasoning\n\n## Areas to Improve\n\n- Quantify outcomes\n\n## Recommendation\n\nKeep practicing.";
        }

        $questionCount = $session->conversation_id
            ? DB::table('agent_conversation_messages')->where('conversation_id', $session->conversation_id)->where('role', 'assistant')->count()
            : 0;

        return 'Question '.($questionCount + 1).': Describe a project decision and its outcome.';
    });
});
$app->handleRequest(Request::capture());
