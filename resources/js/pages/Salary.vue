<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { BarChart3, BriefcaseBusiness, CheckCircle2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';

type ComparableVacancy = {
    id: number;
    title: string;
    company: string;
    annual_min: number;
    annual_max: number;
    currency: string;
    original_period: string;
    reasons: string[];
};

type Comparison = {
    sufficient: boolean;
    message?: string;
    count: number;
    minimum?: number;
    median?: number;
    maximum?: number;
    currency?: string;
    period?: string;
    comparables: ComparableVacancy[];
};

const props = defineProps<{
    resumes: { id: number; title: string }[];
    industries: string[];
    positionLevels: string[];
    filters: {
        application_id?: number;
        title?: string;
        industry?: string;
        position_level?: string;
    };
    comparison: Comparison | null;
    applications: {
        id: number;
        work_job: {
            id: number;
            title: string;
            company: string;
            industry: string | null;
            position_level: string | null;
        } | null;
    }[];
    selectedApplicationId: number | null;
}>();

const role = ref(props.filters.title || '');
const industry = ref(props.filters.industry || '');
const level = ref(props.filters.position_level || '');
const frequency = ref<'weekly' | 'monthly' | 'annually'>('annually');
const mode = ref<'application' | 'manual'>(
    props.applications.length ? 'application' : 'manual',
);
const applicationId = ref(
    props.selectedApplicationId ? String(props.selectedApplicationId) : '',
);

const compare = () =>
    router.get(
        '/salary',
        {
            application_id:
                mode.value === 'application' ? applicationId.value : undefined,
            title: mode.value === 'manual' ? role.value : undefined,
            industry: mode.value === 'manual' ? industry.value : undefined,
            position_level: mode.value === 'manual' ? level.value : undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
        },
    );

const divisor = computed(() =>
    frequency.value === 'weekly' ? 52 : frequency.value === 'monthly' ? 12 : 1,
);

const formatSalary = (value?: number, currency?: string) =>
    new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: currency || props.comparison?.currency || 'USD',
        maximumFractionDigits: 0,
    }).format((value ?? 0) / divisor.value);

const frequencyLabel = computed(
    () =>
        ({
            weekly: 'Paid Weekly',
            monthly: 'Paid Monthly',
            annually: 'Paid Annually',
        })[frequency.value],
);

defineOptions({
    layout: { breadcrumbs: [{ title: 'Salary', href: '/salary' }] },
});
</script>

<template>
    <Head title="Salary Comparison" />

    <div class="jobflow-page dark:bg-slate-950">
        <div class="mx-auto w-full max-w-7xl space-y-7">
            <section>
                <p
                    class="text-sm font-bold tracking-wide text-primary uppercase"
                >
                    JobFlow salary data
                </p>
                <h1 class="jobflow-page-title mt-1 dark:text-white">
                    Salary Insights
                </h1>
                <p class="mt-2 max-w-3xl text-slate-600 dark:text-slate-300">
                    Compare a job with similar JobFlow vacancies.
                </p>
            </section>

            <Card class="border-slate-200/70 shadow-sm dark:border-slate-800">
                <CardContent class="p-6">
                    <div class="mb-5 flex gap-2">
                        <Button
                            type="button"
                            :variant="
                                mode === 'application' ? 'default' : 'outline'
                            "
                            @click="mode = 'application'"
                            >Compare My Application</Button
                        >
                        <Button
                            type="button"
                            :variant="mode === 'manual' ? 'default' : 'outline'"
                            @click="mode = 'manual'"
                            >Search Manually</Button
                        >
                    </div>
                    <form
                        v-if="mode === 'application'"
                        class="flex flex-col gap-4 sm:flex-row"
                        @submit.prevent="compare"
                    >
                        <div class="flex-1">
                            <label
                                for="salary-application"
                                class="mb-1.5 block text-sm font-semibold"
                                >Select vacancy/application</label
                            >
                            <select
                                id="salary-application"
                                v-model="applicationId"
                                required
                                class="salary-select"
                            >
                                <option value="" disabled>
                                    Select an application
                                </option>
                                <option
                                    v-for="application in applications"
                                    :key="application.id"
                                    :value="String(application.id)"
                                >
                                    {{ application.work_job?.title }} —
                                    {{ application.work_job?.company }}
                                </option>
                            </select>
                        </div>
                        <Button type="submit" class="self-end">Compare</Button>
                    </form>
                    <form
                        v-else
                        class="grid gap-4 md:grid-cols-[minmax(0,1.4fr)_1fr_1fr_auto]"
                        @submit.prevent="compare"
                    >
                        <div>
                            <label
                                for="salary-role"
                                class="mb-1.5 block text-sm font-semibold"
                                >Role or keyword</label
                            >
                            <Input
                                id="salary-role"
                                v-model="role"
                                required
                                placeholder="e.g. Coding Instructor"
                            />
                        </div>
                        <div>
                            <label
                                for="salary-industry"
                                class="mb-1.5 block text-sm font-semibold"
                                >Industry</label
                            >
                            <select
                                id="salary-industry"
                                v-model="industry"
                                required
                                class="salary-select"
                            >
                                <option value="" disabled>
                                    Select industry
                                </option>
                                <option
                                    v-for="item in industries"
                                    :key="item"
                                    :value="item"
                                >
                                    {{ item }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <label
                                for="salary-level"
                                class="mb-1.5 block text-sm font-semibold"
                                >Position level</label
                            >
                            <select
                                id="salary-level"
                                v-model="level"
                                required
                                class="salary-select"
                            >
                                <option value="" disabled>Select level</option>
                                <option
                                    v-for="item in positionLevels"
                                    :key="item"
                                    :value="item"
                                >
                                    {{ item }}
                                </option>
                            </select>
                        </div>
                        <Button type="submit" class="self-end">Compare</Button>
                    </form>
                    <p
                        v-if="mode === 'manual'"
                        class="mt-3 text-sm text-slate-500"
                    >
                        We compare published JobFlow vacancies with the same
                        industry, level, and a similar core role.
                    </p>
                </CardContent>
            </Card>

            <Card
                v-if="!comparison"
                class="border-blue-100 bg-blue-50/60 shadow-none dark:border-blue-950 dark:bg-blue-950/20"
            >
                <CardContent
                    class="grid gap-5 p-5 md:grid-cols-[auto_1fr] md:items-start"
                >
                    <div
                        class="flex h-10 w-10 items-center justify-center rounded-xl bg-white text-primary shadow-sm dark:bg-slate-900"
                    >
                        <BarChart3 class="h-5 w-5" />
                    </div>
                    <div>
                        <h2 class="font-bold text-slate-900 dark:text-white">
                            How salary comparison works
                        </h2>
                        <ol
                            class="mt-3 grid gap-2 text-sm text-slate-600 sm:grid-cols-3 dark:text-slate-300"
                        >
                            <li>
                                <strong class="text-slate-900 dark:text-white"
                                    >1.</strong
                                >
                                Choose an application or describe your role.
                            </li>
                            <li>
                                <strong class="text-slate-900 dark:text-white"
                                    >2.</strong
                                >
                                We find comparable published vacancies.
                            </li>
                            <li>
                                <strong class="text-slate-900 dark:text-white"
                                    >3.</strong
                                >
                                Review the real low, median, and high range.
                            </li>
                        </ol>
                        <p class="mt-3 text-sm font-medium text-slate-500">
                            Your market salary range will appear here after you
                            compare.
                        </p>
                    </div>
                </CardContent>
            </Card>

            <template v-if="comparison">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <p class="font-semibold">
                        Based on {{ comparison.count }} comparable JobFlow
                        {{ comparison.count === 1 ? 'vacancy' : 'vacancies' }}.
                    </p>
                    <div
                        class="flex rounded-lg border border-slate-200 p-1 dark:border-slate-700"
                    >
                        <button
                            v-for="option in [
                                'weekly',
                                'monthly',
                                'annually',
                            ] as const"
                            :key="option"
                            type="button"
                            class="rounded-md px-3 py-1.5 text-sm font-semibold capitalize"
                            :class="
                                frequency === option
                                    ? 'bg-primary text-primary-foreground'
                                    : 'text-slate-600 dark:text-slate-300'
                            "
                            @click="frequency = option"
                        >
                            {{ option }}
                        </button>
                    </div>
                </div>

                <Card v-if="comparison.count === 0" class="border-dashed">
                    <CardContent
                        class="py-12 text-center text-slate-600 dark:text-slate-300"
                    >
                        <BriefcaseBusiness
                            class="mx-auto mb-3 h-9 w-9 text-slate-400"
                        />
                        {{ comparison.message }}
                    </CardContent>
                </Card>

                <Card
                    v-else-if="!comparison.sufficient"
                    class="border-amber-200 bg-amber-50/70 dark:border-amber-900 dark:bg-amber-950/20"
                >
                    <CardContent class="space-y-4 p-6">
                        <p
                            class="font-semibold text-amber-950 dark:text-amber-100"
                        >
                            {{ comparison.message }}
                        </p>
                        <div class="text-3xl font-black">
                            {{ formatSalary(comparison.minimum) }}–{{
                                formatSalary(comparison.maximum)
                            }}
                            <span class="text-sm font-medium text-slate-500">{{
                                frequencyLabel
                            }}</span>
                        </div>
                    </CardContent>
                </Card>

                <Card
                    v-else
                    class="border-[#071F49] bg-[#071F49] text-white shadow-md"
                >
                    <CardContent class="space-y-6 p-6 sm:p-8">
                        <div class="grid grid-cols-3 gap-4 text-center">
                            <div
                                v-for="metric in [
                                    {
                                        label: 'Low',
                                        value: comparison.minimum,
                                    },
                                    {
                                        label: 'Median',
                                        value: comparison.median,
                                    },
                                    {
                                        label: 'High',
                                        value: comparison.maximum,
                                    },
                                ]"
                                :key="metric.label"
                            >
                                <p class="text-sm font-bold text-slate-300">
                                    {{ metric.label }}
                                </p>
                                <p class="mt-2 text-xl font-black sm:text-3xl">
                                    {{ formatSalary(metric.value) }}
                                </p>
                                <p class="mt-1 text-sm text-slate-300">
                                    {{ frequencyLabel }}
                                </p>
                            </div>
                        </div>
                        <div
                            class="relative h-2 rounded-full bg-white/15"
                            aria-hidden="true"
                        >
                            <div
                                class="absolute inset-y-0 left-0 w-1/2 rounded-full bg-gradient-to-r from-[#3157D5] to-[#7047EB]"
                            ></div>
                            <div
                                class="absolute top-1/2 left-1/2 h-4 w-4 -translate-x-1/2 -translate-y-1/2 rounded-full border-2 border-white bg-blue-400"
                            ></div>
                        </div>
                    </CardContent>
                </Card>

                <Card
                    v-if="comparison.comparables.length"
                    class="border-slate-200/70 shadow-sm dark:border-slate-800"
                >
                    <CardContent class="p-6">
                        <div class="mb-5 flex items-center gap-2">
                            <BarChart3 class="h-5 w-5 text-primary" />
                            <h2 class="text-lg font-bold">
                                Comparable vacancies
                            </h2>
                        </div>
                        <div
                            class="divide-y divide-slate-200 dark:divide-slate-800"
                        >
                            <article
                                v-for="vacancy in comparison.comparables"
                                :key="vacancy.id"
                                class="py-4 first:pt-0 last:pb-0"
                            >
                                <div
                                    class="flex flex-col justify-between gap-3 sm:flex-row sm:items-start"
                                >
                                    <div>
                                        <h3 class="text-base font-bold">
                                            {{ vacancy.title }}
                                        </h3>
                                        <p class="text-sm text-slate-500">
                                            {{ vacancy.company }}
                                        </p>
                                    </div>
                                    <p class="font-bold">
                                        {{
                                            formatSalary(
                                                vacancy.annual_min,
                                                vacancy.currency,
                                            )
                                        }}–{{
                                            formatSalary(
                                                vacancy.annual_max,
                                                vacancy.currency,
                                            )
                                        }}
                                    </p>
                                </div>
                                <div class="mt-3 flex flex-wrap gap-2">
                                    <Badge
                                        v-for="reason in vacancy.reasons"
                                        :key="reason"
                                        variant="secondary"
                                        class="gap-1"
                                    >
                                        <CheckCircle2 class="h-3.5 w-3.5" />{{
                                            reason
                                        }}
                                    </Badge>
                                </div>
                            </article>
                        </div>
                    </CardContent>
                </Card>
            </template>
        </div>
    </div>
</template>

<style scoped>
.salary-select {
    width: 100%;
    border-radius: 0.375rem;
    border-width: 1px;
    background: var(--background);
    padding: 0.5rem 0.75rem;
}
</style>
