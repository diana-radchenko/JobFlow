<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { interviewPreparation } from '@/routes';
import { Card, CardContent } from '@/components/ui/card';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { Label } from '@/components/ui/label';
import { Button } from '@/components/ui/button';
import { 
    Mic, 
    Type, 
    Video, 
    ChevronRight, 
    Sparkles,
} from 'lucide-vue-next';
import { ref } from 'vue';

const interviewType = ref('ai-generated');
const complexity = ref('advanced');

const interviewTypes = [
    { id: 'behavioral', label: 'General Behavioral Questions' },
    { id: 'technical', label: 'Technical Interview' },
    { id: 'case-study', label: 'Case Study & Problem Solving' },
    { id: 'ai-generated', label: 'AI-generated questions based on your resume & job description' },
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
                <!-- Interview Type Card -->
                <Card class="bg-slate-100/50 dark:bg-slate-900/50 border-0 shadow-none rounded-[24px]">
                    <CardContent class="p-6">
                        <h3 class="text-lg font-bold text-slate-900 dark:text-slate-100 mb-6 leading-tight">
                            Choosing the type of interview (by vacancy)
                        </h3>
                        
                        <RadioGroup v-model="interviewType" class="space-y-4">
                            <div v-for="type in interviewTypes" :key="type.id" class="flex items-start gap-3">
                                <RadioGroupItem :id="type.id" :value="type.id" class="mt-1" />
                                <Label :for="type.id" class="text-[15px] font-medium text-slate-700 dark:text-slate-300 leading-snug cursor-pointer">
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
                        
                        <RadioGroup v-model="complexity" class="space-y-4">
                            <div v-for="level in complexities" :key="level.id" class="flex items-center gap-3">
                                <RadioGroupItem :id="level.id" :value="level.id" />
                                <Label :for="level.id" class="text-[15px] font-medium text-slate-700 dark:text-slate-300 cursor-pointer">
                                    {{ level.label }}
                                </Label>
                            </div>
                        </RadioGroup>
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
                            class="w-full bg-white dark:bg-slate-950 p-5 sm:p-8 rounded-[24px] sm:rounded-[32px] shadow-sm border border-slate-200/60 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center gap-4 sm:gap-6 text-left transition-all hover:shadow-md hover:border-primary/20 group"
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
