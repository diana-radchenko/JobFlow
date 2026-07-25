<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { Sparkles, Check, Copy, ChevronLeft } from 'lucide-vue-next';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Spinner } from '@/components/ui/spinner';
import { store as analyzeResumeRequest } from '@/routes/resume-analysis';

interface Props {
    resume: { id: number; title: string };
}

const props = defineProps<Props>();

defineEmits<{
    nextSection: [section: string];
}>();

interface ResumeAnalysisResult {
    strengths: string[];
    weaknesses: string[];
    recommendations: string[];
    professionalSummary: string;
}

const isAnalyzing = ref(false);
const analysisError = ref<string | null>(null);
const analysis = ref<ResumeAnalysisResult | null>(null);
const summaryCopied = ref(false);

const analyzeResume = async () => {
    isAnalyzing.value = true;
    analysisError.value = null;

    try {
        const response = await fetch(analyzeResumeRequest.url(), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': usePage().props.csrf_token as string,
            },
            body: JSON.stringify({ resume_id: props.resume.id }),
        });

        if (!response.ok) {
            throw new Error('Request failed');
        }

        analysis.value = (await response.json()) as ResumeAnalysisResult;
    } catch (error) {
        console.error('Failed to analyze resume:', error);
        analysisError.value =
            'Could not analyze this resume. Please try again.';
    } finally {
        isAnalyzing.value = false;
    }
};

const copyProfessionalSummary = async () => {
    if (!analysis.value) {
        return;
    }

    await navigator.clipboard.writeText(analysis.value.professionalSummary);
    summaryCopied.value = true;
    setTimeout(() => (summaryCopied.value = false), 2000);
};
</script>

<template>
    <div class="space-y-6">
        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <Sparkles class="h-5 w-5 text-primary" />
                    AI Resume Analysis
                </CardTitle>
                <CardDescription>
                    Get an overall review of your resume — strengths,
                    weaknesses, recommendations, and a ready-to-use
                    professional summary.
                </CardDescription>
            </CardHeader>
            <CardContent class="space-y-6">
                <Button
                    type="button"
                    :disabled="isAnalyzing"
                    @click="analyzeResume"
                >
                    <Spinner v-if="isAnalyzing" class="mr-2 h-4 w-4" />
                    <Sparkles v-else class="mr-2 h-4 w-4" />
                    {{ analysis ? 'Re-analyze Resume' : 'Analyze Resume' }}
                </Button>

                <p v-if="analysisError" class="text-sm text-destructive">
                    {{ analysisError }}
                </p>

                <div v-if="analysis" class="space-y-6">
                    <div class="space-y-2">
                        <h4 class="font-semibold">Professional Summary</h4>
                        <div
                            class="flex items-start justify-between gap-3 rounded-lg border border-border bg-muted/40 p-3"
                        >
                            <p class="text-sm text-foreground/80">
                                {{ analysis.professionalSummary }}
                            </p>
                            <Button
                                type="button"
                                size="sm"
                                variant="ghost"
                                class="shrink-0"
                                @click="copyProfessionalSummary"
                            >
                                <Check v-if="summaryCopied" class="h-4 w-4" />
                                <Copy v-else class="h-4 w-4" />
                            </Button>
                        </div>
                    </div>

                    <div class="grid gap-6 sm:grid-cols-2">
                        <div class="space-y-2">
                            <h4 class="font-semibold">Strengths</h4>
                            <ul
                                class="list-disc space-y-1 pl-5 text-sm text-foreground/80"
                            >
                                <li
                                    v-for="(item, index) in analysis.strengths"
                                    :key="index"
                                >
                                    {{ item }}
                                </li>
                            </ul>
                        </div>
                        <div class="space-y-2">
                            <h4 class="font-semibold">Weaknesses</h4>
                            <ul
                                class="list-disc space-y-1 pl-5 text-sm text-foreground/80"
                            >
                                <li
                                    v-for="(item, index) in analysis.weaknesses"
                                    :key="index"
                                >
                                    {{ item }}
                                </li>
                            </ul>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <h4 class="font-semibold">Recommendations</h4>
                        <ul
                            class="list-disc space-y-1 pl-5 text-sm text-foreground/80"
                        >
                            <li
                                v-for="(item, index) in analysis.recommendations"
                                :key="index"
                            >
                                {{ item }}
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="flex border-t pt-4">
                    <Button
                        type="button"
                        variant="outline"
                        @click="$emit('nextSection', 'summary')"
                    >
                        <ChevronLeft class="mr-2 h-4 w-4" />
                        Back to Summary
                    </Button>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
