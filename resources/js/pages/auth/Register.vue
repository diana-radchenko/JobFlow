<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { BriefcaseBusiness, UserRound } from 'lucide-vue-next';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { login } from '@/routes';
import { store } from '@/routes/register';

defineOptions({
    layout: {
        title: 'Create an account',
        description: 'Enter your details below to create your account',
    },
});

// Preselected from /register?type=employer, which the landing page's HRFlow
// "Manage your best team" call to action links to.
const { accountType = 'candidate' } = defineProps<{
    accountType?: 'candidate' | 'employer';
}>();

const selectedProfileType = ref<'candidate' | 'employer'>(accountType);
</script>

<template>
    <Head title="Register" />

    <Form
        v-bind="store.form()"
        :reset-on-success="['password', 'password_confirmation']"
        v-slot="{ errors, processing }"
        class="flex flex-col gap-6"
    >
        <div class="grid gap-6">
            <div class="grid gap-4">
                <div
                    class="grid grid-cols-2 gap-4"
                    role="radiogroup"
                    aria-label="Account type"
                >
                    <button
                        type="button"
                        role="radio"
                        :aria-checked="selectedProfileType === 'candidate'"
                        :tabindex="1"
                        autofocus
                        data-test="register-profile-type-candidate"
                        class="flex items-start gap-3 rounded-lg border border-input bg-background p-4 text-left transition-colors hover:bg-muted/40"
                        :class="
                            selectedProfileType === 'candidate'
                                ? 'border-primary bg-primary/5'
                                : 'border-input'
                        "
                        @click="selectedProfileType = 'candidate'"
                    >
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-md bg-muted"
                        >
                            <UserRound class="h-5 w-5 text-foreground" />
                        </div>

                        <div class="flex flex-col gap-1 pt-0.5">
                            <div class="text-base font-semibold">
                                I'm looking for a job
                            </div>
                            <div class="text-sm text-muted-foreground">
                                Candidate profile
                            </div>
                        </div>
                    </button>

                    <button
                        type="button"
                        role="radio"
                        :aria-checked="selectedProfileType === 'employer'"
                        :tabindex="2"
                        data-test="register-profile-type-employer"
                        class="flex items-start gap-3 rounded-lg border border-input bg-background p-4 text-left transition-colors hover:bg-muted/40"
                        :class="
                            selectedProfileType === 'employer'
                                ? 'border-primary bg-primary/5'
                                : 'border-input'
                        "
                        @click="selectedProfileType = 'employer'"
                    >
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-md bg-muted"
                        >
                            <BriefcaseBusiness
                                class="h-5 w-5 text-foreground"
                            />
                        </div>

                        <div class="flex flex-col gap-1 pt-0.5">
                            <div class="text-base font-semibold">
                                I'm hiring
                            </div>
                            <div class="text-sm text-muted-foreground">
                                Employer profile
                            </div>
                        </div>
                    </button>
                </div>

                <input
                    type="hidden"
                    name="account_type"
                    :value="selectedProfileType"
                />
            </div>

            <!-- Name: optional at DB level; collect later in profile/settings if needed.
            <div class="grid gap-2">
                <Label for="name">Name</Label>
                <Input
                    id="name"
                    type="text"
                    required
                    :tabindex="3"
                    autocomplete="name"
                    name="name"
                    placeholder="Full name"
                />
                <InputError :message="errors.name" />
            </div>
            -->

            <div class="grid gap-2">
                <Label for="email">Email address</Label>
                <Input
                    id="email"
                    type="email"
                    required
                    :tabindex="3"
                    autocomplete="email"
                    name="email"
                    placeholder="email@example.com"
                />
                <InputError :message="errors.email" />
            </div>

            <div class="grid gap-2">
                <Label for="password">Password</Label>
                <PasswordInput
                    id="password"
                    required
                    :tabindex="4"
                    autocomplete="new-password"
                    name="password"
                    placeholder="Password"
                />
                <InputError :message="errors.password" />
            </div>

            <div class="grid gap-2">
                <Label for="password_confirmation">Confirm password</Label>
                <PasswordInput
                    id="password_confirmation"
                    required
                    :tabindex="5"
                    autocomplete="new-password"
                    name="password_confirmation"
                    placeholder="Confirm password"
                />
                <InputError :message="errors.password_confirmation" />
            </div>

            <Button
                type="submit"
                class="mt-2 w-full"
                tabindex="6"
                :disabled="processing"
                data-test="register-user-button"
            >
                <Spinner v-if="processing" />
                Create account
            </Button>
        </div>

        <div class="text-center text-sm text-muted-foreground">
            Already have an account?
            <TextLink
                :href="login()"
                class="underline underline-offset-4"
                :tabindex="7"
                >Log in</TextLink
            >
        </div>
    </Form>
</template>
