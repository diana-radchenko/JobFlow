<script setup lang="ts">
import { Check } from 'lucide-vue-next';
import { CheckboxIndicator, CheckboxRoot } from 'reka-ui';
import type { CheckboxRootProps } from 'reka-ui';
import type { HTMLAttributes } from 'vue';

defineProps<
    CheckboxRootProps & { class?: HTMLAttributes['class']; label?: string }
>();

const propsModelValue = defineModel<boolean>();
</script>

<template>
    <div class="flex flex-col gap-2.5">
        <label
            class="flex flex-row items-center gap-4 [&>.checkbox]:hover:bg-neutral-100"
        >
            <CheckboxRoot
                v-model="propsModelValue"
                class="flex h-5 w-5 appearance-none items-center justify-center rounded-md border bg-white shadow-sm outline-none hover:bg-stone-50"
            >
                <CheckboxIndicator
                    class="flex h-full w-full items-center justify-center rounded bg-white"
                >
                    <Check class="size-3.5" />
                </CheckboxIndicator>
            </CheckboxRoot>
            <span
                v-if="label"
                class="cursor-pointer text-sm text-stone-700 select-none dark:text-white"
            >
                {{ label }}
            </span>
        </label>
    </div>
</template>
