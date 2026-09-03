<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowRight, KeyRound, LifeBuoy, Palette, ShieldCheck, UserRound } from 'lucide-vue-next';

defineOptions({ layout: { breadcrumbs: [{ title: 'Support', href: '/support' }] } });
</script>

<template>
    <Head title="Support Center" />
    <div class="jobflow-page bg-[#F5F7FB] text-[#14213D] dark:bg-stone-950">
        <div class="jobflow-page-frame space-y-7">
            <header class="rounded-[22px] border border-[#D9E2EF] bg-white p-6 shadow-sm md:p-8 dark:border-slate-800 dark:bg-slate-900">
                <div class="flex items-start gap-4">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-[#051C2E] text-white shadow-sm">
                        <LifeBuoy class="h-6 w-6" />
                    </div>
                    <div>
                        <h1 class="text-[28px] font-bold tracking-tight text-[#0A2E48] dark:text-white">Support Center</h1>
                        <p class="mt-2 max-w-2xl text-[15px] leading-6 text-slate-600 dark:text-slate-300">Manage your account and security settings in one place.</p>
                    </div>
                </div>
            </header>

            <section>
                <div class="mb-4">
                    <h2 class="text-[20px] font-bold text-[#0A2E48] dark:text-white">Account & Security</h2>
                    <p class="mt-1 text-[14px] text-slate-600 dark:text-slate-300">Quick access to the account controls already available in JobFlow.</p>
                </div>

                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <Link href="/settings/security" class="group rounded-[18px] border border-[#D9E2EF] bg-white p-5 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:border-[#AEBED2] hover:shadow-md dark:border-slate-800 dark:bg-slate-900">
                        <KeyRound class="h-5 w-5 text-[#0A2E48]" />
                        <h3 class="mt-4 text-[16px] font-bold">Change password</h3>
                        <p class="mt-2 text-[14px] leading-5 text-slate-600 dark:text-slate-300">Update your current password securely.</p>
                        <span class="mt-4 flex items-center gap-1 text-[13px] font-semibold text-[#3157D5]">Open security <ArrowRight class="h-3.5 w-3.5 transition group-hover:translate-x-0.5" /></span>
                    </Link>

                    <Link href="/settings/security" class="group rounded-[18px] border border-[#D9E2EF] bg-white p-5 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:border-[#AEBED2] hover:shadow-md dark:border-slate-800 dark:bg-slate-900">
                        <ShieldCheck class="h-5 w-5 text-[#0A2E48]" />
                        <h3 class="mt-4 text-[16px] font-bold">Two-factor authentication</h3>
                        <p class="mt-2 text-[14px] leading-5 text-slate-600 dark:text-slate-300">Manage 2FA and recovery codes.</p>
                        <span class="mt-4 flex items-center gap-1 text-[13px] font-semibold text-[#3157D5]">Manage 2FA <ArrowRight class="h-3.5 w-3.5 transition group-hover:translate-x-0.5" /></span>
                    </Link>

                    <Link href="/settings/profile" class="group rounded-[18px] border border-[#D9E2EF] bg-white p-5 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:border-[#AEBED2] hover:shadow-md dark:border-slate-800 dark:bg-slate-900">
                        <UserRound class="h-5 w-5 text-[#0A2E48]" />
                        <h3 class="mt-4 text-[16px] font-bold">Profile settings</h3>
                        <p class="mt-2 text-[14px] leading-5 text-slate-600 dark:text-slate-300">Review and update your account information.</p>
                        <span class="mt-4 flex items-center gap-1 text-[13px] font-semibold text-[#3157D5]">Open profile <ArrowRight class="h-3.5 w-3.5 transition group-hover:translate-x-0.5" /></span>
                    </Link>

                    <Link href="/settings/appearance" class="group rounded-[18px] border border-[#D9E2EF] bg-white p-5 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:border-[#AEBED2] hover:shadow-md dark:border-slate-800 dark:bg-slate-900">
                        <Palette class="h-5 w-5 text-[#0A2E48]" />
                        <h3 class="mt-4 text-[16px] font-bold">Appearance</h3>
                        <p class="mt-2 text-[14px] leading-5 text-slate-600 dark:text-slate-300">Choose the interface appearance that suits you.</p>
                        <span class="mt-4 flex items-center gap-1 text-[13px] font-semibold text-[#3157D5]">Appearance settings <ArrowRight class="h-3.5 w-3.5 transition group-hover:translate-x-0.5" /></span>
                    </Link>
                </div>
            </section>
        </div>
    </div>
</template>
