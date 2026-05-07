<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { interviewPreparation } from '@/routes';
import { store as interviewSessionStore, show as interviewSessionShow, complete as interviewSessionComplete } from '@/actions/App/Http/Controllers/InterviewSessionController';
import { Card, CardContent } from '@/components/ui/card';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { Label } from '@/components/ui/label';
import { Button } from '@/components/ui/button';
import { InfiniteScroll } from '@inertiajs/vue3';
import { 
    Mic, 
    Type, 
    Video, 
    ChevronRight, 
    Sparkles,
    History,
    PlayCircle,
    CheckCircle2
} from 'lucide-vue-next';
import { ref } from 'vue';
import { stringForHuman } from '@/helpers/strings';

const props = defineProps<{
    activeSession?: {
        id: number;
        type: string;
        complexity: string;
        created_at: string;
    } | null;
    pastSessions: {
        data: {
            id: number;
            type: string;
            complexity: string;
            created_at: string;
        }[];
    };
}>();

const interviewType = ref('resume-based');
const complexity = ref('advanced');

const interviewTypes = [
    { id: 'behavioral', label: 'General Behavioral Questions' },
    { id: 'technical', label: 'Technical Interview' },
    { id: 'case-study', label: 'Case Study & Problem Solving' },
    { id: 'resume-based', label: 'AI-generated questions based on your resume & job description' },
];

const complexities = [
    { id: 'beginner', label: 'Beginner' },
    { id: 'intermediate', label: 'Intermediate' },
    { id: 'advanced', label: 'Advanced' },
];

const interviewModes = [
    {
        id: 'live',
        title: 'Live AI Interview Mode',
        description: 'The AI asks questions, the user responds with his voice, the system analyzes tone, pauses, and confidence.',
        icon: Mic,
    },
    {
        id: 'text',
        title: 'Text-Based Interview Mode',
        description: 'The questions and answers are in text format, and AI evaluates the content and style.',
        icon: Type,
    },
    {
        id: 'video',
        title: 'Video Recording Mode',
        description: 'The user records their responses, and the AI analyzes facial expressions, voice, and speech structure.',
        icon: Video,
    },
];

const form = useForm({
    type: '',
    complexity: '',
    mode: '',
});

function startInterview(modeId: string) {
    if (modeId !== 'text') {
        alert('Only Text-Based Interview Mode is currently supported.');
        return;
    }

    if (props.activeSession) {
        alert('You already have an active interview session. Please finish it first.');
        return;
    }

    form.type = interviewType.value;
    form.complexity = complexity.value;
    form.mode = modeId;
    form.post(interviewSessionStore.url());
}

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Interview Preparing',
                href: interviewPreparation(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Interview Preparation" />

    <div class="container mx-auto px-5 py-8 font-sans max-w-[1400px]">
        <div class="mb-8">
            <h1 class="text-sm font-bold text-slate-900 dark:text-slate-100 mb-6">Interview Preparation with AI</h1>
            
            <div class="flex items-start justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-slate-900 dark:text-slate-100 mb-2">Get Ready for Your Interview with AI</h2>
                    <p class="text-lg text-slate-600 dark:text-slate-400">Practice real interview questions, get AI feedback, and boost your confidence!</p>
                </div>
                <Sparkles class="w-6 h-6 text-primary" />
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-[350px_1fr] gap-8">
            <!-- Left Column: Settings -->
            <div class="space-y-6">
                <!-- Active Session Card -->
                <Card v-if="activeSession" class="bg-primary/5 dark:bg-primary/10 border-primary/20 shadow-none rounded-[24px]">
                    <CardContent class="p-6">
                        <div class="flex items-center gap-3 mb-4">
                            <PlayCircle class="w-6 h-6 text-primary" />
                            <h3 class="text-lg font-bold text-slate-900 dark:text-slate-100 leading-tight">
                                Active Interview
                            </h3>
                        </div>
                        <p class="text-sm text-slate-600 dark:text-slate-400 mb-4">
                            You have an ongoing {{ activeSession.complexity }} {{ activeSession.type }} interview.
                        </p>
                        <Link :href="interviewSessionShow.url(activeSession.id)"
                            class="w-full inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50 bg-primary text-primary-foreground shadow hover:bg-primary/90 h-10 px-4 py-2">
                            Continue Interview
                        </Link>
                        <form :action="interviewSessionComplete.url(activeSession.id)"
                            method="POST">
                            <input type="hidden" name="_token" :value="$page.props.csrf_token">
                            <Button type="submit" variant="outline" class="gap-2 cursor-pointer w-full mt-4 dark:bg-primary/15 dark:hover:bg-primary/25">
                                <CheckCircle2 class="w-4 h-4" />
                                Complete Interview
                            </Button>
                        </form>
                    </CardContent>
                </Card>

                <!-- Interview Type Card -->
                <Card class="bg-slate-100/50 dark:bg-slate-900/50 border-0 shadow-none rounded-[24px]">
                    <CardContent class="p-6">
                        <h3 class="text-lg font-bold text-slate-900 dark:text-slate-100 mb-6 leading-tight">
                            Choosing the type of interview (by vacancy)
                        </h3>
                        
                        <RadioGroup v-model="interviewType" class="space-y-4" :disabled="!!activeSession">
                            <div v-for="type in interviewTypes" :key="type.id" class="flex items-start gap-3">
                                <RadioGroupItem :id="type.id" :value="type.id" class="mt-1" />
                                <Label :for="type.id" class="text-[15px] font-medium text-slate-700 dark:text-slate-300 leading-snug cursor-pointer" :class="{ 'opacity-50': !!activeSession }">
                                    {{ type.label }}
                                </Label>
                            </div>
                        </RadioGroup>
                    </CardContent>
                </Card>

                <!-- Complexity Card -->
                <Card class="bg-slate-100/50 dark:bg-slate-900/50 border-0 shadow-none rounded-[24px]">
                    <CardContent class="p-6">
                        <h3 class="text-lg font-bold text-slate-900 dark:text-slate-100 mb-6 leading-tight">
                            Choosing the complexity of the questions
                        </h3>
                        
                        <RadioGroup v-model="complexity" class="space-y-4" :disabled="!!activeSession">
                            <div v-for="level in complexities" :key="level.id" class="flex items-center gap-3">
                                <RadioGroupItem :id="level.id" :value="level.id" />
                                <Label :for="level.id" class="text-[15px] font-medium text-slate-700 dark:text-slate-300 cursor-pointer" :class="{ 'opacity-50': !!activeSession }">
                                    {{ level.label }}
                                </Label>
                            </div>
                        </RadioGroup>
                    </CardContent>
                </Card>

                <!-- Past Sessions -->
                <Card v-if="pastSessions.data && pastSessions.data.length > 0" class="bg-slate-100/50 dark:bg-slate-900/50 border-0 shadow-none rounded-[24px]">
                    <CardContent class="p-6">
                        <div class="flex items-center gap-3 mb-4">
                            <History class="w-5 h-5 text-slate-600" />
                            <h3 class="text-lg font-bold text-slate-900 dark:text-slate-100 leading-tight">
                                Past Interviews
                            </h3>
                        </div>
                        
                        <InfiniteScroll data="pastSessions" manual>
                            <div class="space-y-3">
                                <div v-for="session in pastSessions.data" :key="session.id" class="flex flex-col gap-1 p-3 bg-white dark:bg-slate-950 rounded-xl border border-slate-200 dark:border-slate-800">
                                    <div class="text-sm font-medium text-slate-900 dark:text-slate-100">
                                        {{ stringForHuman(session.complexity) }} &bull; {{ stringForHuman(session.type) }}
                                    </div>
                                    <div class="text-xs text-slate-500">
                                        {{ new Date(session.created_at).toLocaleDateString() }}
                                    </div>
                                    <Link 
                                        :href="interviewSessionShow.url(session.id)"
                                        class="text-xs text-primary hover:underline mt-1"
                                    >
                                        Review
                                    </Link>
                                </div>
                            </div>

                            <template #next="{ loading, fetch, hasMore }">
                                <Button 
                                    v-if="hasMore" 
                                    @click="fetch" 
                                    :disabled="loading"
                                    variant="outline"
                                    class="w-full mt-4 bg-white dark:bg-slate-950 rounded-xl"
                                >
                                    {{ loading ? "Loading..." : "Load More" }}
                                </Button>
                            </template>
                        </InfiniteScroll>
                    </CardContent>
                </Card>
            </div>

            <!-- Right Column: Interview Modes -->
            <Card class="bg-slate-100/50 dark:bg-slate-900/50 border-0 shadow-none rounded-[24px] overflow-hidden">
                <CardContent class="p-4 sm:p-8">
                    <h3 class="text-lg font-medium text-slate-600 dark:text-slate-400 mb-6">AI Interview Modes</h3>
                    
                    <div class="space-y-6">
                        <button 
                            v-for="mode in interviewModes" 
                            :key="mode.id"
                            @click="startInterview(mode.id)"
                            :disabled="form.processing || !!activeSession"
                            class="w-full bg-white dark:bg-slate-950 p-5 sm:p-8 rounded-[24px] sm:rounded-[32px] shadow-sm border border-slate-200/60 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center gap-4 sm:gap-6 text-left transition-all hover:shadow-md hover:border-primary/20 group disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <div class="w-12 h-12 sm:w-16 sm:h-16 shrink-0 flex items-center justify-center">
                                <component :is="mode.icon" class="w-7 h-7 sm:w-10 sm:h-10 text-slate-900 dark:text-slate-100" />
                            </div>
                            
                            <div class="flex-1">
                                <h4 class="text-lg sm:text-xl font-bold text-slate-900 dark:text-slate-100 mb-2">{{ mode.title }}</h4>
                                <p class="text-sm sm:text-base text-slate-600 dark:text-slate-400 leading-relaxed max-w-2xl">
                                    {{ mode.description }}
                                </p>
                            </div>
                            
                            <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-full bg-slate-900 dark:bg-slate-100 flex items-center justify-center text-white dark:text-slate-900 transition-transform group-hover:scale-110 self-end sm:self-auto">
                                <ChevronRight class="w-5 h-5 sm:w-6 sm:h-6" />
                            </div>
                        </button>
                    </div>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
