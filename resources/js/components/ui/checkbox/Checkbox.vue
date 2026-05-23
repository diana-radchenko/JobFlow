<script setup lang="ts">
import type { CheckboxRootProps } from "reka-ui"
import type { HTMLAttributes } from "vue"
import { Check } from "lucide-vue-next"
import { CheckboxIndicator, CheckboxRoot } from "reka-ui"

const props = defineProps<CheckboxRootProps & { class?: HTMLAttributes["class"] , label: string }>()

const propsModelValue = defineModel<boolean>()

</script>

<template>
<div class="flex flex-col gap-2.5">
    <label class="flex flex-row gap-4 items-center [&>.checkbox]:hover:bg-neutral-100">
      <CheckboxRoot
        v-model="propsModelValue"
        class="hover:bg-stone-50 flex h-5 w-5 appearance-none items-center justify-center rounded-md bg-white shadow-sm border outline-none"
      >
        <CheckboxIndicator class="bg-white h-full w-full rounded flex items-center justify-center">
          <Check class="size-3.5" />
        </CheckboxIndicator>
      </CheckboxRoot>
      <span class="select-none text-stone-700 text-sm dark:text-white cursor-pointer">{{ label }}</span>
    </label>
  </div>
</template>
