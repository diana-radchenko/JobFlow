<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    BookOpen,
    BriefcaseBusiness,
    FileText,
    HelpCircle,
    KeyRound,
    MessageCircleQuestion,
    MonitorCog,
    ShieldCheck,
    Sparkles,
    UserRound,
} from 'lucide-vue-next';

const helpTopics = [
    {
        title: 'Getting started',
        description: 'Learn the main JobFlow workflow from resume to application and interview.',
        icon: BookOpen,
        items: ['Create or select your primary resume', 'Find and save suitable vacancies', 'Track applications and prepare for interviews'],
    },
    {
        title: 'Resumes & applications',
        description: 'Help with resumes, vacancy matching, saved jobs and application status.',
        icon: FileText,
        items: ['Primary resume and matching', 'Saved and Applied job filters', 'Application Tracker statuses'],
    },
    {
        title: 'AI interview',
        description: 'Understand interview preparation, voice mode and interview results.',
        icon: Sparkles,
        items: ['Prepare with AI', 'Text and Voice interview modes', 'Interview feedback and saved results'],
    },
    {
        title: 'Technical issues',
        description: 'Quick checks for browser, microphone, page refresh and access issues.',
        icon: MonitorCog,
        items: ['Refresh the page with Ctrl + F5', 'Check browser microphone permission', 'Retry after reconnecting to the internet'],
    },
];

const faqs = [
    {
        question: 'How do I change my password?',
        answer: 'Open Security settings, enter your current password, then your new password and confirmation. Your existing password-change flow is preserved.',
    },
    {
        question: 'Why is a job match unavailable?',
        answer: 'JobFlow only shows a match when there is enough real resume and vacancy data to calculate it. Missing criteria are not replaced with fake scores.',
    },
    {
        question: 'Where can I see jobs I saved or applied to?',
        answer: 'Open Job Selection and use the Saved or Applied tabs. The list and job count update from your real account data.',
    },
    {
        question: 'Where are my interview details?',
        answer: 'Upcoming interview details are available from the Dashboard interview area, including scheduling context and the meeting link when one was saved.',
    },
];

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Support', href: '/support' }],
    },
});
</script>

<template>
    <Head title="Support" />

    <div class="jobflow-page bg-[#F5F7FB]">
        <div class="jobflow-page-frame space-y-6">
            <header>
                <div class="flex items-center gap-3">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-[#051C2E] text-white shadow-sm">
                        <HelpCircle class="h-5 w-5" />
                    </div>
                    <div>
                        <h1 class="jobflow-page-title">Support & Account</h1>
                        <p class="mt-1 text-[15px] text-slate-600">
                            Manage account security and find help without leaving JobFlow.
                        </p>
                    </div>
                </div>
            </header>

            <section class="grid gap-4 md:grid-cols-3">
                <Link
                    href="/settings/security"
                    class="group rounded-2xl border border-[#D9E2EF] bg-white p-5 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:border-[#0A2E48]/40 hover:shadow-md"
                >
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#EEF3FA] text-[#0A2E48]">
                            <KeyRound class="h-5 w-5" />
                        </div>
                        <span class="text-[13px] font-semibold text-[#3157D5]">Open</span>
                    </div>
                    <h2 class="mt-4 text-[18px] font-semibold text-[#14213D]">Password & Security</h2>
                    <p class="mt-2 text-[14px] leading-6 text-slate-600">
                        Change your password and manage two-factor authentication.
                    </p>
                </Link>

                <Link
                    href="/settings/profile"
                    class="group rounded-2xl border border-[#D9E2EF] bg-white p-5 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:border-[#0A2E48]/40 hover:shadow-md"
                >
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#EEF3FA] text-[#0A2E48]">
                            <UserRound class="h-5 w-5" />
                        </div>
                        <span class="text-[13px] font-semibold text-[#3157D5]">Open</span>
                    </div>
                    <h2 class="mt-4 text-[18px] font-semibold text-[#14213D]">Profile Settings</h2>
                    <p class="mt-2 text-[14px] leading-6 text-slate-600">
                        Review and update your personal account information.
                    </p>
                </Link>

                <Link
                    href="/settings/appearance"
                    class="group rounded-2xl border border-[#D9E2EF] bg-white p-5 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:border-[#0A2E48]/40 hover:shadow-md"
                >
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#EEF3FA] text-[#0A2E48]">
                            <ShieldCheck class="h-5 w-5" />
                        </div>
                        <span class="text-[13px] font-semibold text-[#3157D5]">Open</span>
                    </div>
                    <h2 class="mt-4 text-[18px] font-semibold text-[#14213D]">Appearance</h2>
                    <p class="mt-2 text-[14px] leading-6 text-slate-600">
                        Adjust the interface appearance using the settings already available in JobFlow.
                    </p>
                </Link>
            </section>

            <section>
                <div class="mb-4 flex items-end justify-between gap-4">
                    <div>
                        <h2 class="text-[22px] font-bold text-[#0F2347]">Help Center</h2>
                        <p class="mt-1 text-[14px] text-slate-600">Quick guidance for the areas people use most.</p>
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <article
                        v-for="topic in helpTopics"
                        :key="topic.title"
                        class="rounded-2xl border border-[#D9E2EF] bg-white p-5 shadow-sm"
                    >
                        <div class="flex items-start gap-4">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#EEF3FA] text-[#3157D5]">
                                <component :is="topic.icon" class="h-5 w-5" />
                            </div>
                            <div>
                                <h3 class="text-[17px] font-semibold text-[#14213D]">{{ topic.title }}</h3>
                                <p class="mt-1 text-[14px] leading-6 text-slate-600">{{ topic.description }}</p>
                            </div>
                        </div>
                        <ul class="mt-4 space-y-2 border-t border-slate-100 pt-4 text-[14px] text-slate-600">
                            <li v-for="item in topic.items" :key="item" class="flex gap-2">
                                <span class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-[#3157D5]" />
                                <span>{{ item }}</span>
                            </li>
                        </ul>
                    </article>
                </div>
            </section>

            <section class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_320px]">
                <div class="rounded-2xl border border-[#D9E2EF] bg-white p-5 shadow-sm">
                    <div class="mb-4 flex items-center gap-3">
                        <MessageCircleQuestion class="h-5 w-5 text-[#3157D5]" />
                        <h2 class="text-[20px] font-bold text-[#0F2347]">Frequently asked questions</h2>
                    </div>
                    <div class="divide-y divide-slate-100">
                        <details v-for="faq in faqs" :key="faq.question" class="group py-4 first:pt-1">
                            <summary class="cursor-pointer list-none text-[15px] font-semibold text-[#14213D]">
                                {{ faq.question }}
                            </summary>
                            <p class="mt-2 pr-4 text-[14px] leading-6 text-slate-600">{{ faq.answer }}</p>
                        </details>
                    </div>
                </div>

                <aside class="rounded-2xl border border-[#0A2E48]/20 bg-[#051C2E] p-5 text-white shadow-sm">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/10">
                        <BriefcaseBusiness class="h-5 w-5" />
                    </div>
                    <h2 class="mt-4 text-[19px] font-semibold">Need account help?</h2>
                    <p class="mt-2 text-[14px] leading-6 text-slate-300">
                        Password, security, profile and appearance controls are available through the existing Settings flows so no account functionality is lost.
                    </p>
                    <Link
                        href="/settings/security"
                        class="mt-5 inline-flex h-10 items-center justify-center rounded-lg bg-white px-4 text-[14px] font-semibold text-[#051C2E] transition hover:bg-slate-100"
                    >
                        Open Security Settings
                    </Link>
                </aside>
            </section>
        </div>
    </div>
</template>
