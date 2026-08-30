<?php

namespace App\Services;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Laravel\Ai\Audio;
use Laravel\Ai\Files\Audio as AudioFile;
use Laravel\Ai\Providers\Provider;
use Laravel\Ai\Transcription;
use Throwable;

class InterviewVoice
{
    public const MAX_UPLOAD_KILOBYTES = 1536;

    public const MAX_SPEECH_CHARACTERS = 4000;

    public const TIMEOUT_SECONDS = 45;

    /** @var array<string, array{0: string, 1: string}> */
    private const AUDIO_FORMATS = [
        'audio/webm' => ['audio/webm', 'webm'],
        'video/webm' => ['audio/webm', 'webm'],
        'audio/mp4' => ['audio/mp4', 'mp4'],
        'video/mp4' => ['audio/mp4', 'mp4'],
        'audio/m4a' => ['audio/mp4', 'm4a'],
        'audio/x-m4a' => ['audio/mp4', 'm4a'],
        'audio/ogg' => ['audio/ogg', 'ogg'],
        'application/ogg' => ['audio/ogg', 'ogg'],
        'audio/wav' => ['audio/wav', 'wav'],
        'audio/x-wav' => ['audio/wav', 'wav'],
        'audio/vnd.wave' => ['audio/wav', 'wav'],
        'audio/mpeg' => ['audio/mpeg', 'mp3'],
        'audio/mp3' => ['audio/mpeg', 'mp3'],
        'audio/mpga' => ['audio/mpeg', 'mpga'],
        'audio/flac' => ['audio/flac', 'flac'],
        'audio/x-flac' => ['audio/flac', 'flac'],
    ];

    /** @return array<string, array<int, string>> */
    public function transcriptionRules(): array
    {
        return [
            'audio' => [
                'bail',
                'required',
                'file',
                'max:'.self::MAX_UPLOAD_KILOBYTES,
                'mimetypes:'.implode(',', array_keys(self::AUDIO_FORMATS)),
            ],
        ];
    }

    /** @return array<string, array<int, string>> */
    public function speechRules(): array
    {
        return ['content' => ['required', 'string', 'max:'.self::MAX_SPEECH_CHARACTERS]];
    }

    public function transcribe(UploadedFile $upload): JsonResponse
    {
        if (! $upload->isValid() || $upload->getSize() === 0) {
            return $this->failure('empty_audio', 'The recording is empty. Record your answer again.', 422);
        }

        if ($upload->getSize() > self::MAX_UPLOAD_KILOBYTES * 1024) {
            return $this->failure('audio_too_large', 'The recording is too large. Record a shorter answer.', 422);
        }

        $format = self::AUDIO_FORMATS[$this->normalizeMimeType($upload->getMimeType())] ?? null;

        if ($format === null) {
            return $this->failure('unsupported_audio', 'This recording format is not supported. Try another browser.', 422);
        }

        try {
            if (! $this->isConfigured('transcription', Transcription::isFaked())) {
                return $this->failure('voice_not_configured', 'Voice recognition is not configured. Please use text mode or contact support.', 503);
            }

            $audio = AudioFile::fromPath($upload->getRealPath(), $format[0])
                ->as('recording.'.$format[1]);
            $transcript = Transcription::of($audio)
                ->language('en')
                ->timeout(self::TIMEOUT_SECONDS)
                ->generate();
        } catch (Throwable $exception) {
            $this->logFailure('transcription', $exception);

            return $this->failure('voice_unavailable', 'Voice recognition is temporarily unavailable. Try again or use text mode.', 503);
        }

        $text = trim($transcript->text);

        if ($text === '') {
            return $this->failure('no_speech', 'No speech was detected. Check your microphone and record your answer again.', 422);
        }

        return response()->json(['text' => $text])->header('Cache-Control', 'no-store');
    }

    public function audio(string $content): Response|JsonResponse
    {
        $content = trim($content);

        if ($content === '' || mb_strlen($content) > self::MAX_SPEECH_CHARACTERS) {
            return $this->failure('invalid_speech_text', 'Speech text must contain between 1 and 4000 characters.', 422);
        }

        try {
            if (! $this->isConfigured('audio', Audio::isFaked())) {
                return $this->failure('voice_not_configured', 'Voice playback is not configured. Please use text mode or contact support.', 503);
            }

            $audio = Audio::of($content)
                ->female()
                ->instructions('Speak naturally as a calm, supportive technical interviewer. Keep a warm conversational tone and avoid sounding robotic.')
                ->timeout(self::TIMEOUT_SECONDS)
                ->generate();
            $bytes = $audio->content();
            $mimeType = $this->normalizeMimeType($audio->mimeType());
        } catch (Throwable $exception) {
            $this->logFailure('audio', $exception);

            return $this->failure('voice_unavailable', 'Voice playback is temporarily unavailable. Try again or continue with the text on screen.', 503);
        }

        if ($bytes === '' || ! str_starts_with($mimeType, 'audio/') || ! isset(self::AUDIO_FORMATS[$mimeType])) {
            return $this->failure('invalid_voice_response', 'The voice service returned no playable audio. Try again or continue with the text on screen.', 502);
        }

        return response($bytes)
            ->header('Content-Type', self::AUDIO_FORMATS[$mimeType][0])
            ->header('Cache-Control', 'no-store');
    }

    private function isConfigured(string $capability, bool $faked): bool
    {
        if ($faked) {
            return true;
        }

        $configured = config('ai.default_for_'.$capability);

        if ($configured === null) {
            return false;
        }

        foreach (Provider::formatProviderAndModelList($configured) as $provider => $model) {
            if (filled(config('ai.providers.'.$provider.'.key'))) {
                return true;
            }
        }

        return false;
    }

    private function normalizeMimeType(?string $mimeType): string
    {
        return strtolower(trim(explode(';', $mimeType ?? '')[0]));
    }

    private function logFailure(string $operation, Throwable $exception): void
    {
        Log::warning('Interview voice provider request failed.', [
            'operation' => $operation,
            'exception_class' => $exception::class,
        ]);
    }

    private function failure(string $code, string $message, int $status): JsonResponse
    {
        return response()->json(['message' => $message, 'code' => $code], $status)
            ->header('Cache-Control', 'no-store');
    }
}
