<script setup lang="ts">
import { Head, router, Link } from '@inertiajs/vue3';
import { watchDebounced } from '@vueuse/core';
import { BadgeCheck, MapPin, Sparkles, Heart } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { jobSelection as jobSelectionRoute } from '@/routes';
import { show as jobSelectionShow } from '@/routes/job-selection';
import type { WorkJob } from '@/types/laravel-models';

const props = defineProps<{
    jobs: WorkJob[];
    filters?: {
        incomeLevel?: string;
        region?: string;
        ownSalary?: string;
    };
}>();

// own is a filter, where user writes his own dynamic filter value for salary (this is not a predefined value)

const incomeLevel = ref(props.filters?.incomeLevel || 'does-not-matter');
const region = ref(props.filters?.region || 'does-not-matter');
const ownSalary = ref(props.filters?.ownSalary || '');

interface IncomeLevelOption {
    id: string;
    value: string;
    label: string;
    count: number;
    isSpecial?: boolean;
}

const incomeLevelOptions: IncomeLevelOption[] = [
    {
        id: 'inc-1',
        value: 'does-not-matter',
        label: "It doesn't matter",
        count: 244,
    },
    { id: 'inc-2', value: '45000', label: 'from $45,000', count: 218 },
    { id: 'inc-3', value: '60000', label: 'from $60,000', count: 126 },
    { id: 'inc-4', value: '80000', label: 'from $80,000', count: 61 },
    { id: 'inc-5', value: '90000', label: 'from $90,000', count: 61 },
    {
        id: 'inc-6',
        value: 'own',
        label: 'Own salary',
        count: 0,
        isSpecial: true,
    },
];

interface RegionOption {
    id: string;
    value: string;
    label: string;
    count: number;
}

const regionOptions: RegionOption[] = [
    { id: 'reg-1', value: 'does-not-matter', label: 'All', count: 244 },
    { id: 'reg-2', value: 'New York', label: 'New York', count: 244 },
    { id: 'reg-3', value: 'Los Angeles', label: 'Los Angeles', count: 218 },
    { id: 'reg-4', value: 'Chicago', label: 'Chicago', count: 126 },
    { id: 'reg-5', value: 'Boston', label: 'Boston', count: 61 },
];

watch(ownSalary, (newVal) => {
    if (newVal) {
        incomeLevel.value = 'own';
    }
});

watchDebounced(
    [incomeLevel, region, ownSalary],
    ([newIncomeLevel, newRegion, newOwnSalary]) => {
        router.get(
            jobSelectionRoute.url(),
            {
                incomeLevel: newIncomeLevel,
                region: newRegion,
                ownSalary: newOwnSalary,
            },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
                only: ['jobs', 'filters'],
            },
        );
    },
    { debounce: 300, maxWait: 1000 },
);

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Job Selection',
                href: jobSelectionRoute(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Job Selection" />

    <div
        class="flex h-full flex-1 gap-6 overflow-x-auto bg-white p-4 md:p-6 dark:bg-stone-900"
    >
        <!-- Sidebar Filters -->
        <div class="flex w-72 flex-shrink-0 flex-col gap-6">
            <!-- Income Level Filter -->
            <div class="rounded-xl bg-blueish p-6">
                <h3 class="mb-5 font-bold text-stone-900 dark:text-white">
                    Income level
                </h3>
                <RadioGroup v-model="incomeLevel" class="flex flex-col gap-3.5">
                    <template
                        v-for="option in incomeLevelOptions"
                        :key="option.id"
                    >
                        <div
                            v-if="!option.isSpecial"
                            class="flex items-center justify-between"
                        >
                            <div class="flex items-center gap-3">
                                <RadioGroupItem
                                    :id="option.id"
                                    :value="option.value"
                                />
                                <label
                                    :for="option.id"
                                    class="cursor-pointer text-[15px] text-stone-600 dark:text-stone-300"
                                    >{{ option.label }}</label
                                >
                            </div>
                            <!-- <span class="text-sm text-stone-500">{{ option.count }}</span> -->
                        </div>
                        <div v-else class="mt-1 flex items-center gap-3">
                            <RadioGroupItem
                                :id="option.id"
                                :value="option.value"
                            />
                            <label
                                :for="option.id"
                                class="cursor-pointer text-[15px] text-stone-600 dark:text-stone-300"
                                >{{ option.label }}</label
                            >
                        </div>
                    </template>
                </RadioGroup>

                <div class="mt-4">
                    <Input
                        v-model="ownSalary"
                        placeholder="from"
                        class="rounded-lg border-0 bg-white py-5 placeholder:text-stone-400 focus-visible:ring-1 focus-visible:ring-stone-400 dark:bg-stone-900"
                    />
                </div>
            </div>

            <!-- Region Filter -->
            <div class="rounded-xl bg-blueish p-6">
                <h3 class="mb-5 font-bold text-stone-900 dark:text-white">
                    Region
                </h3>
                <RadioGroup v-model="region" class="flex flex-col gap-3.5">
                    <div
                        v-for="(option, index) in regionOptions"
                        :key="option.id"
                        :class="[
                            index === regionOptions.length - 1 && !option.count
                                ? 'flex items-center gap-3'
                                : 'flex items-center justify-between',
                        ]"
                    >
                        <div class="flex items-center gap-3">
                            <RadioGroupItem
                                :id="option.id"
                                :value="option.value"
                            />
                            <label
                                :for="option.id"
                                class="cursor-pointer text-[15px] text-stone-600 dark:text-stone-300"
                                >{{ option.label }}</label
                            >
                        </div>
                        <!-- <span v-if="option.count > 0" class="text-sm text-stone-500">{{ option.count }}</span> -->
                    </div>
                </RadioGroup>
            </div>

            <!-- Analyze resume -->
            <!--    <div class="rounded-xl bg-blueish p-6 dark:bg-stone-800">
                <div class="flex items-center justify-between mb-1">
                    <h3 class="font-bold text-stone-900 dark:text-white">Analyze your resume</h3>
                    <Sparkles class="h-4 w-4 text-[#0b1c34] dark:text-white" />
                </div>
                <p class="text-[13px] text-stone-500 mb-4">Paste a link to your LinkedIn</p>
                <Input placeholder="type..." class="bg-white border-0 py-5 focus-visible:ring-1 focus-visible:ring-stone-400 dark:bg-stone-900 rounded-lg placeholder:text-stone-400" />
            </div> -->
        </div>

        <!-- Job Listings -->
        <div class="flex flex-1 flex-col gap-5">
            <Link
                v-for="job in jobs"
                :key="job.id"
                :href="jobSelectionShow.url(job)"
                class="group relative flex justify-between rounded-2xl border border-stone-200 bg-white p-6 shadow-sm transition-shadow hover:shadow-md dark:border-stone-800 dark:bg-stone-900"
            >
                <!-- Heart Icon (absolute top right) -->
                <div class="absolute top-6 right-6">
                    <button
                        @click.prevent
                        class="flex h-10 w-10 items-center justify-center rounded-full bg-blueish/60 transition-colors hover:bg-blueish dark:bg-stone-800 dark:hover:bg-stone-700"
                    >
                        <Heart class="h-5 w-5 text-stone-400" />
                    </button>
                </div>

                <div class="flex flex-1 flex-col pr-20">
                    <div class="mb-3 text-sm text-stone-500">
                        There are currently 5 people considering this position
                    </div>

                    <div
                        class="mb-3 flex flex-wrap items-center gap-x-4 gap-y-2"
                    >
                        <h2
                            class="text-[22px] leading-tight font-bold text-stone-900 dark:text-white"
                        >
                            {{ job.title }}
                        </h2>
                        <div class="flex flex-wrap gap-2">
                            <Badge
                                v-for="(tech, index) in job.technologies.slice(
                                    0,
                                    5,
                                )"
                                :key="index"
                                variant="secondary"
                                class="rounded-full border-none bg-primary px-3.5 py-0.5 text-[13px] font-medium tracking-wide text-primary-foreground hover:bg-primary/60"
                            >
                                {{ tech }}
                            </Badge>
                        </div>
                    </div>

                    <div
                        class="mb-5 text-[15px] font-medium text-stone-600 italic dark:text-stone-300"
                    >
                        from ${{
                            Number(job.salary_start).toLocaleString()
                        }}/month
                    </div>

                    <div class="mb-2.5 flex items-center gap-1.5">
                        <span
                            class="text-[15px] font-bold text-stone-900 dark:text-white"
                            >{{ job.company }}</span
                        >
                        <BadgeCheck
                            class="h-5 w-5 fill-stone-900 stroke-white text-stone-900 dark:fill-white dark:stroke-stone-900 dark:text-white"
                        />
                    </div>

                    <div
                        class="flex items-center gap-1.5 text-sm font-medium text-stone-500"
                    >
                        <MapPin class="h-4 w-4" />
                        <span>{{ job.location }}</span>
                    </div>
                </div>

                <div class="flex flex-col justify-end">
                    <Button
                        class="cursor-pointer rounded-full bg-primary px-10 py-6 text-xs font-semibold tracking-wider text-primary-foreground hover:bg-primary/70"
                    >
                        More
                    </Button>
                </div>
            </Link>
        </div>
    </div>
</template>
