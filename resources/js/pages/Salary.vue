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
    industries: string[];
    positionLevels: string[];
    filters: {
        title?: string;
        industry?: string;
        position_level?: string;
    };
    comparison: Comparison | null;
}>();

const role = ref(props.filters.title || '');
const industry = ref(props.filters.industry || '');
const level = ref(props.filters.position_level || '');
const frequency = ref<'weekly' | 'monthly' | 'annually'>('annually');

const compare = () => router.get('/salary', {
    title: role.value,
    industry: industry.value,
    position_level: level.value,
}, {
    preserveState: true,
    preserveScroll: true,
});

const divisor = computed(() => frequency.value === 'weekly' ? 52 : frequency.value === 'monthly' ? 12 : 1);

const formatSalary = (value?: number, currency?: string) => new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: currency || props.comparison?.currency || 'USD',
    maximumFractionDigits: 0,
}).format((value ?? 0) / divisor.value);

const frequencyLabel = computed(() => ({
    weekly: 'Paid Weekly',
    monthly: 'Paid Monthly',
    annually: 'Paid Annually',
})[frequency.value]);

defineOptions({
    layout: { breadcrumbs: [{ title: 'Salary', href: '/salary' }] },
});
</script>

<template>
    <Head title="Salary Comparison" />

    <div class="mx-auto w-full max-w-6xl space-y-6 px-5 py-8">
        <section>
            <p class="text-sm font-bold tracking-wide text-primary uppercase">JobFlow salary data</p>
            <h1 class="mt-1 text-3xl font-black text-slate-950 dark:text-white">Compare salaries from real vacancies</h1>
            <p class="mt-2 max-w-3xl text-slate-600 dark:text-slate-300">
                Results use published JobFlow vacancies with the same industry, position level and a similar normalized core role.
            </p>
        </section>

        <Card class="border-slate-200/70 shadow-sm dark:border-slate-800">
            <CardContent class="p-6">
                <form class="grid gap-4 md:grid-cols-[minmax(0,1.4fr)_1fr_1fr_auto]" @submit.prevent="compare">
                    <div>
                        <label for="salary-role" class="mb-1.5 block text-sm font-semibold">Role or keyword</label>
                        <Input id="salary-role" v-model="role" required placeholder="e.g. Coding Instructor" />
                    </div>
                    <div>
                        <label for="salary-industry" class="mb-1.5 block text-sm font-semibold">Industry</label>
                        <select id="salary-industry" v-model="industry" required class="salary-select">
                            <option value="" disabled>Select industry</option>
                            <option v-for="item in industries" :key="item" :value="item">{{ item }}</option>
                        </select>
                    </div>
                    <div>
                        <label for="salary-level" class="mb-1.5 block text-sm font-semibold">Position level</label>
                        <select id="salary-level" v-model="level" required class="salary-select">
                            <option value="" disabled>Select level</option>
                            <option v-for="item in positionLevels" :key="item" :value="item">{{ item }}</option>
                        </select>
                    </div>
                    <Button type="submit" class="self-end">Compare</Button>
                </form>
            </CardContent>
        </Card>

        <template v-if="comparison">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <p class="font-semibold">
                    Based on {{ comparison.count }} comparable JobFlow {{ comparison.count === 1 ? 'vacancy' : 'vacancies' }}.
                </p>
                <div class="flex rounded-lg border border-slate-200 p-1 dark:border-slate-700">
                    <button
                        v-for="option in (['weekly', 'monthly', 'annually'] as const)"
                        :key="option"
                        type="button"
                        class="rounded-md px-3 py-1.5 text-sm font-semibold capitalize"
                        :class="frequency === option ? 'bg-primary text-primary-foreground' : 'text-slate-600 dark:text-slate-300'"
                        @click="frequency = option"
                    >
                        {{ option }}
                    </button>
                </div>
            </div>

            <Card v-if="comparison.count === 0" class="border-dashed">
                <CardContent class="py-12 text-center text-slate-600 dark:text-slate-300">
                    <BriefcaseBusiness class="mx-auto mb-3 h-9 w-9 text-slate-400" />
                    {{ comparison.message }}
                </CardContent>
            </Card>

            <Card v-else-if="!comparison.sufficient" class="border-amber-200 bg-amber-50/70 dark:border-amber-900 dark:bg-amber-950/20">
                <CardContent class="space-y-4 p-6">
                    <p class="font-semibold text-amber-950 dark:text-amber-100">{{ comparison.message }}</p>
                    <div class="text-2xl font-black">
                        {{ formatSalary(comparison.minimum) }}–{{ formatSalary(comparison.maximum) }}
                        <span class="text-sm font-medium text-slate-500">{{ frequencyLabel }}</span>
                    </div>
                </CardContent>
            </Card>

            <div v-else class="grid gap-4 sm:grid-cols-3">
                <Card v-for="metric in [
                    { label: 'Low', value: comparison.minimum },
                    { label: 'Median', value: comparison.median },
                    { label: 'High', value: comparison.maximum },
                ]" :key="metric.label" class="border-slate-200/70 shadow-sm dark:border-slate-800">
                    <CardContent class="p-5">
                        <p class="text-sm font-bold text-slate-500">{{ metric.label }}</p>
                        <p class="mt-2 text-2xl font-black">{{ formatSalary(metric.value) }}</p>
                        <p class="mt-1 text-xs text-slate-500">{{ frequencyLabel }}</p>
                    </CardContent>
                </Card>
            </div>

            <Card v-if="comparison.comparables.length" class="border-slate-200/70 shadow-sm dark:border-slate-800">
                <CardContent class="p-6">
                    <div class="mb-5 flex items-center gap-2">
                        <BarChart3 class="h-5 w-5 text-primary" />
                        <h2 class="text-lg font-bold">Comparable vacancies</h2>
                    </div>
                    <div class="divide-y divide-slate-200 dark:divide-slate-800">
                        <article v-for="vacancy in comparison.comparables" :key="vacancy.id" class="py-4 first:pt-0 last:pb-0">
                            <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-start">
                                <div>
                                    <h3 class="font-bold">{{ vacancy.title }}</h3>
                                    <p class="text-sm text-slate-500">{{ vacancy.company }}</p>
                                </div>
                                <p class="font-bold">
                                    {{ formatSalary(vacancy.annual_min, vacancy.currency) }}–{{ formatSalary(vacancy.annual_max, vacancy.currency) }}
                                </p>
                            </div>
                            <div class="mt-3 flex flex-wrap gap-2">
                                <Badge v-for="reason in vacancy.reasons" :key="reason" variant="secondary" class="gap-1">
                                    <CheckCircle2 class="h-3.5 w-3.5" />{{ reason }}
                                </Badge>
                            </div>
                        </article>
                    </div>
                </CardContent>
            </Card>
        </template>
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
