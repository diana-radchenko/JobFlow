<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight, Save } from 'lucide-vue-next';
import { ref } from 'vue';
import AlertSuccess from '@/components/AlertSuccess.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Textarea } from '@/components/ui/textarea';
import type { AdditionalInformation } from '@/types/laravel-models';

interface Props {
    additionalInfo: AdditionalInformation;
}

interface Emits {
    nextSection: [section: string];
}

const props = defineProps<Props>();

defineEmits<Emits>();

const toFormValues = (additionalInfo: AdditionalInformation | null) => ({
    languages: additionalInfo?.languages ?? '',
    certifications: additionalInfo?.certifications ?? '',
    interests: additionalInfo?.interests ?? '',
    notes: additionalInfo?.notes ?? '',
});

const form = useForm(toFormValues(props.additionalInfo));

const showSuccessAlert = ref(false);

const submit = () => {
    form.post('/resume-editor/additional-info', {
        onSuccess: () => {
            showSuccessAlert.value = true;
            setTimeout(() => {
                showSuccessAlert.value = false;
            }, 3000);
        },
    });
};
</script>

<template>
    <div class="space-y-6">
        <AlertSuccess
            v-if="showSuccessAlert"
            title="Saved!"
            message="Your additional information has been saved successfully."
        />
        <Card>
            <CardHeader>
                <CardTitle>Additional Information</CardTitle>
                <CardDescription>
                    Add languages, certifications, interests, and other details
                </CardDescription>
            </CardHeader>
            <CardContent>
                <form @submit.prevent="submit" class="space-y-4">
                    <div>
                        <label
                            for="languages"
                            class="mb-1 block text-sm font-medium text-foreground"
                        >
                            Languages
                        </label>
                        <Textarea
                            id="languages"
                            v-model="form.languages"
                            placeholder="e.g., English (Native), Spanish (Fluent), French (Intermediate)"
                            :rows="3"
                        />
                    </div>

                    <div>
                        <label
                            for="certifications"
                            class="mb-1 block text-sm font-medium text-foreground"
                        >
                            Certifications
                        </label>
                        <Textarea
                            id="certifications"
                            v-model="form.certifications"
                            placeholder="e.g., AWS Certified Solutions Architect, Google Cloud Professional"
                            :rows="3"
                        />
                    </div>

                    <div>
                        <label
                            for="interests"
                            class="mb-1 block text-sm font-medium text-foreground"
                        >
                            Interests
                        </label>
                        <Textarea
                            id="interests"
                            v-model="form.interests"
                            placeholder="e.g., Machine Learning, Open Source, Mentoring"
                            :rows="3"
                        />
                    </div>

                    <div>
                        <label
                            for="notes"
                            class="mb-1 block text-sm font-medium text-foreground"
                        >
                            Additional Notes
                        </label>
                        <Textarea
                            id="notes"
                            v-model="form.notes"
                            placeholder="Any other information you'd like to add"
                            :rows="4"
                        />
                    </div>

                    <div class="flex gap-3 border-t pt-4">
                        <Button type="submit" :disabled="form.processing">
                            <Save class="mr-2 h-4 w-4" />
                            Save Additional Info
                        </Button>
                    </div>
                    <div class="flex justify-between gap-3 pt-4">
                        <Button
                            type="button"
                            variant="outline"
                            @click="$emit('nextSection', 'projects')"
                        >
                            <ChevronLeft class="mr-2 h-4 w-4" />
                            Back
                        </Button>
                        <Button
                            type="button"
                            @click="$emit('nextSection', 'summary')"
                        >
                            Review Summary
                            <ChevronRight class="ml-2 h-4 w-4" />
                        </Button>
                    </div>
                </form>
            </CardContent>
        </Card>
    </div>
</template>
