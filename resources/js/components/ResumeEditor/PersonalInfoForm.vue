<script setup lang="ts">
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { ChevronRight, Save } from 'lucide-vue-next';
import AlertSuccess from '@/components/AlertSuccess.vue';

interface Props {
    user: any;
    profile: any;
}

interface Emits {
    nextSection: [section: string];
}

defineEmits<Emits>();

const form = useForm({
    phone: '',
    linkedin_url: '',
    location: '',
});

const showSuccessAlert = ref(false);

const initializeForm = () => {
    if (props.profile) {
        form.phone = props.profile.phone || '';
        form.linkedin_url = props.profile.linkedin_url || '';
        form.location = props.profile.location || '';
    }
};

const props = defineProps<Props>();

const submit = () => {
    form.post('/resume-editor/personal-info', {
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
    // Allow only digits, +, spaces, parentheses, and hyphens for formatting
    const filtered = input.value.replace(/[^\d+\s\(\)\-]/g, '');
    form.phone = filtered;
};

initializeForm();
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
                    Add your contact details and location
                </CardDescription>
            </CardHeader>
            <CardContent>
                <form @submit.prevent="submit" class="space-y-4">
                    <div>
                        <label for="name" class="block text-sm font-medium text-foreground mb-1">
                            Full Name
                        </label>
                        <Input
                            id="name"
                            :model-value="user.name"
                            disabled
                            class="bg-muted"
                        />
                        <p class="text-xs text-foreground/60 mt-1">
                            Your name from account settings
                        </p>
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-medium text-foreground mb-1">
                            Email
                        </label>
                        <Input
                            id="email"
                            :model-value="user.email"
                            disabled
                            class="bg-muted"
                        />
                        <p class="text-xs text-foreground/60 mt-1">
                            Your account email
                        </p>
                    </div>

                    <div>
                        <label for="phone" class="block text-sm font-medium text-foreground mb-1">
                            Phone Number
                        </label>
                        <Input
                            id="phone"
                            v-model="form.phone"
                            type="tel"
                            placeholder="+1 (555) 123-4567"
                            @input="filterPhoneInput"
                        />
                    </div>

                    <div>
                        <label for="location" class="block text-sm font-medium text-foreground mb-1">
                            Location
                        </label>
                        <Input
                            id="location"
                            v-model="form.location"
                            placeholder="City, Country"
                        />
                    </div>

                    <div>
                        <label for="linkedin" class="block text-sm font-medium text-foreground mb-1">
                            LinkedIn URL
                        </label>
                        <Input
                            id="linkedin"
                            v-model="form.linkedin_url"
                            type="url"
                            placeholder="https://linkedin.com/in/yourprofile"
                        />
                    </div>

                    <div class="flex justify-between gap-3 pt-4">
                        <Button
                            type="submit"
                            :disabled="form.processing"
                        >
                            <Save class="h-4 w-4 mr-2" />
                            Save Personal Info
                        </Button>
                        <Button
                            type="button"
                            variant="outline"
                            @click="$emit('nextSection', 'workExperience')"
                        >
                            Next
                            <ChevronRight class="h-4 w-4 ml-2" />
                        </Button>
                    </div>
                </form>
            </CardContent>
        </Card>
    </div>
</template>
