<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { BadgeCheck, MapPin, Heart, ChevronLeft } from 'lucide-vue-next';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { jobSelection as jobSelectionRoute } from '@/routes';
import type { WorkJob } from '@/types/laravel-models';

defineProps<{
    job: WorkJob;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Job Selection',
                href: jobSelectionRoute(),
            },
            {
                title: 'Job Details',
            },
        ],
    },
});
</script>

<template>
    <Head :title="job.title" />

    <div class="flex h-full flex-1 flex-col bg-white dark:bg-stone-900">
        <!-- Back Button -->
        <div class="border-b border-stone-200 dark:border-stone-800">
            <div class="flex items-center gap-2 p-4 md:p-6">
                <Link :href="jobSelectionRoute.url()" class="flex items-center gap-2 text-stone-600 hover:text-stone-900 dark:text-stone-400 dark:hover:text-white transition-colors">
                    <ChevronLeft class="h-5 w-5" />
                    <span>Back to Jobs</span>
                </Link>
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex-1 overflow-y-auto p-4 md:p-6">
            <div class="max-w-3xl mx-auto">
                <!-- Header with Heart Icon -->
                <div class="flex justify-between items-start mb-6">
                    <div class="flex-1">
                        <div class="text-sm text-stone-500 mb-2">
                            There are currently 5 people considering this position
                        </div>
                        
                        <h1 class="text-4xl font-bold text-stone-900 dark:text-white mb-4">{{ job.title }}</h1>
                        
                        <div class="flex items-center gap-1.5 mb-4">
                            <span class="font-bold text-stone-900 dark:text-white text-lg">{{ job.company }}</span>
                            <BadgeCheck class="h-6 w-6 text-stone-900 fill-stone-900 stroke-white dark:text-white dark:fill-white dark:stroke-stone-900" />
                        </div>

                        <div class="flex items-center gap-1.5 text-stone-600 dark:text-stone-300 mb-4">
                            <MapPin class="h-5 w-5" />
                            <span class="text-lg">{{ job.location }}</span>
                        </div>

                        <div class="text-stone-600 dark:text-stone-300 font-medium text-lg mb-6">
                            from ${{ Number(job.salary_start).toLocaleString() }}/month
                        </div>
                    </div>

                    <!-- Heart Icon -->
                    <button class="flex h-12 w-12 items-center justify-center rounded-full bg-blueish/60 hover:bg-blueish dark:bg-stone-800 dark:hover:bg-stone-700 transition-colors flex-shrink-0 ml-4">
                        <Heart class="h-6 w-6 text-stone-400" />
                    </button>
                </div>

                <!-- Technologies -->
                <div class="mb-8">
                    <h2 class="text-lg font-semibold text-stone-900 dark:text-white mb-3">Technologies</h2>
                    <div class="flex flex-wrap gap-2">
                        <Badge v-for="(tech, index) in job.technologies" :key="index" variant="secondary" class="bg-primary hover:bg-primary/60 text-primary-foreground border-none rounded-full px-4 py-2 text-sm font-medium">
                            {{ tech }}
                        </Badge>
                    </div>
                </div>

                <!-- Description -->
                <div class="mb-8">
                    <h2 class="text-lg font-semibold text-stone-900 dark:text-white mb-3">Job Description</h2>
                    <div class="prose prose-sm dark:prose-invert max-w-none">
                        <p class="text-stone-600 dark:text-stone-300 whitespace-pre-wrap leading-relaxed">{{ job.description }}</p>
                    </div>
                </div>

                <!-- Contacts -->
                <div v-if="job.contacts" class="mb-8">
                    <h2 class="text-lg font-semibold text-stone-900 dark:text-white mb-3">Contact Information</h2>
                    <p class="text-stone-600 dark:text-stone-300 whitespace-pre-wrap">{{ job.contacts }}</p>
                </div>

                <!-- Apply Button -->
                <div class="flex gap-4 sticky bottom-0 bg-white dark:bg-stone-900 pt-6 mt-8 border-t border-stone-200 dark:border-stone-800">
                    <Button class="flex-1 bg-primary hover:bg-primary/70 text-primary-foreground rounded-lg px-8 py-6 font-semibold text-base tracking-wide">
                        Apply for Position
                    </Button>
                </div>
            </div>
        </div>
    </div>
</template>
