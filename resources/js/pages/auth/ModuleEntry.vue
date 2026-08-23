<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { BriefcaseBusiness, UserRound } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';

defineProps<{
    moduleName: 'JobFlow' | 'HRFlow';
    targetRole: 'candidate' | 'employer';
    currentRole: 'candidate' | 'employer';
    returnUrl: string;
    loginUrl: string;
    registerUrl: string;
    showRegistration: boolean;
    status?: string;
}>();

defineOptions({
    layout: {
        title: 'Switch workspace',
        description:
            'Continue with the account for the workspace you want to use.',
    },
});
</script>

<template>
    <Head :title="`Enter ${moduleName}`" />

    <div class="flex flex-col gap-6">
        <div
            v-if="status"
            class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900"
        >
            {{ status }}
        </div>

        <div class="rounded-xl border border-border bg-card p-6 text-center">
            <div class="mb-4 flex justify-center">
                <div class="rounded-full bg-muted p-4">
                    <BriefcaseBusiness
                        v-if="targetRole === 'employer'"
                        class="h-8 w-8"
                    />
                    <UserRound v-else class="h-8 w-8" />
                </div>
            </div>
            <h1 class="text-2xl font-semibold">Continue to {{ moduleName }}</h1>
            <p class="mt-3 text-sm leading-6 text-muted-foreground">
                You are currently signed in with
                {{ currentRole === 'employer' ? 'an' : 'a' }}
                {{ currentRole }} account. To keep both workspaces separate,
                continue with {{ targetRole === 'employer' ? 'an' : 'a' }}
                {{ targetRole }} account. Your current account and its data will
                not be changed.
            </p>
        </div>

        <Button as-child variant="outline" class="w-full">
            <a :href="returnUrl">
                Return to
                {{ currentRole === 'candidate' ? 'JobFlow' : 'HRFlow' }}
            </a>
        </Button>

        <Form :action="loginUrl" method="post" v-slot="{ processing }">
            <Button type="submit" class="w-full" :disabled="processing">
                Sign Out and Continue to {{ moduleName }}
            </Button>
        </Form>

        <Form
            v-if="showRegistration"
            :action="registerUrl"
            method="post"
            v-slot="{ processing }"
        >
            <Button
                type="submit"
                variant="outline"
                class="w-full"
                :disabled="processing"
            >
                Create a {{ targetRole }} account
            </Button>
        </Form>
    </div>
</template>
