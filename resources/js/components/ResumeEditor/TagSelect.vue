<script setup lang="ts">
import { onClickOutside } from '@vueuse/core';
import { X } from 'lucide-vue-next';
import { computed, ref, useTemplateRef } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';

interface Props {
    modelValue: string[];
    options: string[];
    placeholder?: string;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    'update:modelValue': [value: string[]];
}>();

const search = ref('');
const open = ref(false);
const root = useTemplateRef('root');

onClickOutside(root, () => (open.value = false));

const filteredOptions = computed(() => {
    const query = search.value.trim().toLowerCase();

    return props.options
        .filter((option) => !props.modelValue.includes(option))
        .filter((option) => !query || option.toLowerCase().includes(query))
        .slice(0, 8);
});

const addValue = (value: string) => {
    const trimmed = value.trim();

    if (!trimmed || props.modelValue.includes(trimmed)) {
        return;
    }

    emit('update:modelValue', [...props.modelValue, trimmed]);
    search.value = '';
};

const removeValue = (value: string) => {
    emit(
        'update:modelValue',
        props.modelValue.filter((item) => item !== value),
    );
};

const onEnter = () => {
    if (filteredOptions.value.length > 0) {
        addValue(filteredOptions.value[0]);
    } else {
        addValue(search.value);
    }
};
</script>

<template>
    <div ref="root" class="relative">
        <div
            v-if="modelValue.length > 0"
            class="mb-2 flex flex-wrap gap-2"
        >
            <Badge
                v-for="value in modelValue"
                :key="value"
                variant="secondary"
                class="gap-1 pr-1"
            >
                {{ value }}
                <button
                    type="button"
                    class="rounded-full hover:bg-foreground/10"
                    @click="removeValue(value)"
                >
                    <X class="h-3 w-3" />
                </button>
            </Badge>
        </div>

        <Input
            v-model="search"
            :placeholder="placeholder"
            autocomplete="off"
            @focus="open = true"
            @keydown.enter.prevent="onEnter"
        />

        <div
            v-if="open && filteredOptions.length > 0"
            class="absolute z-10 mt-1 max-h-48 w-full overflow-auto rounded-md border bg-popover p-1 shadow-md"
        >
            <button
                v-for="option in filteredOptions"
                :key="option"
                type="button"
                class="block w-full rounded-sm px-2 py-1.5 text-left text-sm hover:bg-accent hover:text-accent-foreground"
                @mousedown.prevent="addValue(option)"
            >
                {{ option }}
            </button>
        </div>
    </div>
</template>
