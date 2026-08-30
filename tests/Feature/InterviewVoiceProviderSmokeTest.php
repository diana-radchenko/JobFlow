<?php

use App\Models\InterviewSession;
use App\Models\User;
use Illuminate\Http\UploadedFile;

test('opt in real provider voice synthesis and transcription round trip', function () {
    if (getenv('JOBFLOW_RUN_VOICE_PROVIDER_SMOKE') !== '1') {
        $this->markTestSkipped('Set JOBFLOW_RUN_VOICE_PROVIDER_SMOKE=1 explicitly to make two paid provider requests.');
    }

    $user = User::factory()->create();
    $resume = $user->resumes()->create(['title' => 'Neutral Voice Test']);
    $session = InterviewSession::create([
        'user_id' => $user->id,
        'resume_id' => $resume->id,
        'type' => 'technical',
        'complexity' => 'intermediate',
        'mode' => 'live',
        'status' => 'in_progress',
    ]);
    $audio = $this->actingAs($user)->postJson(route('interview-session.audio', $session), [
        'content' => 'This is a short voice test. I build reliable software.',
    ])->assertSuccessful()->assertHeader('Content-Type', 'audio/mpeg');

    expect(strlen($audio->getContent()))->toBeGreaterThan(100);
    file_put_contents(sys_get_temp_dir().'/jobflow-voice-provider.mp3', $audio->getContent());

    $transcript = $this->postJson(route('interview-session.transcribe', $session), [
        'audio' => UploadedFile::fake()->createWithContent('voice-test.mp3', $audio->getContent()),
    ])->assertSuccessful();

    expect(trim($transcript->json('text')))->not->toBeEmpty();
})->group('voice-provider');
