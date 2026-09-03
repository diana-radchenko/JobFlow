<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowRight, BriefcaseBusiness, CircleHelp, FileText, KeyRound, Laptop, LifeBuoy, MessageCircleQuestion, Palette, ShieldCheck, UserRound } from 'lucide-vue-next';

const helpTopics = [
    { title: 'Getting started', description: 'Learn the main JobFlow workflow from resume to application.', icon: LifeBuoy },
    { title: 'Resumes & applications', description: 'Manage resumes, applications, saved jobs and application status.', icon: FileText },
    { title: 'Job Selection', description: 'Understand vacancy filters, matching and saved or applied views.', icon: BriefcaseBusiness },
    { title: 'AI Interview', description: 'Prepare for interviews and understand interview practice features.', icon: MessageCircleQuestion },
    { title: 'Technical issues', description: 'Troubleshoot page, browser or account access problems.', icon: Laptop },
    { title: 'FAQ', description: 'Quick answers to common questions about using JobFlow.', icon: CircleHelp },
];

defineOptions({ layout: { breadcrumbs: [{ title: 'Support', href: '/support' }] } });
</script>

<template>
    <Head title="Support Center" />
    <div class="jobflow-page bg-[#F5F7FB] text-[#14213D] dark:bg-stone-950">
        <div class="jobflow-page-frame space-y-7">
            <header class="rounded-[22px] border border-[#D9E2EF] bg-white p-6 shadow-sm md:p-8 dark:border-slate-800 dark:bg-slate-900">
                <div class="flex items-start gap-4">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-[#051C2E] text-white shadow-sm"><LifeBuoy class="h-6 w-6" /></div>
                    <div><h1 class="text-[28px] font-bold tracking-tight text-[#0A2E48] dark:text-white">Support Center</h1><p class="mt-2 max-w-2xl text-[15px] leading-6 text-slate-600 dark:text-slate-300">Manage your account and get help with JobFlow. Security settings remain connected to the existing account protection features.</p></div>
                </div>
            </header>

            <section>
                <div class="mb-4"><h2 class="text-[20px] font-bold text-[#0A2E48] dark:text-white">Account & Security</h2><p class="mt-1 text-[14px] text-slate-600 dark:text-slate-300">Quick access to the account controls already available in JobFlow.</p></div>
                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <Link href="/settings/security" class="group rounded-[18px] border border-[#D9E2EF] bg-white p-5 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:border-[#AEBED2] hover:shadow-md dark:border-slate-800 dark:bg-slate-900"><KeyRound class="h-5 w-5 text-[#0A2E48]"/><h3 class="mt-4 text-[16px] font-bold">Change password</h3><p class="mt-2 text-[14px] leading-5 text-slate-600">Update your current password securely.</p><span class="mt-4 flex items-center gap-1 text-[13px] font-semibold text-[#3157D5]">Open security <ArrowRight class="h-3.5 w-3.5 transition group-hover:translate-x-0.5"/></span></Link>
                    <Link href="/settings/security" class="group rounded-[18px] border border-[#D9E2EF] bg-white p-5 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:border-[#AEBED2] hover:shadow-md dark:border-slate-800 dark:bg-slate-900"><ShieldCheck class="h-5 w-5 text-[#0A2E48]"/><h3 class="mt-4 text-[16px] font-bold">Two-factor authentication</h3><p class="mt-2 text-[14px] leading-5 text-slate-600">Manage 2FA and recovery codes.</p><span class="mt-4 flex items-center gap-1 text-[13px] font-semibold text-[#3157D5]">Manage 2FA <ArrowRight class="h-3.5 w-3.5"/></span></Link>
                    <Link href="/settings/profile" class="group rounded-[18px] border border-[#D9E2EF] bg-white p-5 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:border-[#AEBED2] hover:shadow-md dark:border-slate-800 dark:bg-slate-900"><UserRound class="h-5 w-5 text-[#0A2E48]"/><h3 class="mt-4 text-[16px] font-bold">Profile settings</h3><p class="mt-2 text-[14px] leading-5 text-slate-600">Review and update your account information.</p><span class="mt-4 flex items-center gap-1 text-[13px] font-semibold text-[#3157D5]">Open profile <ArrowRight class="h-3.5 w-3.5"/></span></Link>
                    <Link href="/settings/appearance" class="group rounded-[18px] border border-[#D9E2EF] bg-white p-5 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:border-[#AEBED2] hover:shadow-md dark:border-slate-800 dark:bg-slate-900"><Palette class="h-5 w-5 text-[#0A2E48]"/><h3 class="mt-4 text-[16px] font-bold">Appearance</h3><p class="mt-2 text-[14px] leading-5 text-slate-600">Choose the interface appearance that suits you.</p><span class="mt-4 flex items-center gap-1 text-[13px] font-semibold text-[#3157D5]">Appearance settings <ArrowRight class="h-3.5 w-3.5"/></span></Link>
                </div>
            </section>

            <section>
                <div class="mb-4"><h2 class="text-[20px] font-bold text-[#0A2E48] dark:text-white">Help & Support</h2><p class="mt-1 text-[14px] text-slate-600 dark:text-slate-300">Choose the area where you need help.</p></div>
                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    <article v-for="topic in helpTopics" :key="topic.title" class="rounded-[18px] border border-[#D9E2EF] bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900"><div class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#EEF3FA] text-[#0A2E48]"><component :is="topic.icon" class="h-4.5 w-4.5"/></div><h3 class="mt-4 text-[16px] font-bold">{{ topic.title }}</h3><p class="mt-2 text-[14px] leading-5 text-slate-600 dark:text-slate-300">{{ topic.description }}</p><p class="mt-4 text-[13px] font-medium text-slate-400">Help content coming next</p></article>
                </div>
            </section>

            <section class="flex flex-col justify-between gap-4 rounded-[20px] bg-[#051C2E] p-6 text-white shadow-md md:flex-row md:items-center"><div><h2 class="text-[18px] font-bold">Still need help?</h2><p class="mt-1 text-[14px] text-slate-300">A direct support channel can be connected here without pretending that a ticket system already exists.</p></div><div class="rounded-xl border border-white/15 bg-white/5 px-4 py-2 text-[13px] font-semibold text-slate-200">Support channel: not configured yet</div></section>
        </div>
    </div>
</template>
