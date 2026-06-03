<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { ChevronRight, Save } from 'lucide-vue-next';
import { ref } from 'vue';
import AlertSuccess from '@/components/AlertSuccess.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatDateForInput } from '@/helpers/dates';
import personalInfo from '@/routes/resume-editor/personal-info';
import type { User, UserProfile } from '@/types/laravel-models';

interface Props {
    user: User;
    profile: UserProfile | null;
}

interface Emits {
    nextSection: [section: string];
}

const props = defineProps<Props>();

defineEmits<Emits>();

const toFormValues = (profile: UserProfile | null) => ({
    first_name: profile?.first_name ?? '',
    last_name: profile?.last_name ?? '',
    middle_name: profile?.middle_name ?? '',
    date_of_birth: formatDateForInput(profile?.date_of_birth),
    phone: profile?.phone ?? '',
    linkedin_url: profile?.linkedin_url ?? '',
    city: profile?.city ?? '',
    country: profile?.country ?? '',
});

const form = useForm(toFormValues(props.profile));

const showSuccessAlert = ref(false);

const submit = () => {
    form.post(personalInfo.update.url(), {
        onSuccess: () => {
            showSuccessAlert.value = true;
            setTimeout(() => {
                showSuccessAlert.value = false;
            }, 3000);
        },
    });
};

const filterPhoneInput = (event: Event) => {
    const input = event.target as HTMLInputElement;
    const filtered = input.value.replace(/[^\d+\s\(\)\-]/g, '');
    form.phone = filtered;
};
</script>

<template>
    <div class="space-y-6">
        <AlertSuccess
            v-if="showSuccessAlert"
            title="Saved!"
            message="Your personal information has been saved successfully."
        />
        <Card>
            <CardHeader>
                <CardTitle>Personal Information</CardTitle>
                <CardDescription>
                    Your name, contact details, and location
                </CardDescription>
            </CardHeader>
            <CardContent>
                <form @submit.prevent="submit" class="space-y-4">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="first_name">First name *</Label>
                            <Input
                                id="first_name"
                                v-model="form.first_name"
                                type="text"
                                name="first_name"
                                autocomplete="given-name"
                                required
                            />
                            <InputError :message="form.errors.first_name" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="last_name">Last name *</Label>
                            <Input
                                id="last_name"
                                v-model="form.last_name"
                                type="text"
                                name="last_name"
                                autocomplete="family-name"
                                required
                            />
                            <InputError :message="form.errors.last_name" />
                        </div>
                    </div>

                    <div class="grid gap-2">
                        <Label for="middle_name">Middle name</Label>
                        <Input
                            id="middle_name"
                            v-model="form.middle_name"
                            type="text"
                            name="middle_name"
                            autocomplete="additional-name"
                        />
                        <InputError :message="form.errors.middle_name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="email">Email</Label>
                        <Input
                            id="email"
                            :model-value="props.user.email"
                            disabled
                            class="bg-muted"
                        />
                        <p class="text-xs text-foreground/60">
                            Your account email
                        </p>
                    </div>

                    <div class="grid gap-2">
                        <Label for="date_of_birth">Date of birth</Label>
                        <Input
                            id="date_of_birth"
                            v-model="form.date_of_birth"
                            type="date"
                            name="date_of_birth"
                            autocomplete="bday"
                        />
                        <InputError :message="form.errors.date_of_birth" />
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="city">City</Label>
                            <Input
                                id="city"
                                v-model="form.city"
                                type="text"
                                name="city"
                                autocomplete="address-level2"
                            />
                            <InputError :message="form.errors.city" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="country">Country</Label>
                            <Input
                                id="country"
                                v-model="form.country"
                                type="text"
                                name="country"
                                autocomplete="country-name"
                            />
                            <InputError :message="form.errors.country" />
                        </div>
                    </div>

                    <div class="grid gap-2">
                        <Label for="phone">Phone number</Label>
                        <Input
                            id="phone"
                            v-model="form.phone"
                            type="tel"
                            name="phone"
                            placeholder="+1 (555) 123-4567"
                            @input="filterPhoneInput"
                        />
                        <InputError :message="form.errors.phone" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="linkedin">LinkedIn URL</Label>
                        <Input
                            id="linkedin"
                            v-model="form.linkedin_url"
                            type="url"
                            name="linkedin_url"
                            placeholder="https://linkedin.com/in/yourprofile"
                        />
                        <InputError :message="form.errors.linkedin_url" />
                    </div>

                    <div class="flex justify-between gap-3 pt-4">
                        <Button type="submit" :disabled="form.processing">
                            <Save class="mr-2 h-4 w-4" />
                            Save Personal Info
                        </Button>
                        <Button
                            type="button"
                            variant="outline"
                            @click="$emit('nextSection', 'workExperience')"
                        >
                            Next
                            <ChevronRight class="ml-2 h-4 w-4" />
                        </Button>
                    </div>
                </form>
            </CardContent>
        </Card>
    </div>
</template>
