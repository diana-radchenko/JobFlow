<script setup lang="ts">
import { CheckCircle } from 'lucide-vue-next';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';

type Props = {
    message: string;
    title?: string;
};

const props = withDefaults(defineProps<Props>(), {
    title: 'Success!',
});
</script>

<template>
    <Alert
        variant="default"
        class="border-green-200 bg-green-50 dark:border-green-900 dark:bg-green-950"
    >
        <CheckCircle class="size-4 text-green-600 dark:text-green-400" />
        <AlertTitle class="text-green-900 dark:text-green-200">{{
            title
        }}</AlertTitle>
        <AlertDescription class="text-green-800 dark:text-green-300">
            {{ message }}
        </AlertDescription>
    </Alert>
</template>
