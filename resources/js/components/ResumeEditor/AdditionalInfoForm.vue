<script setup lang="ts">
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { ChevronLeft, ChevronRight, Save } from 'lucide-vue-next';
import AlertSuccess from '@/components/AlertSuccess.vue';

interface Props {
    additionalInfo: any;
}

interface Emits {
    nextSection: [section: string];
}

defineEmits<Emits>();

const form = useForm({
    languages: '',
    certifications: '',
    interests: '',
    notes: '',
});

const showSuccessAlert = ref(false);

const initializeForm = () => {
    if (props.additionalInfo) {
        form.languages = props.additionalInfo.languages || '';
        form.certifications = props.additionalInfo.certifications || '';
        form.interests = props.additionalInfo.interests || '';
        form.notes = props.additionalInfo.notes || '';
    }
};

const props = defineProps<Props>();

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

initializeForm();
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
                        <label for="languages" class="block text-sm font-medium text-foreground mb-1">
                            Languages
                        </label>
                        <Textarea
                            id="languages"
                            v-model="form.languages"
                            placeholder="e.g., English (Native), Spanish (Fluent), French (Intermediate)"
                            rows="3"
                        />
                    </div>

                    <div>
                        <label for="certifications" class="block text-sm font-medium text-foreground mb-1">
                            Certifications
                        </label>
                        <Textarea
                            id="certifications"
                            v-model="form.certifications"
                            placeholder="e.g., AWS Certified Solutions Architect, Google Cloud Professional"
                            rows="3"
                        />
                    </div>

                    <div>
                        <label for="interests" class="block text-sm font-medium text-foreground mb-1">
                            Interests
                        </label>
                        <Textarea
                            id="interests"
                            v-model="form.interests"
                            placeholder="e.g., Machine Learning, Open Source, Mentoring"
                            rows="3"
                        />
                    </div>

                    <div>
                        <label for="notes" class="block text-sm font-medium text-foreground mb-1">
                            Additional Notes
                        </label>
                        <Textarea
                            id="notes"
                            v-model="form.notes"
                            placeholder="Any other information you'd like to add"
                            rows="4"
                        />
                    </div>

                    <div class="flex gap-3 pt-4 border-t">
                        <Button
                            type="submit"
                            :disabled="form.processing"
                        >
                            <Save class="h-4 w-4 mr-2" />
                            Save Additional Info
                        </Button>
                        </div>
                    <div class="flex justify-between gap-3 pt-4">
                        <Button
                            type="button"
                            variant="outline"
                            @click="$emit('nextSection', 'projects')"
                        >
                            <ChevronLeft class="h-4 w-4 mr-2" />
                            Back
                        </Button>
                        <Button
                            type="button"
                            @click="$emit('nextSection', 'summary')"
                        >
                            Review Summary
                            <ChevronRight class="h-4 w-4 ml-2" />
                        </Button>
                    </div>
                </form>
            </CardContent>
        </Card>
    </div>
</template>
