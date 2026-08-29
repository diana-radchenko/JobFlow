<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { watchDebounced } from '@vueuse/core';
import { BriefcaseBusiness, Building2, CalendarDays, MapPin } from 'lucide-vue-next';
import { ref } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import type { WorkJob } from '@/types/laravel-models';
import { jobSelection as jobSelectionRoute } from '@/routes';
import { show as jobSelectionShow } from '@/routes/job-selection';

type Filters = {
    keyword?: string;
    company?: string;
    industry?: string;
    position_level?: string;
    employment_type?: string;
    location?: string;
    workplace_type?: string;
    salary_min?: string;
    date_posted?: string;
};

const props = defineProps<{
    jobs: WorkJob[];
    filters?: Filters;
    filterOptions: {
        industries: string[];
        positionLevels: string[];
        employmentTypes: string[];
        workplaceTypes: string[];
    };
}>();

const keyword = ref(props.filters?.keyword || '');
const company = ref(props.filters?.company || '');
const industry = ref(props.filters?.industry || '');
const positionLevel = ref(props.filters?.position_level || '');
const employmentType = ref(props.filters?.employment_type || '');
const location = ref(props.filters?.location || '');
const workplaceType = ref(props.filters?.workplace_type || '');
const salaryMin = ref(props.filters?.salary_min || '');
const datePosted = ref(props.filters?.date_posted || '');

const filterValues = () => ({
    keyword: keyword.value || undefined,
    company: company.value || undefined,
    industry: industry.value || undefined,
    position_level: positionLevel.value || undefined,
    employment_type: employmentType.value || undefined,
    location: location.value || undefined,
    workplace_type: workplaceType.value || undefined,
    salary_min: salaryMin.value || undefined,
    date_posted: datePosted.value || undefined,
});

watchDebounced(
    [keyword, company, industry, positionLevel, employmentType, location, workplaceType, salaryMin, datePosted],
    () => router.get(jobSelectionRoute.url(), filterValues(), {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        only: ['jobs', 'filters'],
    }),
    { debounce: 300, maxWait: 1000 },
);

const clearFilters = () => {
    keyword.value = '';
    company.value = '';
    industry.value = '';
    positionLevel.value = '';
    employmentType.value = '';
    location.value = '';
    workplaceType.value = '';
    salaryMin.value = '';
    datePosted.value = '';
};

const salary = (job: WorkJob) => {
    if (job.salary_start === null && job.salary_end === null) {
        return 'Salary not specified';
    }

    const formatter = new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: job.salary_currency || 'USD',
        maximumFractionDigits: 0,
    });
    const range = job.salary_start !== null && job.salary_end !== null
        ? `${formatter.format(job.salary_start)}–${formatter.format(job.salary_end)}`
        : formatter.format(job.salary_start ?? job.salary_end ?? 0);

    return job.salary_period ? `${range} / ${job.salary_period}` : range;
};

const posted = (job: WorkJob) => job.published_at
    ? new Intl.DateTimeFormat('en-US', { month: 'short', day: 'numeric', year: 'numeric' }).format(new Date(job.published_at))
    : 'Date not specified';

defineOptions({
    layout: { breadcrumbs: [{ title: 'Job Selection', href: jobSelectionRoute() }] },
});
</script>

<template>
    <Head title="Job Selection" />

    <div class="flex h-full flex-1 gap-6 bg-white p-4 md:p-6 dark:bg-stone-900">
        <aside class="w-72 flex-shrink-0 self-start rounded-xl bg-blueish p-6">
            <div class="mb-5 flex items-center justify-between gap-3">
                <h2 class="font-bold">Vacancy filters</h2>
                <button type="button" class="text-sm font-semibold text-primary underline-offset-4 hover:underline" @click="clearFilters">
                    Clear all
                </button>
            </div>

            <div class="space-y-3">
                <Input v-model="keyword" aria-label="Job title or keyword" placeholder="Job title or keyword" />
                <Input v-model="company" aria-label="Company" placeholder="Company" />
                <select v-model="industry" aria-label="Industry" class="filter-select">
                    <option value="">All industries</option>
                    <option v-for="item in filterOptions.industries" :key="item" :value="item">{{ item }}</option>
                </select>
                <select v-model="positionLevel" aria-label="Position level" class="filter-select">
                    <option value="">All position levels</option>
                    <option v-for="item in filterOptions.positionLevels" :key="item" :value="item">{{ item }}</option>
                </select>
                <select v-model="employmentType" aria-label="Employment type" class="filter-select">
                    <option value="">All employment types</option>
                    <option v-for="item in filterOptions.employmentTypes" :key="item" :value="item">{{ item }}</option>
                </select>
                <Input v-model="location" aria-label="Location" placeholder="Location" />
                <select v-model="workplaceType" aria-label="Work arrangement" class="filter-select">
                    <option value="">All work arrangements</option>
                    <option v-for="item in filterOptions.workplaceTypes" :key="item" :value="item">{{ item }}</option>
                </select>
                <Input v-model="salaryMin" type="number" min="0" aria-label="Minimum salary" placeholder="Minimum salary" />
                <select v-model="datePosted" aria-label="Date posted" class="filter-select">
                    <option value="">Any date posted</option>
                    <option value="1">Past 24 hours</option>
                    <option value="7">Past 7 days</option>
                    <option value="30">Past 30 days</option>
                </select>
            </div>
        </aside>

        <main class="min-w-0 flex-1 space-y-5">
            <div v-if="jobs.length === 0" class="rounded-2xl border border-dashed border-stone-300 px-6 py-16 text-center dark:border-stone-700">
                <BriefcaseBusiness class="mx-auto mb-4 h-10 w-10 text-stone-400" />
                <p class="font-semibold text-stone-700 dark:text-stone-200">No vacancies match your current filters.</p>
            </div>

            <Link
                v-for="job in jobs"
                :key="job.id"
                :href="jobSelectionShow.url(job)"
                class="group block rounded-2xl border border-stone-200 bg-white p-6 shadow-sm transition hover:border-primary/40 hover:shadow-md dark:border-stone-800 dark:bg-stone-900"
            >
                <div class="flex flex-col justify-between gap-5 lg:flex-row lg:items-end">
                    <div class="min-w-0 space-y-4">
                        <div>
                            <h2 class="text-[22px] font-bold text-stone-900 group-hover:text-primary dark:text-white">{{ job.title }}</h2>
                            <div class="mt-2 flex flex-wrap gap-2">
                                <Badge v-if="job.workplace_type" variant="secondary">{{ job.workplace_type }}</Badge>
                                <Badge v-if="job.industry" variant="outline">{{ job.industry }}</Badge>
                                <Badge v-if="job.position_level" variant="outline">{{ job.position_level }}</Badge>
                                <Badge v-if="job.employment_type" variant="outline">{{ job.employment_type }}</Badge>
                            </div>
                        </div>

                        <div class="grid gap-2 text-sm text-stone-600 sm:grid-cols-2 dark:text-stone-300">
                            <span class="flex items-center gap-2"><Building2 class="h-4 w-4" />{{ job.company }}</span>
                            <span class="flex items-center gap-2"><MapPin class="h-4 w-4" />{{ job.location || 'Location not specified' }}</span>
                            <span class="font-semibold text-stone-800 dark:text-stone-100">{{ salary(job) }}</span>
                            <span class="flex items-center gap-2"><CalendarDays class="h-4 w-4" />Posted {{ posted(job) }}</span>
                        </div>
                    </div>

                    <Button class="rounded-full px-8">View vacancy</Button>
                </div>
            </Link>
        </main>
    </div>
</template>

<style scoped>
.filter-select {
    width: 100%;
    border-radius: 0.375rem;
    border-width: 1px;
    background-color: white;
    padding: 0.5rem;
}

:global(.dark) .filter-select {
    background-color: rgb(28 25 23);
}
</style>
