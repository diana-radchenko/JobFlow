<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { watchDebounced } from '@vueuse/core';
import {
    Bookmark,
    BriefcaseBusiness,
    Building2,
    CalendarDays,
    Code2,
    Filter,
    MapPin,
    Sparkles,
    X,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { jobSelection as jobSelectionRoute } from '@/routes';
import { show as jobSelectionShow } from '@/routes/job-selection';
import type { WorkJob } from '@/types/laravel-models';

type Filters = {
    keyword?: string;
    company?: string;
    industry?: string;
    position_level?: string;
    employment_type?: string;
    location?: string;
    workplace_type?: string;
    salary_min?: string;
    salary_max?: string;
    date_posted?: string;
    view?: 'all' | 'saved' | 'applied';
    sort?: 'newest' | 'salary_high' | 'salary_low';
};
type MatchCriterion = { label: string; score: number | null; status: string; matches?: string[] };
type MatchPayload = { score: number | null; criteria: MatchCriterion[]; strong_matches: string[]; gaps: string[] };

const props = defineProps<{
    jobs: (WorkJob & { applied: boolean; saved: boolean })[];
    filters?: Filters;
    filterOptions: { industries: string[]; positionLevels: string[]; employmentTypes: string[]; workplaceTypes: string[] };
    matchingResume?: { id: number; title: string } | null;
    matches?: Record<number, MatchPayload>;
}>();

const keyword = ref(props.filters?.keyword || '');
const company = ref(props.filters?.company || '');
const industry = ref(props.filters?.industry || '');
const positionLevel = ref(props.filters?.position_level || '');
const employmentType = ref(props.filters?.employment_type || '');
const location = ref(props.filters?.location || '');
const workplaceType = ref(props.filters?.workplace_type || '');
const salaryMin = ref(props.filters?.salary_min || '');
const salaryMax = ref(props.filters?.salary_max || '');
const datePosted = ref(props.filters?.date_posted || '');
const activeView = ref<'all' | 'saved' | 'applied'>(props.filters?.view || 'all');
const sort = ref(props.filters?.sort || 'newest');
const mobileFiltersOpen = ref(false);
const matchDialogJobId = ref<number | null>(null);
const selectedMatch = computed(() => matchDialogJobId.value ? props.matches?.[matchDialogJobId.value] : null);
const selectedMatchJob = computed(() => props.jobs.find((job) => job.id === matchDialogJobId.value));

const filterValues = () => ({
    keyword: keyword.value || undefined, company: company.value || undefined,
    industry: industry.value || undefined, position_level: positionLevel.value || undefined,
    employment_type: employmentType.value || undefined, location: location.value || undefined,
    workplace_type: workplaceType.value || undefined, salary_min: salaryMin.value || undefined,
    salary_max: salaryMax.value || undefined, date_posted: datePosted.value || undefined,
    view: activeView.value === 'all' ? undefined : activeView.value,
    sort: sort.value === 'newest' ? undefined : sort.value,
});
watchDebounced([keyword, company, industry, positionLevel, employmentType, location, workplaceType, salaryMin, salaryMax, datePosted, activeView, sort], () =>
    router.get(jobSelectionRoute.url(), filterValues(), { preserveState: true, preserveScroll: true, replace: true, only: ['jobs', 'filters', 'matches', 'matchingResume'] }),
{ debounce: 300, maxWait: 1000 });

const clearFilters = () => {
    keyword.value = ''; company.value = ''; industry.value = ''; positionLevel.value = '';
    employmentType.value = ''; location.value = ''; workplaceType.value = '';
    salaryMin.value = ''; salaryMax.value = ''; datePosted.value = '';
};
const toggleSaved = (job: WorkJob & { saved: boolean }) => job.saved
    ? router.delete(`/saved-jobs/${job.id}`, { preserveScroll: true })
    : router.post(`/saved-jobs/${job.id}`, {}, { preserveScroll: true });
const salary = (job: WorkJob) => {
    const start = Number(job.salary_start ?? 0); const end = Number(job.salary_end ?? 0);
    if (start <= 0 && end <= 0) return 'Salary not specified';
    const formatter = new Intl.NumberFormat('en-US', { style: 'currency', currency: job.salary_currency || 'USD', maximumFractionDigits: 0 });
    const range = start > 0 && end > 0 ? `${formatter.format(start)}–${formatter.format(end)}` : formatter.format(start || end);
    return job.salary_period ? `${range} / ${job.salary_period}` : range;
};
const posted = (job: WorkJob) => job.published_at ? new Intl.DateTimeFormat('en-US', { month: 'short', day: 'numeric', year: 'numeric' }).format(new Date(job.published_at)) : 'Date not specified';
const isNew = (job: WorkJob) => job.published_at ? Date.now() - new Date(job.published_at).getTime() <= 3 * 86400000 : false;
const matchTone = (score: number | null | undefined) => score === null || score === undefined ? 'text-slate-500' : score >= 70 ? 'text-emerald-700' : score >= 45 ? 'text-[#3157D5]' : 'text-slate-600';
const criterionText = (criterion: MatchCriterion) => criterion.status === 'not_specified' ? 'Not specified' : criterion.status === 'not_enough_data' ? 'Not enough data' : `${criterion.score ?? 0}%`;

defineOptions({ layout: { breadcrumbs: [{ title: 'Job Selection', href: jobSelectionRoute() }] } });
</script>

<template>
    <Head title="Job Selection" />
    <div class="jobflow-page min-h-full bg-[#F5F7FB] dark:bg-stone-950">
        <div class="jobflow-page-frame">
            <header class="mb-6 flex flex-wrap items-end justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2"><h1 class="jobflow-page-title dark:text-white">Job Selection</h1><Sparkles class="h-5 w-5 text-[#5267D8]" /></div>
                    <p class="mt-2 text-[15px] text-slate-600 dark:text-slate-300">Explore active opportunities matched to your profile.</p>
                    <p v-if="matchingResume" class="mt-1 text-[13px] text-slate-500">Matched using: <strong class="font-semibold text-[#0A2E48] dark:text-slate-200">{{ matchingResume.title }}</strong></p>
                </div>
                <Button type="button" variant="outline" class="lg:hidden" @click="mobileFiltersOpen = true"><Filter class="mr-2 h-4 w-4" />Filters</Button>
            </header>

            <div class="grid gap-6 lg:grid-cols-[270px_minmax(0,1fr)]">
                <aside class="hidden self-start rounded-2xl border border-[#DCE4EF] bg-white p-5 shadow-[0_4px_18px_rgba(7,31,73,.05)] lg:block dark:border-slate-800 dark:bg-slate-900">
                    <div class="mb-5 flex items-center justify-between"><h2 class="text-[18px] font-semibold">Filters</h2><button type="button" class="text-[14px] font-semibold text-[#3157D5] hover:underline" @click="clearFilters">Clear all</button></div>
                    <div class="space-y-3">
                        <Input v-model="keyword" class="h-11 text-[15px]" placeholder="Job title or keyword" />
                        <Input v-model="company" class="h-11 text-[15px]" placeholder="Company" />
                        <select v-model="industry" class="filter-select"><option value="">All industries</option><option v-for="item in filterOptions.industries" :key="item" :value="item">{{ item }}</option></select>
                        <select v-model="positionLevel" class="filter-select"><option value="">All position levels</option><option v-for="item in filterOptions.positionLevels" :key="item" :value="item">{{ item }}</option></select>
                        <select v-model="employmentType" class="filter-select"><option value="">All employment types</option><option v-for="item in filterOptions.employmentTypes" :key="item" :value="item">{{ item }}</option></select>
                        <Input v-model="location" class="h-11 text-[15px]" placeholder="Location" />
                        <select v-model="workplaceType" class="filter-select"><option value="">All work arrangements</option><option v-for="item in filterOptions.workplaceTypes" :key="item" :value="item">{{ item }}</option></select>
                        <Input v-model="salaryMin" class="h-11 text-[15px]" type="number" min="0" placeholder="Minimum annual salary" />
                        <Input v-model="salaryMax" class="h-11 text-[15px]" type="number" min="0" placeholder="Maximum annual salary" />
                        <select v-model="datePosted" class="filter-select"><option value="">Any date posted</option><option value="1">Past 24 hours</option><option value="7">Past 7 days</option><option value="30">Past 30 days</option></select>
                    </div>
                </aside>

                <main class="min-w-0 space-y-4">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div class="flex rounded-xl border border-[#DCE4EF] bg-white p-1 shadow-sm" aria-label="Vacancy views">
                            <button v-for="view in ['all','saved','applied'] as const" :key="view" type="button" class="rounded-lg px-4 py-2 text-[15px] font-semibold capitalize transition" :class="activeView === view ? 'bg-[#051C2E] text-white' : 'text-slate-600 hover:bg-slate-50'" @click="activeView = view">{{ view === 'all' ? 'All Jobs' : view }}</button>
                        </div>
                        <div class="flex items-center gap-3"><strong class="text-[15px]">{{ jobs.length }} jobs found</strong><select v-model="sort" class="filter-select !w-auto"><option value="newest">Newest</option><option value="salary_high">Salary: High to Low</option><option value="salary_low">Salary: Low to High</option></select></div>
                    </div>

                    <div v-if="jobs.length === 0" class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-14 text-center">
                        <BriefcaseBusiness class="mx-auto mb-4 h-9 w-9 text-slate-400" /><p class="text-[16px] font-semibold">{{ activeView === 'saved' ? 'No saved jobs yet' : activeView === 'applied' ? 'No applications yet' : 'No vacancies match your current filters.' }}</p><Button class="mt-4" variant="outline" @click="activeView === 'all' ? clearFilters() : (activeView = 'all')">{{ activeView === 'all' ? 'Clear filters' : 'Browse Jobs' }}</Button>
                    </div>

                    <article v-for="job in jobs" :key="job.id" class="group rounded-2xl border border-[#DCE4EF] bg-white p-5 shadow-[0_4px_16px_rgba(7,31,73,.045)] transition-all duration-200 hover:-translate-y-0.5 hover:border-[#AEBED2] hover:shadow-[0_9px_24px_rgba(7,31,73,.09)] dark:border-slate-800 dark:bg-slate-900">
                        <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_180px] xl:items-center">
                            <div class="min-w-0">
                                <div class="flex items-start gap-4">
                                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-[#071F49] text-white shadow-sm"><Code2 class="h-5 w-5" /></div>
                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-center gap-2"><Badge v-if="isNew(job)" variant="outline" class="border-blue-200 bg-blue-50 text-blue-700">New</Badge><h2 class="text-[18px] font-semibold text-[#14213D] dark:text-white">{{ job.title }}</h2></div>
                                        <p class="mt-1 flex items-center gap-2 text-[14px] text-slate-600"><Building2 class="h-4 w-4" />{{ job.company }}</p>
                                        <div class="mt-3 flex flex-wrap gap-2"><Badge v-if="job.applied">Applied</Badge><Badge v-if="job.saved" variant="secondary">Saved</Badge><Badge v-if="job.workplace_type" variant="secondary">{{ job.workplace_type }}</Badge><Badge v-if="job.position_level" variant="outline">{{ job.position_level }}</Badge><Badge v-if="job.industry" variant="outline">{{ job.industry }}</Badge></div>
                                        <div class="mt-4 flex flex-wrap gap-x-6 gap-y-2 text-[14px] text-slate-600"><span class="flex items-center gap-2"><MapPin class="h-4 w-4" />{{ job.location || 'Location not specified' }}</span><span class="flex items-center gap-2"><CalendarDays class="h-4 w-4" />Posted {{ posted(job) }}</span></div>
                                        <p class="mt-3 text-[16px] font-semibold text-[#14213D] dark:text-white">{{ salary(job) }}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="flex flex-col items-stretch gap-3 xl:items-end">
                                <div v-if="matches?.[job.id]" class="text-right"><div class="text-[18px] font-bold" :class="matchTone(matches[job.id].score)">{{ matches[job.id].score === null ? 'Match unavailable' : `${matches[job.id].score}% match` }}</div><button type="button" class="mt-1 text-[13px] font-semibold text-[#3157D5] hover:underline" @click="matchDialogJobId = job.id">Why this matches</button></div>
                                <div class="flex gap-2"><Button type="button" variant="outline" class="h-10 w-10 p-0" :aria-label="job.saved ? 'Remove from saved' : 'Save vacancy'" @click="toggleSaved(job)"><Bookmark class="h-4 w-4" :class="job.saved && 'fill-current'" /></Button><Button as-child class="px-5"><Link :href="jobSelectionShow.url(job)">{{ job.applied ? 'View application' : 'View vacancy' }}</Link></Button></div>
                            </div>
                        </div>
                    </article>
                </main>
            </div>
        </div>

        <div v-if="mobileFiltersOpen" class="fixed inset-0 z-50 bg-slate-950/35 lg:hidden" @click.self="mobileFiltersOpen = false"><aside class="absolute inset-y-0 left-0 w-[min(90vw,340px)] overflow-y-auto bg-white p-5 shadow-xl"><div class="mb-5 flex items-center justify-between"><h2 class="text-[19px] font-semibold">Filters</h2><button @click="mobileFiltersOpen = false"><X class="h-5 w-5" /></button></div><div class="space-y-3"><Input v-model="keyword" class="h-11" placeholder="Job title or keyword"/><