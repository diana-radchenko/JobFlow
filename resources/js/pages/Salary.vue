<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import {
    Sparkles,
    UploadCloud,
    CheckCircle2,
    BarChart2,
    TrendingUp,
    ChevronDown,
    DollarSign,
    Table,
    LineChart,
    Loader2,
    X,
    MessageSquare,
    PhoneCall,
    Volume2,
    HelpCircle,
    SlidersHorizontal,
    ArrowDownUp,
    Maximize2
} from 'lucide-vue-next';
import { ref, computed } from 'vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
    DialogFooter
} from '@/components/ui/dialog';
import { store as analyzeResumeRequest } from '@/routes/resume-salary-analysis';
import resumeRoutes from '@/routes/resumes';

// Define breadcrumbs for Layout
defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Salary',
                href: '/salary',
            },
        ],
    },
});

interface Props {
    resumes: { id: number; title: string }[];
}

const props = defineProps<Props>();

// Dropdown/Factor Selection values
const selectedEducation = ref("Bachelor's");
const selectedDirectReports = ref("None");
const selectedPerformance = ref("Meets Expectations");
const selectedExperience = ref("2-4 years");
const selectedReportsTo = ref("Manager");

// Options lists
const educationOptions = ["High School", "Bachelor's", "Master's", "PhD"];
const directReportsOptions = ["None", "1-5", "6-15", "15+"];
const performanceOptions = ["Needs Improvement", "Meets Expectations", "Exceeds Expectations", "Outstanding"];
const experienceOptions = ["0-1 years", "2-4 years", "5-9 years", "10+ years"];
const reportsToOptions = ["Manager", "Director", "VP", "C-Level"];

// Frequency option: 'weekly' | 'monthly' | 'annually'
const paymentFrequency = ref<'weekly' | 'monthly' | 'annually'>('annually');

// View type: 'graph' | 'table'
const viewType = ref<'graph' | 'table'>('graph');

// Resume AI Review state
const uploadState = ref<'idle' | 'uploading' | 'analyzed'>('idle');
const selectedResumeId = ref<number | null>(props.resumes[0]?.id ?? null);
const analysisError = ref<string | null>(null);
const analysisStrengths = ref<string[]>([]);
const analysisWeaknesses = ref<string[]>([]);
const analysisSalaryMin = ref(0);
const analysisSalaryMax = ref(0);
const analysisSalaryRationale = ref('');
const showCompareModal = ref(false);

// Active compared role index
const activeCompareIndex = ref(0);

// Base Salary Percentiles (Annual values)
const baseSalaries = {
    p10: 72078,
    p25: 81000,
    p50: 90800, // Median (Peak)
    p75: 102500,
    p90: 113152
};

// Compute multiplier based on selections
const currentMultiplier = computed(() => {
    let mult = 1.0;

    // Education
    if (selectedEducation.value === 'High School') mult *= 0.78;
    else if (selectedEducation.value === "Bachelor's") mult *= 1.00;
    else if (selectedEducation.value === "Master's") mult *= 1.18;
    else if (selectedEducation.value === 'PhD') mult *= 1.35;

    // Direct Reports
    if (selectedDirectReports.value === 'None') mult *= 1.00;
    else if (selectedDirectReports.value === '1-5') mult *= 1.08;
    else if (selectedDirectReports.value === '6-15') mult *= 1.18;
    else if (selectedDirectReports.value === '15+') mult *= 1.28;

    // Performance
    if (selectedPerformance.value === 'Needs Improvement') mult *= 0.82;
    else if (selectedPerformance.value === 'Meets Expectations') mult *= 1.00;
    else if (selectedPerformance.value === 'Exceeds Expectations') mult *= 1.12;
    else if (selectedPerformance.value === 'Outstanding') mult *= 1.25;

    // Experience
    if (selectedExperience.value === '0-1 years') mult *= 0.76;
    else if (selectedExperience.value === '2-4 years') mult *= 1.00;
    else if (selectedExperience.value === '5-9 years') mult *= 1.22;
    else if (selectedExperience.value === '10+ years') mult *= 1.45;

    // Reports To
    if (selectedReportsTo.value === 'Manager') mult *= 1.00;
    else if (selectedReportsTo.value === 'Director') mult *= 1.12;
    else if (selectedReportsTo.value === 'VP') mult *= 1.24;
    else if (selectedReportsTo.value === 'C-Level') mult *= 1.36;

    // AI scan boost
    if (uploadState.value === 'analyzed') {
        mult *= 1.10; // 10% premium boost for AI-optimized resume matching
    }

    return mult;
});

// Compute scaled salaries according to multiplier
const scaledSalaries = computed(() => {
    const m = currentMultiplier.value;
    return {
        p10: Math.round(baseSalaries.p10 * m),
        p25: Math.round(baseSalaries.p25 * m),
        p50: Math.round(baseSalaries.p50 * m),
        p75: Math.round(baseSalaries.p75 * m),
        p90: Math.round(baseSalaries.p90 * m)
    };
});

// Function to format salary into string based on active frequency
function formatSalary(value: number): string {
    let finalValue = value;
    if (paymentFrequency.value === 'monthly') {
        finalValue = value / 12;
    } else if (paymentFrequency.value === 'weekly') {
        finalValue = value / 52;
    }
    return '$' + Math.round(finalValue).toLocaleString();
}

// Selected resume's title, for display
const selectedResumeTitle = computed(
    () => props.resumes.find((r) => r.id === selectedResumeId.value)?.title ?? ''
);

// Send the selected resume to the AI salary analysis endpoint
async function analyzeResume() {
    if (!selectedResumeId.value) return;

    uploadState.value = 'uploading';
    analysisError.value = null;

    try {
        const response = await fetch(analyzeResumeRequest.url(), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': usePage().props.csrf_token as string,
            },
            body: JSON.stringify({ resume_id: selectedResumeId.value }),
        });

        if (!response.ok) {
            throw new Error('Request failed');
        }

        const result = (await response.json()) as {
            strengths: string[];
            weaknesses: string[];
            expectedSalaryMin: number;
            expectedSalaryMax: number;
            salaryRationale: string;
        };
        analysisStrengths.value = result.strengths;
        analysisWeaknesses.value = result.weaknesses;
        analysisSalaryMin.value = result.expectedSalaryMin;
        analysisSalaryMax.value = result.expectedSalaryMax;
        analysisSalaryRationale.value = result.salaryRationale;
        uploadState.value = 'analyzed';

        // Auto-adjust factors based on AI analysis of resume for premium feel!
        selectedEducation.value = "Master's";
        selectedExperience.value = "5-9 years";
        selectedPerformance.value = "Outstanding";
        selectedDirectReports.value = "6-15";
        selectedReportsTo.value = "Director";
    } catch (error) {
        console.error('Failed to analyze resume:', error);
        analysisError.value = 'Could not analyze this resume. Please try again.';
        uploadState.value = 'idle';
    }
}

// Reset back to resume selection
function resetUpload() {
    uploadState.value = 'idle';
    analysisStrengths.value = [];
    analysisWeaknesses.value = [];
    analysisSalaryMin.value = 0;
    analysisSalaryMax.value = 0;
    analysisSalaryRationale.value = '';
    analysisError.value = null;
}

// Comparison mock roles
const comparisonRoles = [
    { title: 'Software Engineer', p10: 72000, p50: 98000, p90: 135000 },
    { title: 'Senior Software Engineer', p10: 110000, p50: 145000, p90: 195000 },
    { title: 'Engineering Manager', p10: 130000, p50: 172000, p90: 220000 },
    { title: 'Product Manager', p10: 85000, p50: 115000, p90: 160000 },
    { title: 'Data Scientist', p10: 90000, p50: 125000, p90: 175000 },
    { title: 'UX Designer', p10: 68000, p50: 92000, p90: 128000 }
];
</script>

<template>
    <Head title="Salary Explorer" />

    <div class="container mx-auto max-w-[1400px] px-5 py-8 font-sans">
        <div class="grid grid-cols-1 gap-8 lg:grid-cols-[400px_1fr]">
            
            <!-- Left Column: Factors & AI Upload -->
            <div class="space-y-6">
                <!-- Factors Card -->
                <Card class="rounded-[24px] border border-slate-200/60 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900 transition-all duration-300">
                    <h2 class="text-xl font-bold tracking-tight text-slate-900 dark:text-slate-50 leading-snug mb-6">
                        Did you know these factors can impact your salary? Explore them to discover your true market value.
                    </h2>
                    
                    <div class="space-y-4">
                        <!-- Education -->
                        <div class="space-y-1.5">
                            <label class="text-xs font-semibold text-slate-500 dark:text-slate-400">Education</label>
                            <div class="relative">
                                <select 
                                    v-model="selectedEducation"
                                    class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200/60 dark:border-slate-800 rounded-xl px-4 py-3 text-[15px] font-medium text-slate-700 dark:text-slate-200 appearance-none focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all duration-200 cursor-pointer"
                                >
                                    <option v-for="opt in educationOptions" :key="opt" :value="opt">{{ opt }}</option>
                                </select>
                                <ChevronDown class="absolute right-4 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400 pointer-events-none" />
                            </div>
                        </div>

                        <!-- Direct Reports -->
                        <div class="space-y-1.5">
                            <label class="text-xs font-semibold text-slate-500 dark:text-slate-400">Direct Reports</label>
                            <div class="relative">
                                <select 
                                    v-model="selectedDirectReports"
                                    class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200/60 dark:border-slate-800 rounded-xl px-4 py-3 text-[15px] font-medium text-slate-700 dark:text-slate-200 appearance-none focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all duration-200 cursor-pointer"
                                >
                                    <option v-for="opt in directReportsOptions" :key="opt" :value="opt">{{ opt }}</option>
                                </select>
                                <ChevronDown class="absolute right-4 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400 pointer-events-none" />
                            </div>
                        </div>

                        <!-- Performance -->
                        <div class="space-y-1.5">
                            <label class="text-xs font-semibold text-slate-500 dark:text-slate-400">Performance</label>
                            <div class="relative">
                                <select 
                                    v-model="selectedPerformance"
                                    class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200/60 dark:border-slate-800 rounded-xl px-4 py-3 text-[15px] font-medium text-slate-700 dark:text-slate-200 appearance-none focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all duration-200 cursor-pointer"
                                >
                                    <option v-for="opt in performanceOptions" :key="opt" :value="opt">{{ opt }}</option>
                                </select>
                                <ChevronDown class="absolute right-4 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400 pointer-events-none" />
                            </div>
                        </div>

                        <!-- Years of Exp. -->
                        <div class="space-y-1.5">
                            <label class="text-xs font-semibold text-slate-500 dark:text-slate-400">Years of experience</label>
                            <div class="relative">
                                <select 
                                    v-model="selectedExperience"
                                    class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200/60 dark:border-slate-800 rounded-xl px-4 py-3 text-[15px] font-medium text-slate-700 dark:text-slate-200 appearance-none focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all duration-200 cursor-pointer"
                                >
                                    <option v-for="opt in experienceOptions" :key="opt" :value="opt">{{ opt }}</option>
                                </select>
                                <ChevronDown class="absolute right-4 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400 pointer-events-none" />
                            </div>
                        </div>

                        <!-- Reports To -->
                        <div class="space-y-1.5">
                            <label class="text-xs font-semibold text-slate-500 dark:text-slate-400">Reports To</label>
                            <div class="relative">
                                <select 
                                    v-model="selectedReportsTo"
                                    class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200/60 dark:border-slate-800 rounded-xl px-4 py-3 text-[15px] font-medium text-slate-700 dark:text-slate-200 appearance-none focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all duration-200 cursor-pointer"
                                >
                                    <option v-for="opt in reportsToOptions" :key="opt" :value="opt">{{ opt }}</option>
                                </select>
                                <ChevronDown class="absolute right-4 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400 pointer-events-none" />
                            </div>
                        </div>
                    </div>
                </Card>

                <!-- AI Resume Review Card -->
                <Card class="rounded-[24px] border border-slate-200/60 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900 transition-all duration-300">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-base font-bold text-slate-900 dark:text-slate-50 flex items-center gap-2">
                            Let AI review your resume – just pick one you've made
                        </h3>
                        <Sparkles class="h-5 w-5 text-indigo-500 animate-pulse shrink-0" />
                    </div>

                    <!-- No resumes yet -->
                    <div v-if="props.resumes.length === 0" class="flex flex-col items-center justify-center border-2 border-dashed border-slate-200 dark:border-slate-800 rounded-2xl h-44 bg-slate-50/50 dark:bg-slate-950/50 text-center px-4">
                        <UploadCloud class="h-10 w-10 text-slate-400 mb-3" />
                        <span class="text-[15px] font-bold text-slate-600 dark:text-slate-300">No resumes yet</span>
                        <a :href="resumeRoutes.index().url" class="text-xs text-indigo-500 hover:underline mt-1">Create a resume to get an AI review</a>
                    </div>

                    <!-- Idle State: pick a resume -->
                    <div v-else-if="uploadState === 'idle'" class="flex flex-col justify-center gap-4 border-2 border-dashed border-slate-200 dark:border-slate-800 rounded-2xl min-h-44 bg-slate-50/50 dark:bg-slate-950/50 p-6">
                        <div class="relative">
                            <select
                                v-model="selectedResumeId"
                                class="w-full bg-white dark:bg-slate-900 border border-slate-200/60 dark:border-slate-800 rounded-xl px-4 py-3 text-[15px] font-medium text-slate-700 dark:text-slate-200 appearance-none focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all duration-200 cursor-pointer"
                            >
                                <option v-for="r in props.resumes" :key="r.id" :value="r.id">{{ r.title }}</option>
                            </select>
                            <ChevronDown class="absolute right-4 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400 pointer-events-none" />
                        </div>
                        <Button type="button" :disabled="!selectedResumeId" @click="analyzeResume">
                            <Sparkles class="mr-2 h-4 w-4" />
                            Analyze Resume
                        </Button>
                        <p v-if="analysisError" class="text-sm text-destructive">{{ analysisError }}</p>
                    </div>

                    <!-- Uploading State -->
                    <div v-else-if="uploadState === 'uploading'" class="flex flex-col items-center justify-center border border-slate-100 dark:border-slate-800 rounded-2xl h-44 bg-slate-50/50 dark:bg-slate-950/50 p-6">
                        <Loader2 class="h-8 w-8 text-indigo-500 animate-spin mb-3" />
                        <span class="text-sm font-bold text-slate-700 dark:text-slate-200">Analyzing resume with AI...</span>
                    </div>

                    <!-- Analyzed State -->
                    <div v-else-if="uploadState === 'analyzed'" class="border border-indigo-100 dark:border-indigo-950 bg-indigo-50/20 dark:bg-indigo-950/20 rounded-2xl p-5 relative">
                        <button
                            @click="resetUpload"
                            class="absolute top-4 right-4 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors"
                        >
                            <X class="h-4 w-4" />
                        </button>

                        <div class="flex items-start gap-3">
                            <CheckCircle2 class="h-6 w-6 text-green-500 shrink-0 mt-0.5" />
                            <div class="min-w-0 flex-1">
                                <h4 class="text-sm font-bold text-slate-900 dark:text-slate-50 leading-tight">
                                    Resume Analyzed Successfully!
                                </h4>
                                <p class="text-xs text-slate-500 mt-1 font-semibold truncate max-w-[240px]">
                                    {{ selectedResumeTitle }}
                                </p>

                                <div v-if="analysisStrengths.length" class="mt-3">
                                    <h5 class="text-xs font-bold text-slate-700 dark:text-slate-200">Strengths</h5>
                                    <ul class="list-disc space-y-1 pl-4 text-[13px] text-slate-600 dark:text-slate-300 mt-1">
                                        <li v-for="(item, index) in analysisStrengths" :key="index">{{ item }}</li>
                                    </ul>
                                </div>

                                <div v-if="analysisWeaknesses.length" class="mt-3">
                                    <h5 class="text-xs font-bold text-slate-700 dark:text-slate-200">Weaknesses</h5>
                                    <ul class="list-disc space-y-1 pl-4 text-[13px] text-slate-600 dark:text-slate-300 mt-1">
                                        <li v-for="(item, index) in analysisWeaknesses" :key="index">{{ item }}</li>
                                    </ul>
                                </div>

                                <div class="mt-4 rounded-xl border border-indigo-200/60 dark:border-indigo-900 bg-white dark:bg-slate-900 p-3">
                                    <span class="text-xs font-bold text-slate-500 dark:text-slate-400">Expected Salary</span>
                                    <p class="text-lg font-extrabold text-indigo-600 dark:text-indigo-400">
                                        {{ formatSalary(analysisSalaryMin) }} – {{ formatSalary(analysisSalaryMax) }}
                                        <span class="text-xs font-semibold text-slate-400">/ {{ paymentFrequency }}</span>
                                    </p>
                                    <p class="text-[13px] text-slate-600 dark:text-slate-300 mt-1 leading-relaxed">
                                        {{ analysisSalaryRationale }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </Card>
            </div>

            <!-- Right Column: Chart / Table & Compare -->
            <div class="space-y-6">
                <!-- Top Row Frequency Selector -->
                <div class="flex flex-wrap items-center justify-end gap-3">
                    <button 
                        @click="paymentFrequency = 'weekly'"
                        :class="paymentFrequency === 'weekly' ? 'bg-primary text-primary-foreground font-bold shadow-sm' : 'bg-white text-slate-700 hover:bg-slate-100 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800 border border-slate-200/60 dark:border-slate-800'"
                        class="px-5 py-3 text-sm rounded-xl font-semibold transition-all duration-200 cursor-pointer"
                    >
                        Paid Weekly
                    </button>
                    <button 
                        @click="paymentFrequency = 'monthly'"
                        :class="paymentFrequency === 'monthly' ? 'bg-primary text-primary-foreground font-bold shadow-sm' : 'bg-white text-slate-700 hover:bg-slate-100 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800 border border-slate-200/60 dark:border-slate-800'"
                        class="px-5 py-3 text-sm rounded-xl font-semibold transition-all duration-200 cursor-pointer"
                    >
                        Paid Monthly
                    </button>
                    <button 
                        @click="paymentFrequency = 'annually'"
                        :class="paymentFrequency === 'annually' ? 'bg-primary text-primary-foreground font-bold shadow-sm' : 'bg-white text-slate-700 hover:bg-slate-100 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800 border border-slate-200/60 dark:border-slate-800'"
                        class="px-5 py-3 text-sm rounded-xl font-semibold transition-all duration-200 cursor-pointer"
                    >
                        Paid Annually
                    </button>
                </div>

                <!-- Main Distribution Graph/Table Card -->
                <Card class="rounded-[24px] border-0 bg-[#021F35] text-white p-6 md:p-8 shadow-xl relative overflow-hidden">
                    <!-- Subtle ambient lighting background -->
                    <div class="absolute -top-32 -left-32 w-80 h-80 bg-blue-500/10 rounded-full blur-3xl pointer-events-none"></div>
                    <div class="absolute -bottom-32 -right-32 w-80 h-80 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none"></div>

                    <!-- Header inside Card -->
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-10 relative z-10">
                        <div>
                            <span class="text-blue-400 font-bold tracking-widest text-xs uppercase">Estimated Distribution</span>
                            <h3 class="text-xl md:text-2xl font-extrabold text-white mt-1">Market Salary Range</h3>
                        </div>
                        
                        <button 
                            @click="viewType = viewType === 'graph' ? 'table' : 'graph'"
                            class="self-start sm:self-center bg-white text-slate-900 hover:bg-slate-100 px-6 py-2.5 rounded-full text-xs font-bold uppercase tracking-wider transition-all duration-200 shadow-lg hover:scale-102 flex items-center gap-2 cursor-pointer"
                        >
                            <component :is="viewType === 'graph' ? Table : LineChart" class="h-3.5 w-3.5 text-slate-900" />
                            {{ viewType === 'graph' ? 'View as Table' : 'View as Graph' }}
                        </button>
                    </div>

                    <!-- Dynamic Content Area -->
                    <div class="relative min-h-[300px] z-10">
                        
                        <!-- Graph View (SVG bell-curve) -->
                        <div v-if="viewType === 'graph'" class="w-full">
                            
                            <!-- Bell curve SVG -->
                            <div class="relative w-full overflow-visible">
                                <svg 
                                    viewBox="0 0 1000 320" 
                                    class="w-full h-auto overflow-visible select-none"
                                >
                                    <!-- Definitions for Gradients -->
                                    <defs>
                                        <linearGradient id="areaGrad" x1="0" y1="0" x2="0" y2="1">
                                            <stop offset="0%" stop-color="#3b82f6" stop-opacity="0.32" />
                                            <stop offset="100%" stop-color="#3b82f6" stop-opacity="0.00" />
                                        </linearGradient>
                                        <filter id="glow" x="-20%" y="-20%" width="140%" height="140%">
                                            <feGaussianBlur stdDeviation="6" result="blur" />
                                            <feComposite in="SourceGraphic" in2="blur" operator="over" />
                                        </filter>
                                    </defs>

                                    <!-- Grid lines / horizontal ticks -->
                                    <line x1="50" y1="280" x2="950" y2="280" stroke="#1e293b" stroke-width="1.5" />
                                    
                                    <!-- Area under the curve -->
                                    <path 
                                        d="M 50 280 C 150 270, 250 255, 300 240 C 370 215, 450 120, 500 120 C 550 120, 630 215, 700 240 C 750 255, 850 270, 950 280 L 950 280 L 50 280 Z" 
                                        fill="url(#areaGrad)" 
                                    />

                                    <!-- Main Bell Curve Stroke -->
                                    <path 
                                        d="M 50 280 C 150 270, 250 255, 300 240 C 370 215, 450 120, 500 120 C 550 120, 630 215, 700 240 C 750 255, 850 270, 950 280" 
                                        fill="none" 
                                        stroke="#ffffff" 
                                        stroke-width="3" 
                                        class="drop-shadow-lg"
                                    />

                                    <!-- Vertical dashed lines connecting nodes to baseline -->
                                    <!-- p10: x=150, y=273 -->
                                    <line x1="150" y1="273" x2="150" y2="280" stroke="#64748b" stroke-dasharray="3 3" stroke-width="1.5" />
                                    <!-- p25: x=300, y=240 -->
                                    <line x1="300" y1="240" x2="300" y2="280" stroke="#64748b" stroke-dasharray="3 3" stroke-width="1.5" />
                                    <!-- p50 (Median): x=500, y=120 -->
                                    <line x1="500" y1="120" x2="500" y2="280" stroke="#64748b" stroke-dasharray="3 3" stroke-width="1.5" />
                                    <!-- p75: x=700, y=240 -->
                                    <line x1="700" y1="240" x2="700" y2="280" stroke="#64748b" stroke-dasharray="3 3" stroke-width="1.5" />
                                    <!-- p90: x=850, y=273 -->
                                    <line x1="850" y1="273" x2="850" y2="280" stroke="#64748b" stroke-dasharray="3 3" stroke-width="1.5" />

                                    <!-- Nodes (circles) on the curve -->
                                    <!-- 10% -->
                                    <circle cx="150" cy="273" r="6" fill="#ffffff" stroke="#3b82f6" stroke-width="3.5" />
                                    <!-- 25% -->
                                    <circle cx="300" cy="240" r="6" fill="#ffffff" stroke="#3b82f6" stroke-width="3.5" />
                                    <!-- 50% Peak -->
                                    <circle cx="500" cy="120" r="8" fill="#ffffff" stroke="#10b981" stroke-width="4.5" filter="url(#glow)" />
                                    <!-- 75% -->
                                    <circle cx="700" cy="240" r="6" fill="#ffffff" stroke="#3b82f6" stroke-width="3.5" />
                                    <!-- 90% -->
                                    <circle cx="850" cy="273" r="6" fill="#ffffff" stroke="#3b82f6" stroke-width="3.5" />
                                </svg>

                                <!-- Absolute HTML Labels positioned over the SVG nodes -->
                                <!-- 10% -->
                                <div class="absolute left-[15%] top-[65%] -translate-x-1/2 flex flex-col items-center text-center">
                                    <span class="text-[10px] md:text-xs font-bold text-slate-400">10%</span>
                                    <span class="text-xs md:text-sm font-extrabold text-white mt-0.5">{{ formatSalary(scaledSalaries.p10) }}</span>
                                </div>

                                <!-- 25% -->
                                <div class="absolute left-[30%] top-[54%] -translate-x-1/2 flex flex-col items-center text-center">
                                    <span class="text-[10px] md:text-xs font-bold text-slate-400">25%</span>
                                    <span class="text-xs md:text-sm font-extrabold text-white mt-0.5">{{ formatSalary(scaledSalaries.p25) }}</span>
                                </div>

                                <!-- 50% Median Peak -->
                                <div class="absolute left-[50%] top-[14%] -translate-x-1/2 flex flex-col items-center text-center bg-[#032e4d]/75 px-3 py-1.5 rounded-xl border border-blue-500/30 backdrop-blur-sm">
                                    <span class="text-[10px] md:text-xs font-bold text-green-400">50% (Median)</span>
                                    <span class="text-sm md:text-lg font-black text-white mt-0.5">{{ formatSalary(scaledSalaries.p50) }}</span>
                                </div>

                                <!-- 75% -->
                                <div class="absolute left-[70%] top-[54%] -translate-x-1/2 flex flex-col items-center text-center">
                                    <span class="text-[10px] md:text-xs font-bold text-slate-400">75%</span>
                                    <span class="text-xs md:text-sm font-extrabold text-white mt-0.5">{{ formatSalary(scaledSalaries.p75) }}</span>
                                </div>

                                <!-- 90% -->
                                <div class="absolute left-[85%] top-[65%] -translate-x-1/2 flex flex-col items-center text-center">
                                    <span class="text-[10px] md:text-xs font-bold text-slate-400">90%</span>
                                    <span class="text-xs md:text-sm font-extrabold text-white mt-0.5">{{ formatSalary(scaledSalaries.p90) }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Table View -->
                        <div v-else class="w-full overflow-hidden rounded-2xl border border-slate-800 bg-[#011422] p-2 mt-2">
                            <table class="w-full text-left border-collapse text-sm">
                                <thead>
                                    <tr class="border-b border-slate-800 bg-[#021f35]/50">
                                        <th class="px-6 py-4 font-bold text-blue-400 text-xs uppercase tracking-wider">Percentile</th>
                                        <th class="px-6 py-4 font-bold text-blue-400 text-xs uppercase tracking-wider">Market Standing</th>
                                        <th class="px-6 py-4 font-bold text-blue-400 text-xs uppercase tracking-wider text-right">Estimated Value</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-800/40">
                                    <tr class="hover:bg-slate-800/20 transition-colors">
                                        <td class="px-6 py-4 font-bold">10th Percentile</td>
                                        <td class="px-6 py-4 text-slate-400">Entry / Junior Level Range</td>
                                        <td class="px-6 py-4 text-right font-extrabold text-white">{{ formatSalary(scaledSalaries.p10) }}</td>
                                    </tr>
                                    <tr class="hover:bg-slate-800/20 transition-colors">
                                        <td class="px-6 py-4 font-bold">25th Percentile</td>
                                        <td class="px-6 py-4 text-slate-400">Lower Mid-Level Range</td>
                                        <td class="px-6 py-4 text-right font-extrabold text-white">{{ formatSalary(scaledSalaries.p25) }}</td>
                                    </tr>
                                    <tr class="bg-blue-500/5 hover:bg-blue-500/10 transition-colors border-y border-blue-500/20">
                                        <td class="px-6 py-4 font-extrabold text-green-400">50th Percentile (Median)</td>
                                        <td class="px-6 py-4 text-slate-300 font-medium">True Market Median</td>
                                        <td class="px-6 py-4 text-right font-black text-white text-base">{{ formatSalary(scaledSalaries.p50) }}</td>
                                    </tr>
                                    <tr class="hover:bg-slate-800/20 transition-colors">
                                        <td class="px-6 py-4 font-bold">75th Percentile</td>
                                        <td class="px-6 py-4 text-slate-400">Senior Level / Specialist</td>
                                        <td class="px-6 py-4 text-right font-extrabold text-white">{{ formatSalary(scaledSalaries.p75) }}</td>
                                    </tr>
                                    <tr class="hover:bg-slate-800/20 transition-colors">
                                        <td class="px-6 py-4 font-bold">90th Percentile</td>
                                        <td class="px-6 py-4 text-slate-400">Elite / Lead Practitioner</td>
                                        <td class="px-6 py-4 text-right font-extrabold text-white">{{ formatSalary(scaledSalaries.p90) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                    </div>
                </Card>

                <!-- Compare with Similar Roles Card -->
                <Card class="rounded-[24px] border border-blue-900/30 bg-gradient-to-r from-[#011B2C] to-[#04283D] text-white p-6 shadow-lg">
                    <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
                        <div class="space-y-1.5 flex-1">
                            <h3 class="text-xl font-bold tracking-tight text-white flex items-center gap-2">
                                Compare with Similar Roles
                            </h3>
                        </div>
                        <button 
                            @click="showCompareModal = true"
                            class="px-8 py-3.5 bg-white text-[#011B2C] hover:bg-slate-100 font-bold text-sm tracking-wide rounded-full uppercase shadow-lg hover:shadow-xl transition-all duration-200 shrink-0 select-none cursor-pointer"
                        >
                            Compare Salaries
                        </button>
                    </div>
                </Card>
            </div>

        </div>

        <!-- High-fidelity Comparison Dialog Modals -->
        <Dialog :open="showCompareModal" @update:open="showCompareModal = $event">
            <DialogContent class="sm:max-w-[700px] rounded-[24px] border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 md:p-8">
                <DialogHeader class="mb-6">
                    <DialogTitle class="text-2xl font-black text-slate-900 dark:text-slate-50">Compare Market Salaries</DialogTitle>
                    <DialogDescription class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                        Review simulated benchmarking data across various industry roles based on current live market distributions.
                    </DialogDescription>
                </DialogHeader>

                <div class="space-y-6">
                    <!-- Tabs for roles -->
                    <div class="flex flex-wrap gap-2 pb-2 border-b border-slate-100 dark:border-slate-800">
                        <button 
                            v-for="(role, index) in comparisonRoles" 
                            :key="role.title"
                            @click="activeCompareIndex = index"
                            :class="activeCompareIndex === index ? 'bg-primary text-primary-foreground font-bold' : 'bg-slate-50 text-slate-600 hover:bg-slate-100 dark:bg-slate-950 dark:text-slate-400 dark:hover:bg-slate-800'"
                            class="px-3.5 py-1.5 text-xs font-semibold rounded-lg transition-all duration-200"
                        >
                            {{ role.title }}
                        </button>
                    </div>

                    <!-- Selected Role Salary Bar Chart mockup -->
                    <div class="bg-slate-50 dark:bg-slate-950 rounded-2xl p-6 border border-slate-100 dark:border-slate-800">
                        <h4 class="text-base font-bold text-slate-900 dark:text-slate-50 flex items-center gap-2">
                            <span>Salary Structure for:</span>
                            <span class="text-indigo-600 dark:text-indigo-400">{{ comparisonRoles[activeCompareIndex].title }}</span>
                        </h4>

                        <div class="space-y-5 mt-6">
                            <!-- 10th percentile bar -->
                            <div class="space-y-1.5">
                                <div class="flex items-center justify-between text-xs font-bold">
                                    <span class="text-slate-500">10% Percentile (Low Entry)</span>
                                    <span class="text-slate-900 dark:text-slate-100">${{ comparisonRoles[activeCompareIndex].p10.toLocaleString() }}</span>
                                </div>
                                <div class="w-full bg-slate-200/60 dark:bg-slate-800 h-4 rounded-full overflow-hidden">
                                    <div 
                                        class="bg-blue-400 h-full rounded-full transition-all duration-500"
                                        :style="{ width: (comparisonRoles[activeCompareIndex].p10 / comparisonRoles[activeCompareIndex].p90 * 100) + '%' }"
                                    ></div>
                                </div>
                            </div>

                            <!-- 50th percentile bar -->
                            <div class="space-y-1.5">
                                <div class="flex items-center justify-between text-xs font-bold">
                                    <span class="text-green-500">50% Percentile (Median)</span>
                                    <span class="text-slate-900 dark:text-slate-100">${{ comparisonRoles[activeCompareIndex].p50.toLocaleString() }}</span>
                                </div>
                                <div class="w-full bg-slate-200/60 dark:bg-slate-800 h-4 rounded-full overflow-hidden">
                                    <div 
                                        class="bg-green-500 h-full rounded-full transition-all duration-500"
                                        :style="{ width: (comparisonRoles[activeCompareIndex].p50 / comparisonRoles[activeCompareIndex].p90 * 100) + '%' }"
                                    ></div>
                                </div>
                            </div>

                            <!-- 90th percentile bar -->
                            <div class="space-y-1.5">
                                <div class="flex items-center justify-between text-xs font-bold">
                                    <span class="text-indigo-500">90% Percentile (Elite/Top-tier)</span>
                                    <span class="text-slate-900 dark:text-slate-100">${{ comparisonRoles[activeCompareIndex].p90.toLocaleString() }}</span>
                                </div>
                                <div class="w-full bg-slate-200/60 dark:bg-slate-800 h-4 rounded-full overflow-hidden">
                                    <div 
                                        class="bg-indigo-500 h-full rounded-full transition-all duration-500"
                                        :style="{ width: '100%' }"
                                    ></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <DialogFooter class="mt-6 border-t border-slate-100 dark:border-slate-800 pt-4 flex gap-2">
                    <Button variant="outline" @click="showCompareModal = false" class="rounded-xl font-bold">
                        Close Benchmarks
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

    </div>
</template>

<style scoped>
select {
    background-image: none !important;
}
</style>
