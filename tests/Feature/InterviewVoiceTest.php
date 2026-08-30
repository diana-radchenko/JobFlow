<?php

use App\Enums\ApplicationStatus;
use App\Models\InterviewSession;
use App\Models\User;
use App\Models\UserWorkJobApplication;
use App\Models\WorkJob;
use App\Services\InterviewVoice;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Laravel\Ai\Audio;
use Laravel\Ai\Prompts\AudioPrompt;
use Laravel\Ai\Prompts\TranscriptionPrompt;
use Laravel\Ai\Responses\AudioResponse;
use Laravel\Ai\Responses\Data\Meta;
use Laravel\Ai\Transcription;

function interviewVoiceEndpoint(string $workspace, string $operation, InterviewSession $session): string
{
    return $workspace === 'prep'
        ? route('interview-prep.'.$operation)
        : route('interview-session.'.$operation, $session);
}

function interviewVoiceAudioResponse(string $bytes = 'speech-bytes', ?string $mimeType = 'audio/mpeg'): AudioResponse
{
    return new AudioResponse(base64_encode($bytes), new Meta('openai', 'gpt-4o-mini-tts'), $mimeType);
}

beforeEach(function () {
    $this->voiceUser = User::factory()->create();
    $resume = $this->voiceUser->resumes()->create(['title' => 'Voice Resume']);
    $this->voiceContext = [
        'type' => 'technical',
        'complexity' => 'intermediate',
        'mode' => 'live',
        'resume_id' => $resume->id,
    ];
    $this->voiceSession = InterviewSession::create([
        ...$this->voiceContext,
        'user_id' => $this->voiceUser->id,
        'status' => 'in_progress',
    ]);

    Http::preventStrayRequests();
});

test('voice endpoints require authentication', function (string $workspace, string $operation) {
    Audio::fake();
    Transcription::fake();

    $this->postJson(interviewVoiceEndpoint($workspace, $operation, $this->voiceSession), [])
        ->assertUnauthorized();

    Audio::assertNothingGenerated();
    Transcription::assertNothingGenerated();
})->with(['session', 'prep'])->with(['audio', 'transcribe']);

test('voice session endpoints enforce ownership and active status', function (string $operation) {
    Audio::fake();
    Transcription::fake();
    $url = interviewVoiceEndpoint('session', $operation, $this->voiceSession);

    $this->actingAs(User::factory()->create())->postJson($url, [])->assertForbidden();

    $this->voiceSession->update(['status' => 'completed']);
    $this->actingAs($this->voiceUser)->postJson($url, [])->assertConflict();

    Audio::assertNothingGenerated();
    Transcription::assertNothingGenerated();
})->with(['audio', 'transcribe']);

test('voice prep validates mode and ownership before generating audio', function (string $operation) {
    Audio::fake();
    Transcription::fake();
    $url = interviewVoiceEndpoint('prep', $operation, $this->voiceSession);
    $foreignResume = User::factory()->create()->resumes()->create(['title' => 'Private']);

    $this->actingAs($this->voiceUser)
        ->postJson($url, [...$this->voiceContext, 'mode' => 'text'])
        ->assertUnprocessable()->assertJsonValidationErrors('mode');
    $this->postJson($url, [...$this->voiceContext, 'resume_id' => $foreignResume->id])
        ->assertUnprocessable()->assertJsonValidationErrors('resume_id');

    $job = WorkJob::create([
        'title' => 'Engineer', 'company' => 'Example', 'description' => 'Build software',
        'contacts' => 'jobs@example.com', 'location' => 'Remote', 'technologies' => ['PHP'],
    ]);
    $this->postJson($url, [...$this->voiceContext, 'work_job_id' => $job->id])
        ->assertUnprocessable()->assertJsonValidationErrors('work_job_id');

    Audio::assertNothingGenerated();
    Transcription::assertNothingGenerated();
})->with(['audio', 'transcribe']);

test('voice playback returns raw audio and does not alter interview records', function (string $workspace) {
    Audio::fake([interviewVoiceAudioResponse()]);
    $sessionCount = InterviewSession::count();

    $this->actingAs($this->voiceUser)
        ->postJson(interviewVoiceEndpoint($workspace, 'audio', $this->voiceSession), [
            ...$this->voiceContext, 'content' => 'Tell me about your experience.',
        ])
        ->assertSuccessful()
        ->assertHeader('Content-Type', 'audio/mpeg')
        ->assertHeader('Cache-Control', 'no-store, private')
        ->assertContent('speech-bytes');

    Audio::assertGenerated(fn (AudioPrompt $prompt): bool => $prompt->timeout === InterviewVoice::TIMEOUT_SECONDS
        && $prompt->contains('experience') && $prompt->isFemale());
    expect(InterviewSession::count())->toBe($sessionCount);
    expect($this->voiceSession->fresh()->status)->toBe('in_progress');
    expect($this->voiceSession->fresh()->conversation_id)->toBeNull();
})->with(['session', 'prep']);

test('voice prep accepts the candidates own applied job without creating a session', function () {
    Transcription::fake(['My practice answer.']);
    $this->voiceSession->delete();
    $job = WorkJob::create([
        'title' => 'Engineer', 'company' => 'Example', 'description' => 'Build software',
        'contacts' => 'jobs@example.com', 'location' => 'Remote', 'technologies' => ['PHP'],
    ]);
    UserWorkJobApplication::create([
        'user_id' => $this->voiceUser->id, 'work_job_id' => $job->id, 'status' => ApplicationStatus::Applied,
    ]);

    $this->actingAs($this->voiceUser)->postJson(route('interview-prep.transcribe'), [
        ...$this->voiceContext,
        'work_job_id' => $job->id,
        'audio' => UploadedFile::fake()->create('recording.wav', 2, 'audio/wav'),
    ])->assertSuccessful()->assertJsonPath('text', 'My practice answer.');

    expect(InterviewSession::count())->toBe(0);
});

test('transcription accepts supported containers and normalizes the SDK filename', function (string $mimeType, string $canonicalMime, string $extension) {
    Transcription::fake(['  A clear answer.  ']);

    $this->actingAs($this->voiceUser)
        ->postJson(route('interview-session.transcribe', $this->voiceSession), [
            'audio' => UploadedFile::fake()->create('untrusted-name.bin', 2, $mimeType),
        ])->assertSuccessful()->assertJsonPath('text', 'A clear answer.');

    Transcription::assertGenerated(fn (TranscriptionPrompt $prompt): bool => $prompt->audio->mimeType() === $canonicalMime
        && $prompt->audio->name() === 'recording.'.$extension
        && $prompt->language === 'en'
        && $prompt->timeout === InterviewVoice::TIMEOUT_SECONDS
        && ! $prompt->isDiarized());
})->with([
    'WebM audio' => ['audio/webm', 'audio/webm', 'webm'],
    'WebM container' => ['video/webm', 'audio/webm', 'webm'],
    'MP4 audio' => ['audio/mp4', 'audio/mp4', 'mp4'],
    'MP4 container' => ['video/mp4', 'audio/mp4', 'mp4'],
    'M4A' => ['audio/x-m4a', 'audio/mp4', 'm4a'],
    'Ogg' => ['audio/ogg', 'audio/ogg', 'ogg'],
    'WAV' => ['audio/x-wav', 'audio/wav', 'wav'],
    'MP3' => ['audio/mpeg', 'audio/mpeg', 'mp3'],
]);

test('voice transcription rejects missing empty unsupported and oversized recordings', function (string $workspace) {
    Transcription::fake();
    $url = interviewVoiceEndpoint($workspace, 'transcribe', $this->voiceSession);
    $this->actingAs($this->voiceUser);

    $this->postJson($url, $this->voiceContext)->assertUnprocessable()->assertJsonValidationErrors('audio');
    $this->postJson($url, [...$this->voiceContext, 'audio' => UploadedFile::fake()->create('empty.wav', 0, 'audio/wav')])
        ->assertUnprocessable();
    $this->postJson($url, [...$this->voiceContext, 'audio' => UploadedFile::fake()->create('not-audio.webm', 2, 'text/plain')])
        ->assertUnprocessable()->assertJsonValidationErrors('audio');
    $this->postJson($url, [...$this->voiceContext, 'audio' => UploadedFile::fake()->create('large.wav', 1537, 'audio/wav')])
        ->assertUnprocessable()->assertJsonValidationErrors('audio');

    Transcription::assertNothingGenerated();
})->with(['session', 'prep']);

test('voice transcription accepts the upload size boundary', function () {
    Transcription::fake(['Within the limit.']);

    $this->actingAs($this->voiceUser)->postJson(route('interview-session.transcribe', $this->voiceSession), [
        'audio' => UploadedFile::fake()->create('limit.wav', 1536, 'audio/wav'),
    ])->assertSuccessful();
});

test('voice transcription returns an actionable error for silence', function (string $workspace) {
    Transcription::fake(['   ']);

    $this->actingAs($this->voiceUser)->postJson(interviewVoiceEndpoint($workspace, 'transcribe', $this->voiceSession), [
        ...$this->voiceContext, 'audio' => UploadedFile::fake()->create('silence.wav', 2, 'audio/wav'),
    ])->assertUnprocessable()->assertJsonPath('code', 'no_speech');
})->with(['session', 'prep']);

test('voice playback enforces the text length and empty input limits', function (string $workspace) {
    Audio::fake([interviewVoiceAudioResponse()]);
    $url = interviewVoiceEndpoint($workspace, 'audio', $this->voiceSession);
    $this->actingAs($this->voiceUser);

    foreach (['', '   ', str_repeat('a', 4001)] as $content) {
        $this->postJson($url, [...$this->voiceContext, 'content' => $content])
            ->assertUnprocessable()->assertJsonValidationErrors('content');
    }
    Audio::assertNothingGenerated();
    $this->postJson($url, [...$this->voiceContext, 'content' => str_repeat('a', 4000)])->assertSuccessful();
})->with(['session', 'prep']);

test('voice playback rejects empty or non audio provider responses', function (string $bytes, ?string $mimeType) {
    Audio::fake([interviewVoiceAudioResponse($bytes, $mimeType)]);

    $this->actingAs($this->voiceUser)->postJson(route('interview-session.audio', $this->voiceSession), [
        'content' => 'Please speak this.',
    ])->assertStatus(502)->assertJsonPath('code', 'invalid_voice_response');
})->with([
    'empty bytes' => ['', 'audio/mpeg'],
    'JSON MIME' => ['{"error":"invalid"}', 'application/json'],
    'missing MIME' => ['bytes', null],
]);

test('voice failures expose only safe messages and log no provider secrets', function (string $workspace, string $operation) {
    $secret = 'sk-test-never-expose-this';
    $failure = fn () => throw new RuntimeException('Authorization: Bearer '.$secret);
    Audio::fake($failure);
    Transcription::fake($failure);
    Log::spy();

    $response = $this->actingAs($this->voiceUser)->postJson(interviewVoiceEndpoint($workspace, $operation, $this->voiceSession), [
        ...$this->voiceContext,
        'content' => 'Please speak this.',
        'audio' => UploadedFile::fake()->create('answer.wav', 2, 'audio/wav'),
    ])->assertServiceUnavailable()->assertJsonPath('code', 'voice_unavailable');

    expect($response->getContent())->not->toContain($secret);
    Log::shouldHaveReceived('warning')->once()->with('Interview voice provider request failed.', [
        'operation' => $operation === 'audio' ? 'audio' : 'transcription',
        'exception_class' => RuntimeException::class,
    ]);
    Log::shouldNotHaveReceived('error');
})->with(['session', 'prep'])->with(['audio', 'transcribe']);

test('voice requests with missing configuration fail before any provider request', function (string $operation) {
    config(['ai.providers.openai.key' => null]);

    $this->actingAs($this->voiceUser)->postJson(interviewVoiceEndpoint('session', $operation, $this->voiceSession), [
        'content' => 'Please speak this.',
        'audio' => UploadedFile::fake()->create('answer.wav', 2, 'audio/wav'),
    ])->assertServiceUnavailable()->assertJsonPath('code', 'voice_not_configured');

    Http::assertNothingSent();
})->with(['audio', 'transcribe']);

test('provider HTTP failures become safe JSON voice errors', function (string $operation, int $status) {
    config(['ai.providers.openai.key' => 'not-a-real-key']);
    Http::fake(['*/audio/*' => Http::response(['error' => ['message' => 'sk-test-secret-provider-detail']], $status)]);
    Log::spy();

    $response = $this->actingAs($this->voiceUser)->postJson(interviewVoiceEndpoint('session', $operation, $this->voiceSession), [
        'content' => 'Please speak this.',
        'audio' => UploadedFile::fake()->createWithContent('answer.wav', 'recorded-audio-bytes')->mimeType('audio/wav'),
    ])->assertServiceUnavailable()->assertJsonPath('code', 'voice_unavailable');

    expect($response->getContent())->not->toContain('sk-test-secret-provider-detail');
    Http::assertSentCount(1);
    Log::shouldHaveReceived('warning')->once()->withArgs(function (string $message, array $context): bool {
        return $message === 'Interview voice provider request failed.'
            && array_keys($context) === ['operation', 'exception_class']
            && is_string($context['exception_class']);
    });
})->with(['audio', 'transcribe'])->with([401, 429, 500]);

test('voice requests use the configured providers and default models', function () {
    config([
        'ai.default_for_audio' => 'voice-provider',
        'ai.default_for_transcription' => 'voice-provider',
        'ai.providers.voice-provider' => [
            'driver' => 'openai', 'key' => 'not-a-real-key',
            'models' => ['audio' => ['default' => 'tts-configured'], 'transcription' => ['default' => 'stt-configured']],
        ],
    ]);
    Audio::fake([interviewVoiceAudioResponse()]);
    Transcription::fake(['Configured transcription.']);

    $this->actingAs($this->voiceUser)->postJson(route('interview-session.audio', $this->voiceSession), ['content' => 'Test.'])->assertSuccessful();
    $this->postJson(route('interview-session.transcribe', $this->voiceSession), [
        'audio' => UploadedFile::fake()->create('answer.wav', 2, 'audio/wav'),
    ])->assertSuccessful();

    Audio::assertGenerated(fn (AudioPrompt $prompt): bool => $prompt->provider->name() === 'voice-provider' && $prompt->model === 'tts-configured');
    Transcription::assertGenerated(fn (TranscriptionPrompt $prompt): bool => $prompt->provider->name() === 'voice-provider' && $prompt->model === 'stt-configured');
});

test('the actual SDK multipart request preserves validated container metadata', function () {
    config(['ai.providers.openai.key' => 'not-a-real-key']);
    Http::fake(['*/audio/transcriptions' => Http::response(['text' => 'Recognized answer.'])]);

    $this->actingAs($this->voiceUser)->postJson(route('interview-session.transcribe', $this->voiceSession), [
        'audio' => UploadedFile::fake()->createWithContent('wrong-extension.mp3', 'recorded-audio-bytes')->mimeType('video/webm'),
    ])->assertSuccessful()->assertJsonPath('text', 'Recognized answer.');

    Http::assertSent(function (ClientRequest $request): bool {
        return str_ends_with($request->url(), '/audio/transcriptions')
            && str_contains($request->body(), 'filename="recording.webm"')
            && str_contains($request->body(), 'Content-Type: audio/webm');
    });
});
