import { computed, onBeforeUnmount, ref } from 'vue';

const MAX_AUDIO_BYTES = 1536 * 1024;
const MAX_RECORDING_MS = 90_000;
const MIME_TYPES = [
    'audio/webm;codecs=opus',
    'audio/webm',
    'audio/mp4',
    'audio/ogg;codecs=opus',
];

type VoiceOptions = {
    csrfToken: () => unknown;
    transcribeUrl: () => string;
    audioUrl: () => string;
    context?: () => Record<string, unknown>;
    onTranscript: (text: string) => void | Promise<void>;
};

export function useInterviewVoice(options: VoiceOptions) {
    const isListening = ref(false);
    const isStartingListening = ref(false);
    const isTranscribing = ref(false);
    const isPreparingAudio = ref(false);
    const isSpeaking = ref(false);
    const error = ref('');
    const retryAction = ref<(() => Promise<void>) | null>(null);
    const canReplay = ref(false);
    const isSecureBrowserContext = computed(
        () => typeof window !== 'undefined' && window.isSecureContext,
    );
    const canUseVoiceInput = computed(
        () =>
            isSecureBrowserContext.value &&
            typeof navigator !== 'undefined' &&
            Boolean(navigator.mediaDevices?.getUserMedia) &&
            typeof MediaRecorder !== 'undefined',
    );
    const status = computed(() =>
        isStartingListening.value
            ? 'Waiting for microphone permission'
            : isListening.value
              ? 'Listening'
              : isTranscribing.value
                ? 'Transcribing'
                : isPreparingAudio.value
                  ? 'Preparing voice'
                  : isSpeaking.value
                    ? 'Speaking'
                    : 'Ready',
    );
    let recorder: MediaRecorder | null = null;
    let stream: MediaStream | null = null;
    let recordingTimer: ReturnType<typeof setTimeout> | undefined;
    let permissionGeneration = 0;
    let disposed = false;
    let playback: HTMLAudioElement | null = null;
    let playbackUrl: string | null = null;
    let playbackRequest: AbortController | null = null;
    const requests = new Set<AbortController>();

    async function request(
        url: string,
        init: RequestInit,
        timeout = 60_000,
    ): Promise<Response> {
        const token =
            options.csrfToken() ||
            document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')
                ?.content;

        if (typeof token !== 'string' || !token) {
            throw new Error(
                'Your session has expired. Reload this page and try again.',
            );
        }

        const controller = new AbortController();
        requests.add(controller);
        const abort = () => controller.abort();
        init.signal?.addEventListener('abort', abort, { once: true });

        if (init.signal?.aborted) {
            controller.abort();
        }

        const timer = setTimeout(abort, timeout);

        try {
            const response = await fetch(url, {
                ...init,
                method: 'POST',
                credentials: 'same-origin',
                signal: controller.signal,
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': token,
                    'X-Requested-With': 'XMLHttpRequest',
                    ...init.headers,
                },
            });

            if (!response.ok) {
                const data = await response.json().catch(() => null);
                const validation = data?.errors
                    ? Object.values(data.errors).flat()[0]
                    : null;

                throw new Error(
                    response.status === 419
                        ? 'Your session has expired. Reload this page and try again.'
                        : response.status === 413
                          ? 'The recording is too large. Record a shorter answer or type it below.'
                          : typeof validation === 'string'
                            ? validation
                            : data?.message ||
                              'The voice request failed. Please try again or use text.',
                );
            }

            // Keep the timeout active while downloading audio/JSON, not only headers.
            const body = await response.arrayBuffer();

            return new Response(body, {
                status: response.status,
                headers: response.headers,
            });
        } catch (failure) {
            if (controller.signal.aborted) {
                throw new Error(
                    'The request timed out or was cancelled. Please try again.',
                );
            }

            throw failure;
        } finally {
            clearTimeout(timer);
            init.signal?.removeEventListener('abort', abort);
            requests.delete(controller);
        }
    }

    function releaseMicrophone() {
        clearTimeout(recordingTimer);
        stream?.getTracks().forEach((track) => track.stop());
        stream = null;
        isListening.value = false;
    }

    function stopRecording(send = true) {
        permissionGeneration++;
        isStartingListening.value = false;

        if (recorder) {
            if (!send) {
                recorder.onstop = null;
            }

            if (recorder.state !== 'inactive') {
                recorder.stop();
            }
        }

        releaseMicrophone();
    }

    async function transcribe(blob: Blob) {
        if (disposed) {
            return;
        }

        error.value = '';
        retryAction.value = null;

        if (!blob.size || blob.size > MAX_AUDIO_BYTES) {
            error.value = blob.size
                ? 'The recording is too large. Record a shorter answer or type it below.'
                : 'No audio was recorded. Check your microphone and try again.';

            return;
        }

        isTranscribing.value = true;

        try {
            const extension = blob.type.includes('mp4')
                ? 'm4a'
                : blob.type.includes('ogg')
                  ? 'ogg'
                  : 'webm';
            const form = new FormData();
            form.append('audio', blob, `answer.${extension}`);
            form.append('language', 'en');
            Object.entries(options.context?.() ?? {}).forEach(
                ([key, value]) => {
                    if (value !== null && value !== undefined) {
                        form.append(key, String(value));
                    }
                },
            );
            const response = await request(options.transcribeUrl(), {
                body: form,
            });
            const data = await response.json();

            if (typeof data.text !== 'string' || !data.text.trim()) {
                throw new Error(
                    'No speech was detected. Please speak clearly and try again.',
                );
            }

            isTranscribing.value = false;

            if (!disposed) {
                await options.onTranscript(data.text.trim());
            }
        } catch (failure) {
            if (!disposed) {
                error.value =
                    failure instanceof Error
                        ? failure.message
                        : 'Transcription failed. You can type your answer below.';
                retryAction.value = () => transcribe(blob);
            }
        } finally {
            isTranscribing.value = false;
        }
    }

    async function startRecording() {
        if (
            disposed ||
            isListening.value ||
            isStartingListening.value ||
            isTranscribing.value
        ) {
            return;
        }

        error.value = '';
        retryAction.value = null;

        if (!canUseVoiceInput.value) {
            error.value = !isSecureBrowserContext.value
                ? 'Microphone access requires HTTPS or localhost. You can type your answer below.'
                : 'This browser does not support microphone recording. You can type your answer below.';

            return;
        }

        stopAudio();
        isStartingListening.value = true;
        const generation = ++permissionGeneration;
        const permissionTimer = setTimeout(() => {
            if (generation !== permissionGeneration) {
                return;
            }

            permissionGeneration++;
            isStartingListening.value = false;
            error.value =
                'Microphone permission was not received. Allow microphone access, then try again, or use text.';
        }, 30_000);

        try {
            const acquired = await navigator.mediaDevices.getUserMedia({
                audio: true,
            });

            if (disposed || generation !== permissionGeneration) {
                acquired.getTracks().forEach((track) => track.stop());

                return;
            }

            stream = acquired;
            const mimeType = MIME_TYPES.find((type) =>
                MediaRecorder.isTypeSupported(type),
            );
            recorder = new MediaRecorder(stream, {
                ...(mimeType ? { mimeType } : {}),
                audioBitsPerSecond: 64_000,
            });
            const currentRecorder = recorder;
            const chunks: Blob[] = [];
            let size = 0;
            recorder.ondataavailable = (event) => {
                if (event.data.size) {
                    chunks.push(event.data);
                    size += event.data.size;
                }

                if (size > MAX_AUDIO_BYTES) {
                    stopRecording(false);
                    error.value =
                        'The recording is too large. Record a shorter answer or type it below.';
                }
            };
            recorder.onerror = () => {
                stopRecording(false);
                error.value =
                    'Audio recording failed. Check your microphone, then try again or use text.';
            };
            recorder.onstop = () => {
                releaseMicrophone();

                if (!disposed) {
                    void transcribe(
                        new Blob(chunks, {
                            type:
                                currentRecorder.mimeType ||
                                chunks[0]?.type ||
                                'audio/webm',
                        }),
                    );
                }
            };
            recorder.start(1000);
            isListening.value = true;
            recordingTimer = setTimeout(
                () => stopRecording(),
                MAX_RECORDING_MS,
            );
        } catch (failure) {
            releaseMicrophone();

            if (!disposed && generation === permissionGeneration) {
                const name =
                    failure instanceof DOMException ? failure.name : '';
                error.value =
                    name === 'NotAllowedError'
                        ? 'Microphone permission was denied. Allow access in your browser settings, then try again, or use text.'
                        : name === 'NotFoundError'
                          ? 'No microphone was found. Connect a microphone or type your answer below.'
                          : 'The microphone could not start. Close other apps using it, then try again, or use text.';
            }
        } finally {
            clearTimeout(permissionTimer);

            if (generation === permissionGeneration) {
                isStartingListening.value = false;
            }
        }
    }

    function stopAudio() {
        playbackRequest?.abort();
        playbackRequest = null;

        if (playback) {
            playback.pause();
            playback.onended = null;
            playback.onerror = null;
            playback = null;
        }

        isPreparingAudio.value = false;
        isSpeaking.value = false;
    }

    async function replay() {
        if (!playbackUrl || disposed) {
            return;
        }

        stopAudio();
        error.value = '';
        playback = new Audio(playbackUrl);
        retryAction.value = null;
        const currentPlayback = playback;
        playback.onended = () => {
            if (playback !== currentPlayback) {
                return;
            }

            isSpeaking.value = false;
        };
        playback.onerror = () => {
            if (playback !== currentPlayback) {
                return;
            }

            isSpeaking.value = false;
            error.value =
                'Audio playback failed. Try Play audio again or read the text.';
            retryAction.value = replay;
        };

        try {
            await currentPlayback.play();

            if (playback !== currentPlayback) {
                return;
            }

            isSpeaking.value = true;
        } catch {
            if (playback !== currentPlayback) {
                return;
            }

            isSpeaking.value = false;
            error.value =
                'Your browser could not play the audio automatically. Select Play audio or read the text.';
            retryAction.value = replay;
        }
    }

    async function speak(content: string) {
        stopAudio();
        error.value = '';
        retryAction.value = null;
        canReplay.value = false;

        if (playbackUrl) {
            URL.revokeObjectURL(playbackUrl);
        }

        playbackUrl = null;

        if (!content.trim() || content.length > 4000) {
            error.value =
                'This response is too long to read aloud. Please read the text instead.';

            return;
        }

        const controller = new AbortController();
        playbackRequest = controller;
        isPreparingAudio.value = true;

        try {
            const response = await request(options.audioUrl(), {
                signal: controller.signal,
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ ...options.context?.(), content }),
            });
            const blob = await response.blob();

            if (disposed || controller.signal.aborted) {
                return;
            }

            if (!blob.size || !blob.type.startsWith('audio/')) {
                throw new Error(
                    'The voice service returned no playable audio. You can read the text instead.',
                );
            }

            playbackUrl = URL.createObjectURL(blob);
            canReplay.value = true;
            playbackRequest = null;
            await replay();
        } catch (failure) {
            if (!disposed && !controller.signal.aborted) {
                error.value =
                    failure instanceof Error
                        ? failure.message
                        : 'Voice playback failed. You can read the text instead.';
                retryAction.value = () => speak(content);
            }
        } finally {
            if (playbackRequest === controller) {
                playbackRequest = null;
                isPreparingAudio.value = false;
            }
        }
    }

    onBeforeUnmount(() => {
        disposed = true;
        stopRecording(false);
        stopAudio();
        requests.forEach((controller) => controller.abort());

        if (playbackUrl) {
            URL.revokeObjectURL(playbackUrl);
        }
    });

    return {
        request,
        startRecording,
        stopRecording,
        speak,
        replay,
        stopAudio,
        error,
        retryAction,
        canReplay,
        canUseVoiceInput,
        isSecureBrowserContext,
        status,
        isListening,
        isStartingListening,
        isTranscribing,
        isPreparingAudio,
        isSpeaking,
    };
}
